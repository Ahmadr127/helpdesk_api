# 05 — Business Logic Refactor

Status: **DRAFT — menunggu approval**
Bergantung pada: `02-target-database-design.md`, `03-rbac-redesign.md`, `04-data-migration-plan.md`

---

## 1. Prinsip

1. **Satu sumber logic** untuk seluruh pengajuan: `PengajuanService` + `PengajuanWorkflowService`.
2. Controller hanya menerima request, memanggil service, mengembalikan response.
3. Tidak ada lagi dua implementasi paralel untuk tiket dan order.
4. **Perilaku bisnis yang tidak diminta berubah tetap dipertahankan**:
   - hak edit/hapus pemilik saat status `open`;
   - alur konfirmasi user;
   - auto-confirm 24 jam;
   - alur penolakan;
   - aturan prioritas;
   - penerima notifikasi;
   - aturan laporan/export.
5. Perbedaan tiket vs order hanya pada:
   - `jenis_pengajuan` (IT / IPSRS);
   - field khusus IPSRS di `pengajuan_order_detail`;
   - label status di UI/API;
   - format nomor;
   - tampilan menu.
6. Semua perubahan multi-tabel dalam `DB::transaction`.
7. Notifikasi dikirim setelah commit (`DB::afterCommit`).

---

## 2. Komponen Target

| Komponen | Tanggung jawab |
|---|---|
| `Pengajuan` (Model) | Header pengajuan, relasi kategori/jenis/pemohon/lampiran/histori/pesan/penugasan. |
| `KategoriPengajuan` | Master kategori + jenis + role scope. |
| `RoleKategori` | Pivot role-kategori; menentukan scope, PJ default, penerima notifikasi. |
| `PengajuanService` | Create, update, delete, assign, add message, upload attachment, list/detail. |
| `PengajuanWorkflowService` | Transisi status, guard, histori, auto-confirm. |
| `PengajuanScopeService` | Scope data berdasarkan owner, role, kategori, penugasan. |
| `PengajuanNumberService` | Nomor unik per jenis/periode dengan row lock. |
| `PengajuanNotificationService` | Penerima, payload database/FCM, deep link. |
| `PengajuanAttachmentService` | Simpan/hapus file, metadata, validasi. |
| `PengajuanQueryService` | Filter, search, statistik, dashboard, export. |
| `LegacyPengajuanResolver` | Memetakan ID tiket/order lama ke pengajuan baru selama transisi. |
| `RoleService` | CRUD role, sync permission, sync kategori. |
| `PermissionService` | CRUD permission, sync role/user. |
| `AuditService` | Audit eksplisit untuk pivot dan aksi workflow. |

Komponen lama yang **dihapus setelah cutover**:

- `TicketService` (kecuali adapter sementara).
- `OrderPerbaikanService`.
- `OrderPerbaikanPjScopeService`.
- `PengajuanSinkronService` (TV decommission).
- `TicketResource`, `OrderPerbaikanResource` (diganti `PengajuanResource` + adapter v1).

---

## 3. Model dan Relasi

### 3.1 `Pengajuan`

```php
class Pengajuan extends Model
{
    use SoftDeletes;

    protected $table = 'pengajuan';

    protected $fillable = [
        'nomor', 'kategori_id', 'pemohon_id', 'pemohon_nama',
        'department_id', 'lokasi_id', 'building_id',
        'deskripsi', 'prioritas', 'status', 'follow_up', 'tanggal',
        'started_at', 'closed_at', 'confirmed_at', 'rejected_at',
        'rejection_count', 'user_confirmation', 'user_confirmed_at',
        'created_by', 'updated_by', 'legacy_source', 'legacy_id',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'user_confirmed_at' => 'datetime',
        'user_confirmation' => 'boolean',
        'rejection_count' => 'integer',
    ];
}
```

Relasi:

- `kategori()` belongsTo `KategoriPengajuan`.
- `jenis()` hasOneThrough / via kategori (`jenis_pengajuan`).
- `pemohon()` belongsTo `User`.
- `department()`, `lokasi()`, `building()`.
- `orderDetail()` hasOne `PengajuanOrderDetail`.
- `histori()` hasMany `PengajuanHistori`.
- `pesan()` hasMany `PengajuanPesan`.
- `lampiran()` hasMany `PengajuanLampiran`.
- `penugasan()` hasMany `PengajuanPenugasan`.
- `creator()`, `updater()`.
- `legacyMap()` hasOne `LegacyMap`.

### 3.2 `KategoriPengajuan`

- `jenis()` belongsTo `JenisPengajuan`.
- `roles()` belongsToMany `Role` via `role_kategori`.
- `pengajuan()` hasMany.
- Scope `aktif()`.

### 3.3 `PengajuanOrderDetail`

- Primary key `pengajuan_id`.
- Field: `unit_penerima`, `kode_inventaris`, `nama_barang`, `unit_proses_id`.
- Relasi `pengajuan()`, `unitProses()`.

### 3.4 Model pendukung

- `PengajuanHistori`: `pengajuan()`, `actor()`.
- `PengajuanPesan`: `pengajuan()`, `sender()`.
- `PengajuanLampiran`: `pengajuan()`, `histori()`, `pesan()`, `uploader()`.
- `PengajuanPenugasan`: `pengajuan()`, `user()`, `role()`, `assigner()`.
- `JenisPengajuan`.
- `StatusPengajuan` (jika tabel status dipakai; konstanta tetap di model).

---

## 4. Service Layer

### 4.1 `PengajuanService`

```php
create(User $actor, array $data, array $files = []): Pengajuan
updateOwn(User $actor, Pengajuan $pengajuan, array $data, array $files = []): Pengajuan
updateAdmin(User $actor, Pengajuan $pengajuan, array $data, array $files = [], array $userIdPIC = []): Pengajuan
deleteOwn(User $actor, Pengajuan $pengajuan): void
addMessage(User $actor, Pengajuan $pengajuan, string $body, array $files = []): PengajuanPesan
assign(User $actor, Pengajuan $pengajuan, array $userIds): void
```

Aturan create:

1. Validasi kategori aktif dan `jenis_id` sesuai pilihan.
2. Tentukan `pemohon_id = actor.id`, `pemohon_nama = actor.name`.
3. Tentukan department/lokasi/building dari input + fallback department user.
4. Generate nomor via `PengajuanNumberService` di dalam transaksi.
5. Insert header.
6. Jika jenis IPSRS: insert `pengajuan_order_detail`.
7. Insert histori `created` (actor user).
8. Simpan lampiran.
9. Setelah commit: notifikasi.

Aturan update:

- Hanya pemilik dan status `open` (perilaku lama).
- Admin boleh update submission selama aturan lama mengizinkan.
- Perubahan kategori memindahkan `jenis`? **Tidak** — kategori menentukan jenis; jika kategori berubah ke jenis lain, tolak atau minta konfirmasi. Ini keputusan yang harus disetujui (lihat §17).
- Perubahan kategori memperbarui scope PJ/notification.
- Histori `updated` dengan actor.

Aturan delete:

- Hanya pemilik dan status `open`.
- Soft delete header; lampiran tetap tersimpan.
- Histori `deleted`.
- Jangan hapus file fisik pada delete (perilaku lama menghapus file; ini perubahan perilaku — perlu keputusan; rekomendasi: pertahankan file untuk audit).

### 4.2 `PengajuanWorkflowService`

```php
start(User $actor, Pengajuan $pengajuan): Pengajuan
close(User $actor, Pengajuan $pengajuan, ?string $notes): Pengajuan
adminConfirm(User $actor, Pengajuan $pengajuan, ?string $notes): Pengajuan
adminReject(User $actor, Pengajuan $pengajuan, ?string $notes): Pengajuan
userConfirm(User $actor, Pengajuan $pengajuan, ?string $notes, bool $selesai, array $files = []): Pengajuan
autoConfirmStale(int $hours = 24): int
```

