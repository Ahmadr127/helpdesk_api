# Plan: Multi Flavour/Role Kategori (kategori bisa dipakai banyak role tanpa duplikat)

> Status: **ANALISIS + PLANNING** (belum implement end-to-end).
> Fokus: membongkar hambatan "satu kategori → satu jenis" sehingga kategori yang di-assign ke beberapa role (contoh: **TV → role A, B, C**) muncul di seluruh role tersebut, tanpa perlu membuat kategori duplikat.

---

## 1. Konteks & Tujuan

**Masalah yang dirasakan user:**

> "agar suatu kategori bisa muncul di seluruh role yang di-assign — misalkan TV adalah tugas dari role A, B, C, maka ketiga role ini bisa muncul — tetapi saat ini terhambat dengan jenis kategori."

**Fakta di sistem saat ini (sebelum perubahan):**

| Tempat | Perilaku |
|---|---|
| `kategori_pengajuan.jenis_id` (single, FK) | Setiap kategori **hanya** terkait ke **satu** flavour: IT (Tiket/SIRS) atau IPSRS (Order Perbaikan). |
| Query list tiket/order | `whereHas('kategori.jenis', code=X)` — perlu kategori berjenis X untuk ikut tampil. |
| Nomor pengajuan | `PengajuanService::create` → `numberService->next($kategori->jenis->code)` → prefix dari kategori single. |
| Display nama "Jenis" | `$kategori->jenis?->name` / `->code` (single). |
| Scope penglihatan list (IPSRS user vs admin) | `whereHas('kategori.jenis')` — kategori IPSRS tidak masuk list admin IT, sebaliknya IT tidak masuk list IPSRS. |
| Role assignment (`role_kategori`) | **Independen** — bisa isi kombinasi apa pun (butuh seeding custom). |

**Kenapa role assignment saja belum cukup:**

Pertanyaan yang ditentukan `jenis_pengajuan` itu **bukan soal role**, tapi soal **peforma/peran kategori**:
flavour yang menentukan prefix nomor, UI konfirmasi/order, daftar terkategori, dan gate list.
Role assignment (`role_kategori`) hanya menentukan siapa yang melihat/dapat di-assign ke kategori itu, yang tidak bisa menutup kebutuhan flavour (multi flavour) pada satu baris pengajuan.

**Tujuan akhir:**

1. Satu kategori **tidak dipaksa single flavour** lagi. Dipilih oleh admin di master flavours mana saja ia relevan, tanpa duplikasi.
2. Pengajuan yang dibuat user dari kategori multi-flavour tetap punya **satu flavour tunggal** yang jelas untuk prefix nomor, wording konfirmasi, dan routing list — ditentukan dari **data pengajuan**, bukan dari kategori.
3. Role scope/visibilitas tetap independen (`role_kategori`) dan tidak terikat oleh flavour — multi-flavour tidak boleh memecah scope visibility.

---

## 2. Opsi Desain & Trade-off

### Opsi A — Tambah `pengajuan.jenis_id` (DIREKOMENDASIKAN)

Tambah FK `pengajuan.jenis_id → jenis_pengajuan.id` dan diisi **saat create** dari jalur proses (ticket → IT, order → IPSRS). Backfill data lama dari `kategori_pengajuan.jenis_id`.

| Kriteria | Hasil |
|---|---|
| Prefix nomor | `next($pengajuan->jenis_id)` — akurat, tidak ambigu. |
| Wording konfirmasi / is IPSRS | `in_array('IPSRS', ...)` atau perbandingan `jenis_id` — selalu benar, tidak butuh jenis kategori. |
| Query list/tab (tiket vs order) | `where('pengajuan.jenis_id', ?)` — indeksable, simpel, cepat. |
| Query "filter flavour" di report | Ganti join `kategori_pengajuan.jenis_id` → `pengajuan.jenis_id`. |
| Safety data lama | Backfill dari kategori (saat ini 1:1 → aman). |
| Refactor | Ganti seluruh `whereHas('kategori.jenis')` → `where('pengajuan.jenis_id', ...)`.

**Catatan:** relasi BC `KategoriPengajuan::jenis()` (belongsTo `jenis_id`, single first-linked) dibiarkan sebagai *fallback/backup* tapi bukan sumber kebenaran lagi. Flavor pengajuan resmi = `pengajuan->jenis_id`.