Guard transisi:

| Aksi | Status awal | Status akhir | Aktor |
|---|---|---|---|
| start | `open` | `in_progress` | user dengan scope/PIC |
| close | `open`/`in_progress` | `pending_confirmation` | user dengan scope/PIC |
| adminConfirm | `pending_confirmation` (atau status lama yang diizinkan) | `confirmed` | user dengan scope |
| adminReject | `open`/`in_progress`/`pending_confirmation` | `rejected` | user dengan scope |
| userConfirm selesai | `pending_confirmation` | `confirmed` | pemilik |
| userConfirm belum selesai | `pending_confirmation` | `in_progress` | pemilik |
| autoConfirm | `pending_confirmation` + `closed_at <= now-24h` | `confirmed` | system |

Catatan:

- **Jangan menambah guard baru yang mempersempit** perilaku lama tanpa persetujuan. Guard di atas merepresentasikan perilaku yang ada; beberapa transisi lama longgar (mis. admin confirm dari status apa pun). Untuk production, longgarkan hanya jika diperlukan kompatibilitas; catat di test.
- Setiap transisi menulis `pengajuan_histori` dan meng-update timestamp terkait.
- `closed_at` diisi saat masuk `pending_confirmation`.
- `confirmed_at` diisi saat `confirmed`.
- `rejected_at` diisi saat `rejected`.
- `rejection_count` naik saat user menolak konfirmasi.
- `user_confirmation` true saat user confirm; false saat reject.
- Histori `actor_type=system` dan `actor_id=null` untuk auto-confirm; kolom actor nullable.

### 4.3 `PengajuanScopeService`

```php
visibleTo(User $user): Builder
canView(User $user, Pengajuan $pengajuan): bool
canAct(User $user, Pengajuan $pengajuan): bool
categoryIdsFor(User $user): array
```

Algoritma:

1. Jika `$user->hasPermission('pengajuan.view_all')` → semua.
2. Jika `$pengajuan->pemohon_id === $user->id` → boleh lihat.
3. Ambil role user (`users.role`).
4. Ambil kategori dari `role_kategori` untuk role tersebut.
5. Jika kategori pengajuan ada di daftar → boleh lihat.
6. Aksi (start/close/confirm/reject/assign) hanya jika:
   - role user ada di `role_kategori` untuk kategori tersebut, **atau**
   - user punya `pengajuan_penugasan` aktif.
7. Jika tidak ada role_kategori untuk role user → fallback: user dengan permission dashboard terkait boleh melihat kategori yang belum punya role? **Jangan** fallback otomatis tanpa keputusan; production seed mengisi semua kategori.

### 4.4 `PengajuanNumberService`

```php
next(string $jenisCode): string
```

- Lock row `document_sequences` dengan `SELECT ... FOR UPDATE`.
- Format:
  - IT: `T-{ddmm}-{seq3}` (format lama tiket, `date('dm')`).
  - IPSRS: `OP/RTG/MTC-{Ymd}{seq3}` (format lama order).
- Jika sequence belum ada, hitung dari nomor terakhir yang match, lalu insert.
- Retry pada unique violation maksimal 3x.
- Jangan mengubah nomor lama saat migrasi.

### 4.5 `PengajuanNotificationService`

```php
notifyCreated(Pengajuan $pengajuan, User $actor): void
notifyStatusChanged(Pengajuan $pengajuan, User $actor, string $event): void
notifyMessage(Pengajuan $pengajuan, User $actor, PengajuanPesan $pesan): void
recipientsFor(Pengajuan $pengajuan, ?User $except = null): Collection
```

Penerima:

- **PJ/kategori**: semua user aktif yang role-nya ada di `role_kategori` untuk kategori pengajuan, kecuali aktor.
- **Pemohon**: untuk perubahan status oleh admin.
- **Fallback**: user dengan permission `dashboard.it` (IT) / `dashboard.ipsrs` (IPSRS) jika role_kategori kosong — hanya jika disetujui; production seed harusnya tidak kosong.
- Jangan mengirim ke pemohon untuk event yang dibuat oleh pemohon sendiri.

Payload FCM wajib mempertahankan field lama:

```json
{
  "type": "ticket_created|order_status_updated|...",
  "pengajuan_id": "123",
  "ticket_id": "45",
  "order_id": "67",
  "ticket_number": "T-...",
  "nomor": "OP/...",
  "status": "pending_confirmation",
  "prioritas": "high",
  "deep_link": "helpdesk://pengajuan/123"
}
```

- `ticket_id`/`order_id` diisi dari `legacy_id` bila ada; untuk data baru, isi `pengajuan_id` dan `legacy_id=null`.
- Deep link lama tetap berfungsi melalui resolver.

### 4.6 `PengajuanAttachmentService`

- Validasi: max 3 file, mime image/pdf, ukuran sesuai endpoint lama (2MB/5MB).
- Simpan ke disk `public` dengan subfolder per jenis: `pengajuan/{jenis}/{tahun}/{bulan}`.
- Nama file aman (sanitasi).
- Simpan metadata.
- Hapus file hanya saat admin melakukan hard cleanup, bukan saat soft delete.

### 4.7 `AuditService`

- Model observer tetap untuk create/update/delete.
- Pivot role/permission/kategori diaudit eksplisit dengan event `role_permissions.updated`, `role_kategori.updated`, `permission_user.updated`.
- Audit menyertakan `actor_id`, `old_values`, `new_values`, `url`, `ip`, `user_agent`.
- Untuk migrasi, audit event `migrated` dengan `migration_run_id`.

---

## 5. Controller dan Route

### 5.1 Peta controller lama → baru

| Lama | Baru |
|---|---|
| `User\TicketController` | `PengajuanController` (filter jenis IT) |
| `AdministrasiUmum\OrderPerbaikanController` | `PengajuanController` (filter jenis IPSRS) |
| `Admin\TicketAdminController` | `Admin\PengajuanAdminController` (jenis IT) |
| `Api\User\TicketController` | `Api\V1\PengajuanController` (adapter) |
| `Api\User\OrderPerbaikanController` | `Api\V1\PengajuanController` (adapter) |
| `Api\Admin\TicketAdminController` | `Api\V1\AdminPengajuanController` |
| `Api\Admin\OrderPerbaikanAdminController` | `Api\V1\AdminPengajuanController` |
| `Admin\KategoriOrderController` | `Admin\KategoriPengajuanController` (+ jenis + roles) |
| `Admin\RoleController` (baru) | CRUD role |
| `Admin\PermissionController` | CRUD permission + role/user sync |

### 5.2 Web routes

Pertahankan URL lama selama masa transisi:

```text
/user/administrasi-umum/order-perbaikan/*   → PengajuanController (IPSRS)
/ticket/*                                   → PengajuanController (IT)
/order-perbaikan/myorder/*                  → PengajuanController (IPSRS, owner)
/tickets/myticket/*                         → PengajuanController (IT, owner)
/admin/order-perbaikan/*                    → AdminPengajuanController (IPSRS)
/admin/tickets/*                            → AdminPengajuanController (IT)
/admin/master/kategori-order/*              → KategoriPengajuanController
/admin/master/categories/*                  → KategoriPengajuanController (IT)
/admin/roles/*                              → RoleController (baru)
/admin/permissions/*                        → PermissionController
```

Route name lama dipertahankan agar Blade lama dan bookmark tidak rusak. Controller baru dapat merender view lama dengan data baru, atau view baru dengan route name yang sama.

### 5.3 API

**V1 compatibility (wajib):**