### Opsi B — Derive dari kategori saja (tidak ada kolom baru)

Flavour pengajuan = flavour kategori (multi-flavour kategori → flavour manapun yang didefinisikan sebagai *first* adalah source of truth default).

| Kriteria | Hasil |
|---|---|
| Sederhana | ya, tidak ada migrasi skema. |
| Status kebenaran | **ambigu** — kategori Printer (IT+IPSRS) tidak bisa membedakan apakah ini order atau ticket.
| Nomor | mengikuti flavour first-linked (IT) — salah untuk order. |
| Wording konfirmasi | salah — order akan ditampilkan seperti ticket.
| Risiko | high — tidak dapat diandalkan sebagai source of truth.

### Opsi C — Tantangan dual-label (virtual column)

Tetap derive dari kategori sebagai `array_flavours`; pengajuan diberi flavour **setiap create** secara eksplisit di form (dropdown jenis flavour saat submit tiket/form order).

| Kriteria | Hasil |
|---|---|
| Sederhana | sedang. |
| Beban user | user harus tahu flavour pengajuannya — tidak ideal.
| Risiko | sumber kebenaran tetap berada di UX, bukan data. Dropdown bisa salah pilih.

---

## 3. Desain Target (Opsi A)

### 3.1 Skema

**Tabel pivot `kategori_jenis` (sudah ada dari tahap multi-jenis):**
`kategori_pengajuan (1) — (M) kategori_jenis (M) — (1) jenis_pengajuan`

**Tabel baru & perubahan:**

- `pengajuan.jenis_id` — FK ke `jenis_pengajuan.id`, index `pengajuan_jenis_id_index`, backfill dari `kategori_pengajuan.jenis_id`.

```
pengajuan.jenis_id:   unsignedBigInteger, nullable sementara → NOT NULL setelah backfill
manipulasi data:       kategori → pengajuan: jenis_id := kategori.jenis_id (BC: first-linked untuk multi)
relasi:                pengajuan → jenis_pengajuan
```

### 3.2 Model & relations (target)

`App\Models\Pengajuan` (tambah relasi):

- `jenis()` → belongsTo `JenisPengajuan::class`, foreign `jenis_id`.
- `kategoriJenis()` → accessor koleksi kode flavour kategori (untuk tampilan, opsional).
- Perbarui `$fillable` (`jenis_id`).

`KategoriPengajuan` (sudah): `jenisList()` belongsToMany pivot, `jenisDisplay`/`jenisCodes` accessors. `jenis()` legacy remains for BC only.

### 3.3 Create path (redirect flavour)

`PengajuanService::create($actor, $data, $files)`:

- Tambah parameter opsional `jenis` (atau `jenis_id`) yang disetel **di sisi call-site**:
  - `TicketController@store` (halaman UI Tiket) → `'IT'`
  - `AdministrasiUmumController@storeOrder` (halaman UI Order Perbaikan) → `'IPSRS'`
  - `Api\V1\PengajuanController@store` → dari company/route default (route mendefinisikan jenis: `/api/tiket` vs `/api/order-perbaikan`, lihat RouteServiceProvider).
  - `Api\V1\AdminPengajuanController@start` → tidak diubah.
- Simpan `jenis_id` ke `pengajuan`; gunakan untuk `numberService->next()`.
- Validasi `kategori.jenis_id` — mis. kategori tidak punya flavour dimaksud *tetap* diizinkan (ini multi-flavour kategori dan submit lewat jalur mana pun — memadai); format validasi: `required|exists:jenis_pengajuan,id`.

### 3.4 Queries

Ganti semua `whereHas('kategori.jenis', ...)` dengan `where('pengajuan.jenis_id', ...)` — termasuk scope, dashboard, report, export CSV, search:

| File/lokasi | Penggantian |
|---|---|
| `Admin\TicketAdminController::itQuery()` | `where('jenis_id', IT_ID)` |
| `Admin\TicketController::baseQuery()` | sama |
| `User\TicketController::baseQuery()` | sama |
| `User\AdministrasiUmumController::baseQuery()` | `where('jenis_id', IPSRS_ID)` |
| `AdministrasiUmum\OrderPerbaikanController::baseQuery()` | sama |
| `Api\V1\*` / `Api\V1\AdminPengajuanController` | sama |
| `AppServiceProvider` | sama |
| `PengajuanDashboardService` | sama |
| `PengajuanScopeService` | **tidak diubah** (role_kategori) |
| `PengajuanNotificationService` | `$pengajuan->jenis_id` langsung |
| `PengajuanAttachmentService` | `$pengajuan->jenis_id` langsung |