- Endpoint, field, status, dan ID lama tetap.
- `id` di response v1:
  - untuk data hasil migrasi: `legacy_id` (ID tiket/order lama);
  - untuk data baru: `pengajuan.id`.
- `ticket_number`/`nomor` diisi nomor lama/baru.
- `status` dipetakan kembali:
  - IT: `pending_confirmation` → `closed` (atau `pending` sesuai konteks endpoint; pilih satu dan dokumentasikan);
  - IPSRS: `pending_confirmation` → `tutup`.
- `can_edit`/`can_delete` dihitung dari aturan baru.
- `admin_responses`/`user_replies` direkonstruksi dari pesan/histori.
- `photos` dari `lampiran`.

**V2 (opsional, setelah Flutter siap):**

```text
GET    /api/v2/pengajuan
POST   /api/v2/pengajuan
GET    /api/v2/pengajuan/{id}
PUT    /api/v2/pengajuan/{id}
DELETE /api/v2/pengajuan/{id}
POST   /api/v2/pengajuan/{id}/start
POST   /api/v2/pengajuan/{id}/close
POST   /api/v2/pengajuan/{id}/confirm
POST   /api/v2/pengajuan/{id}/reject
POST   /api/v2/pengajuan/{id}/messages
POST   /api/v2/pengajuan/{id}/attachments
```

Jangan mengubah kontrak v1 secara diam-diam.

---

## 6. Form Request

| Request | Aturan utama |
|---|---|
| `StorePengajuanRequest` | kategori required aktif, jenis optional (diturunkan dari kategori), deskripsi required, prioritas in low/medium/high, department/lokasi/building exists, foto max 3 |
| `UpdatePengajuanRequest` | sama, milik sendiri, status open |
| `UpdatePengajuanAdminRequest` | field submission + `penanggung_jawab_ids` exists users |
| `UpdateStatusRequest` | status in open/in_progress/pending_confirmation/confirmed/rejected, notes nullable |
| `UserConfirmRequest` | konfirmasi in selesai/belum, catatan nullable, lampiran max 3 |
| `StoreMessageRequest` | body required, lampiran max 3 |
| `RoleRequest` | slug unique, name, description, permissions array exists, categories array exists |
| `PermissionRequest` | slug unique, name, group, is_system guarded |

Validasi kategori harus memastikan:
- kategori aktif;
- kategori milik jenis yang dipilih/didukung;
- role user punya akses ke kategori tersebut (kecuali permission khusus/admin).

---

## 7. Authorization

### 7.1 Middleware

- Tetap gunakan `permission:*` untuk gerbang menu/fitur.
- Hapus ketergantungan pada `isAdminIT()`/`isAdminUmum()` untuk data scope.
- `AdminApiMiddleware` dan `AdministrasiUmumApiMiddleware` dipertahankan untuk kompatibilitas, tetapi hanya sebagai gerbang fitur; scope data di service.
- Policy baru:
  - `PengajuanPolicy::view`, `update`, `delete`, `act`, `assign`.
  - `RolePolicy`, `PermissionPolicy`, `KategoriPolicy`.

### 7.2 Matriks akses

| Aksi | User pemilik | Role dengan kategori | PIC aktif | Permission khusus |
|---|---|---|---|---|
| Lihat | ya | ya | ya | view_all |
| Edit | open | — | — | — |
| Hapus | open | — | — | — |
| Start/Close | — | ya | ya | — |
| Confirm/Reject admin | — | ya | ya | — |
| Confirm user | pending_confirmation | — | — | — |
| Assign PIC | — | ya | ya | — |
| Kelola role/kategori | — | — | — | permission |

---

## 8. UI / Blade

Prinsip: **tampilan dan istilah lama dipertahankan** kecuali diperlukan oleh struktur baru.

- Halaman tiket tetap menampilkan istilah "Tiket", "Kategori", "Status".
- Halaman order tetap menampilkan "Order Perbaikan", "Kategori Order", "Prioritas".
- Detail pengajuan menampilkan:
  - header (nomor, tanggal, pemohon, kategori, unit, lokasi);
  - detail khusus IPSRS (unit penerima, kode inventaris, nama barang) hanya untuk jenis IPSRS;
  - histori;
  - pesan;
  - lampiran;
  - penugasan.
- Status label:
  - IT: `pending_confirmation` → "Menunggu Konfirmasi";
  - IPSRS: `pending_confirmation` → "Tutup / Menunggu Konfirmasi".
- Menu sidebar tetap; permission gate tidak berubah.
- Form create:
  - IT: kategori SIRS, lokasi/gedung, deskripsi, prioritas, foto.
  - IPSRS: kategori order, unit/lokasi/gedung, keluhan, prioritas, foto, field khusus production (unit penerima, kode inventaris, nama barang) bila form lama menampilkannya.
- Halaman master kategori baru menggabungkan kategori IT + IPSRS, dengan filter jenis dan assignment role.
- Halaman role baru: list, create, edit, delete, permission checkboxes, kategori checkboxes.

---

## 9. Dashboard, Filter, Statistik

- `DashboardService` menghitung berdasarkan `pengajuan` + filter `jenis`.
- Statistik yang dipertahankan:
  - total, open, in_progress, pending_confirmation, confirmed, rejected;
  - prioritas rendah/sedang/tinggi.
- Filter lama (search nomor/deskripsi/kategori, tanggal, status, prioritas, lokasi) tetap.
- Scope otomatis melalui `PengajuanScopeService`.
- Jangan mengubah angka/definisi dashboard tanpa persetujuan. `closed` + `confirmed` pada tiket lama dihitung sebagai `pending_confirmation` + `confirmed`; dokumentasikan di test.

---

## 10. Export dan Laporan

- `TicketsExport` → query `pengajuan` jenis IT; kolom dan format lama.
- `OrderPerbaikanExport` → query `pengajuan` jenis IPSRS; kolom lama termasuk unit penerima/kode/nama barang.
- `ReportSirsExport` → query `pengajuan` IT dengan filter tanggal/status yang sama.
- Status export lama (`confirmed`, `tutup`, `completed`) dipetakan ke status baru; validasi input diperbaiki agar tidak menerima status yang tidak ada.
- Nomor lama/baru mengikuti `pengajuan.nomor`.

---

## 11. Notifikasi, FCM, dan Deep Link

- Database notification tetap menggunakan `notifications` (morph).
- FCM tetap melalui `SendFirebaseNotificationJob`.
- `notification_settings` tetap.
- Deep link baru: `helpdesk://pengajuan/{id}`.
- Deep link lama:
  - `helpdesk://ticket/{legacy_id}` dan `helpdesk://order/{legacy_id}` diarahkan ke pengajuan yang sama.
- Payload tetap menyertakan `ticket_id`/`order_id` untuk Flutter lama.
- Penerima mengikuti `role_kategori`, bukan hardcode `User::adminIT()/adminUmum()`.
- Jangan mengubah aturan kapan notifikasi dikirim; hanya mekanisme penentuan penerima yang berubah.

---

## 12. Audit Log

- `AuditObserver` tetap otomatis untuk model.
- Tambah audit eksplisit:
  - role create/update/delete;
  - permission sync;
  - role-kategori sync;
  - user-role change;
  - penugasan;
  - workflow transition (histori sudah mencatat, audit mencatat perubahan model).
- Audit migrasi: event `migrated`, metadata `legacy_source`, `legacy_id`, `migration_run_id`.
- Jangan menghapus audit lama.

---

## 13. Scheduler dan Auto-Confirm

- Command tetap `pengajuan:auto-confirm` setiap jam.
- Query:

```php
Pengajuan::where('status', 'pending_confirmation')
    ->where('closed_at', '<=', now()->subHours($hours))
    ->whereNull('deleted_at')
    ->chunkById(100, ...);
```