Helper id: `JenisPengajuan::where('code', 'IT')->first()?->id` (cache di satu konstanta `Jenis::idIT` / `::idIpsrs` — lihat 3.5).

### 3.5 Konstanta jenis

Tambahkan `App\Models\JenisPengajuan::idIt()` / `::idIpsrs()` (atau `config/jenis.php`) agar tidak dipanggil berulang.

```php
// config/jenis.php
return [
    'IT'    => env('JENIS_IT_ID'),
    'IPSRS' => env('JENIS_IPSRS_ID'),
];
```

Alternatif: pakai `JenisPengajuan::query()->where('code', 'IT')->sole()->id` di booted() dengan **cache `file` lokal** — hindari.

### 3.6 Tampilan & wording

- `user/pengajuan/_detail.blade.php`: `$isOrder = ($pengajuan->jenis_id === ID_IPSRS)`;
- `konfirmasiFields`, `showOrderFields`, judul `Detail Tiket/Order` — semua dari `jenis_id` pengajuan.

### 3.7 Seeding (UnifiedPengajuanSeeder/MigrationCategories)

- Seed pivot `kategori_jenis` + denorm join kolom `kategori_pengajuan.jenis_id` (BC) dari kategori asli.
- `role_kategori` dibiarkan (independen).

---

## 4. Inventarisasi File

### Database

| File | Aksi |
|---|---|
| `database/migrations/YYYY_MM_DD_HHMMSS_add_jenis_id_to_pengajuan.php` | **BARU**: tambah FK + index + backfill `pengajuan.jenis_id` dari `kategori_pengajuan.jenis_id`. |
| `database/seeders/UnifiedPengajuanSeeder.php` | print/verify BB setelah seed (tidak ubah logika role). |
| `database/seeders/MigrationCategories.php` | update `where('jenis_id', ...)` → `whereHas('jenisList', ...)`. |

### Models

| File | Aksi |
|---|---|
| `app/Models/Pengajuan.php` | tambah `jenis_id` fillable/casts/relasi `jenis()`. |

### Controllers/Services (query)

| File | Aksi |
|---|---|
| `app/Services/Pengajuan/PengajuanService.php` | `create()`: params `jenis`; hitung nomor dari `jenis`; validasi; `Pengajuan::create` set `jenis_id`. |
| `app/Services/Pengajuan\PengajuanScopeService.php` | **tidak diubah**. |
| `app/Services/Pengajuan\PengajuanNumberService.php` | `next($jenisCode)` tetap — dipanggil dengan kode dari `jenis_id` baru. |
| `app/Services/Pengajuan\PengajuanNotificationService.php` | `$pengajuan->jenis_id` langsung (ganti BC fallback). |
| `app/Services/Pengajuan\PengajuanAttachmentService.php` | sama. |
| `app/Services/Pengajuan\PengajuanDashboardService.php` | `whereHas` → `where('jenis_id', ?)`. |
| `app/Http/Controllers/Admin/TicketAdminController.php` | `itQuery()` → `where('jenis_id', IT)`. |
| `app/Http/Controllers/Admin/TicketController.php` | sama. |
| `app/Http/Controllers/User/TicketController.php` | sama. |
| `app/Http/Controllers/User/AdministrasiUmumController.php` | `baseQuery()` → `where('jenis_id', IPSRS)`. |
| `app/Http/Controllers/AdministrasiUmum/OrderPerbaikanController.php` | sama. |
| `app/Http/Controllers/Api/V1/PengajuanController.php` | `jenis()` dari route/request → `where('jenis_id', ?)`. |
| `app/Http/Controllers/Api/V1/AdminPengajuanController.php` | sama untuk list/scope. |
| `app/Providers/AppServiceProvider.php` | ganti join/whereHas ke `jenis_id`. |
| `app/Http/Controllers/Api/LookupController.php` | rename BC field `jenis_id` pengajuan bila perlu (tetap tampilkan joint, opsional). |
| `app/Http/Controllers/Admin/KategoriPengajuanController.php` | sudah multi-flavour (done). |
| `app/Http/Controllers/Admin/MasterDataController.php`, `RoleController.php` | sudah `jenisList` (done). |