- Aksi:
  - `status = confirmed`;
  - `confirmed_at = now()`;
  - histori `auto_confirmed` dengan actor system;
  - notifikasi ke pemohon + role_kategori (setelah commit).
- **Tidak** memakai kolom `created_by` histori yang NOT NULL; aktor system disimpan di `actor_type`.
- Idempotent: jika sudah confirmed, skip.
- Tidak mengubah aturan 24 jam.

---

## 14. Upload dan Lampiran

- Semua upload melalui `PengajuanAttachmentService`.
- Validasi ukuran/mime mengikuti endpoint lama:
  - web order: max 2MB/file, max 3;
  - API ticket: max 5MB/file, max 3.
- Jangan menyimpan array JSON di kolom varchar; setiap file = satu baris `pengajuan_lampiran`.
- Hapus file hanya pada hard delete administratif, bukan soft delete.
- Path lama dipertahankan; file tidak dipindahkan saat migrasi database.
- Akses file melalui storage URL yang sama.

---

## 15. Penomoran

- `document_sequences(jenis_id, period, last_value)`.
- `period` = `Ymd` untuk IPSRS, `dm` untuk IT (mengikuti format lama).
- Lock + increment dalam transaksi.
- Jika nomor lama ada, sequence diinisialisasi dari nomor tertinggi yang match.
- Unique `pengajuan.nomor` menjadi pengaman terakhir.
- Jangan memakai `MAX+1` tanpa lock.

---

## 16. Transaksi, Race Condition, Idempotensi

- Create/update/transition dalam `DB::transaction`.
- Nomor: row lock pada `document_sequences`.
- Unique violation: retry 3x untuk create.
- Notifikasi setelah commit.
- Auto-confirm: `chunkById`, cek status di dalam transaksi, skip jika sudah berubah.
- Sinkronisasi TV dihapus; tidak ada dua penulisan.
- Migration: `legacy_map` unique + `migration_runs`.

---

## 17. Dampak dan Keputusan yang Masih Perlu Persetujuan

1. **Perubahan kategori ke jenis lain** saat edit: tolak atau izinkan? Rekomendasi: tolak pada edit owner; admin dapat memindahkan dengan histori khusus.
2. **Admin confirm** saat ini menulis `closed` (bukan `confirmed`). Di model baru, apakah admin confirm berarti:
   - A. `pending_confirmation` (menunggu user), atau
   - B. `confirmed` (admin menutup final)?
   Rekomendasi: A, untuk mempertahankan alur user confirmation.
3. **Hapus file saat delete**: perilaku lama menghapus file; model baru merekomendasikan retensi. Perlu keputusan.
4. **Fallback notifikasi** jika role_kategori kosong: kirim ke semua pemegang dashboard atau tidak kirim? Rekomendasi: tidak kirim + log, karena production seed mengisi.
5. **`view_all`**: perlu permission baru atau tidak? Rekomendasi: tidak untuk fase ini; scope role-kategori sudah cukup.
6. **Status API v1** untuk `pending_confirmation` IT: `closed` atau `pending`? Rekomendasi: `closed` (mayoritas jalur saat ini), dokumentasikan.

---

## 18. Urutan Implementasi Logic (ringkas)

1. Model + migration + seeder jenis/kategori/status.
2. `PengajuanNumberService` + `PengajuanScopeService`.
3. `PengajuanService` create/read/update/delete.
4. `PengajuanWorkflowService`.
5. `PengajuanAttachmentService`.
6. `PengajuanNotificationService`.
7. Controller web + route lama.
8. API v1 adapter + resource.
9. Dashboard/filter/statistik/export.
10. Role/permission/kategori CRUD.
11. Scheduler + audit.
12. Hapus service lama setelah cutover.

Detail pekerjaan, file, dan acceptance criteria ada di `06-implementation-tasks.md`.