### Views

| File | Aksi |
|---|---|
| `reports/_table.blade.php`, `_filter-dropdowns.blade.php` | sudah joint (done). |
| `user/pengajuan/_detail.blade.php` | `$isOrder = pengajuan.jenis_id === IPSRS_ID`. |
| `user/pengajuan/show.blade.php` | `konfirmasiFields` dari `jenis_id`. |
| `user/pengajuan/edit.blade.php` | `showOrderFields` dari `jenis_id`. |
| `admin/pengajuan/show.blade.php` | judul/accent dari `jenis_id`. |
| `admin/roles/_form.blade.php` | sudah grouping joint (done). |
| `admin/master/kategori-pengajuan.blade.php` | sudah multi (done). |

### BC & revert

- Bila rollback diperlukan: drop column `pengajuan.jenis_id` + revert `PengajuanService` call-site (recovery BC = dari jenis kategori first-linked).
- Structure `kategori_jenis` tetap berguna untuk multi-flavour master; tidak rollback bersama revert fitur.

---

## 5. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Backfill salah (`UNIQUE` constraint atau NULL) | Transaksi + `chunkById`; report jumlah lewat command `pengajuan:backfill-jenis --dry-run`. |
| Query lama yang tidak di-scan | Tambah integration test membandingkan jumlah list admin/order sebelum/sesudah per jenis. |
| `jenis_id` NULL lama | Fallback `kategori.jenis_id`; semua NOT NULL setelah validasi. |
| Data isolation error di report | Export CSV + report include `jenis_id` mentah; assert query output equal untuk data hari ini. |

---

## 6. Urutan Eksekusi

1. **Migrate** (`add_jenis_id_to_pengajuan` + backfill + index).
2. **Models** (Pengajuan fillable/casts/relasi).
3. **PengajuanService::create** + call-sites (`TicketController`, `AdministrasiUmumController`, API) — bro enabler utama; semua yang lain mengikuti.
4. Refactor daftar (list/tab/scope/dashboard) `whereHas → where('jenis_id')` per controller/service.
5. Views `$isOrder`/`konfirmasiFields`/judul `jenis_id`.
6. CSV export: tambah kolom jenis mentah (`jenis_id`) — opsional, opsional.
7. **Test** (Pest/see file tests/) — seluruh golden path: create ticket, create order, PJ, list, report export. Back up DB; run tests on copy.

**Estimasi:** 8–12 units (1–1,5 hari).

---

## 7. Keputusan Yang Masih Dibutuhkan (Sekali saja)

1. Apakah beralih ke **Opsi A** (kolom `pengajuan.jenis_id`) Anda setuju, atau Anda ingin **Opsi B** (derive dari kategori saja)?
2. Jika Opsi A: apakah `pengajuan.jenis_id` boleh NULL sementara untuk data yang tidak punya jenis di kategori (fallback zero-value → default `IT`) atau wajib NOT NULL di kolom dan default row IT?
3. Nomor prefix untuk **order** yang kategori multi-flavour: pastikan tetap `OP/RTG/MTC-…` (via `jenis_id=IPSRS`) — konfirmasi.
4. Kalau ada kategori yang di-mark `IT+IPSRS`, tampil di **kedua** create form (Tiket & Order),? Estimasi: Ya.

---

## 8. Verifikasi Checklist

- [ ] Pivot `kategori_jenis` terisi (done 19/19).
- [ ] `pengajuan.jenis_id` tidak NULL (backfill sukses).
- [ ] Admin tiket list jumlah = 29; admin order list = 10; report pengajuan total = 39.
- [ ] Nomor order tetap `OP/…`, nomor tiket tetap `T-…`.
- [ ] Detail page: order → `Detail Order Perbaikan`, tiket → `Detail Tiket`.
- [ ] Export CSV tetap sama isi + tambah `jenis_id` (opsional).
- [ ] Kategori multi-flavour muncul di kedua create form.
- [ ] Role assignment tidak berubah perilakunya.
