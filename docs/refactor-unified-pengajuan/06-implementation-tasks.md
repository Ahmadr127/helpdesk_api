# 06 — Implementation Tasks

Status: **DRAFT — seluruh task NOT_STARTED**
Baseline: production `d8de3e9`
Aturan: satu task = satu perubahan yang dapat diuji. Status `COMPLETED` hanya setelah ada bukti test; `VERIFIED` setelah reviewer/PM menyetujui.

Legenda status:

| Status | Arti |
|---|---|
| `NOT_STARTED` | Belum dikerjakan |
| `IN_PROGRESS` | Sedang dikerjakan |
| `BLOCKED` | Menunggu keputusan/dependency |
| `COMPLETED` | Kode selesai + test lulus |
| `VERIFIED` | Sudah diverifikasi di staging/production |

---

## A. Fondasi

### T-BASE-001 — Tetapkan baseline dan tag

- **Status:** COMPLETED
- **Depends:** —
- **Files:** dokumentasi saja; `git tag`
- **Tujuan:** Menetapkan `d8de3e9` sebagai production baseline dan `0040479` sebagai referensi development.
- **Langkah:**
  1. Buat tag `prod-baseline-d8de3e9`.
  2. Catat working tree lokal (README, AdminController, DATABASE-DESIGN.md).
  3. Jangan reset/checkout/rebase.
- **Acceptance:** Tag ada; dokumen baseline mencatat kedua hash.
- **Test:** `git show-ref --tags`, cek di dokumen.

### T-BASE-002 — Restore production dump ke staging/local

- **Status:** COMPLETED
- **Depends:** T-BASE-001
- **Files:** `db_helpdesk.sql`, script restore
- **Tujuan:** Menyediakan database uji yang identik dengan production.
- **Langkah:**
  1. Preprocess dump PG17.6: hapus `\restrict`/`\unrestrict`, `SET transaction_timeout`, atau restore dengan PostgreSQL 17.
  2. Buat database baru `db_helpdesk_prod_copy` (jangan timpa `db_helpdesk`).
  3. Restore; verifikasi 33 tabel dan row count.
  4. Arahkan `.env.testing`/staging ke database ini.
- **Acceptance:** Semua tabel dan row count production cocok.
- **Test:** Query count di `04-data-migration-plan.md` §2.

### T-BASE-003 — Siapkan environment dan test harness

- **Status:** COMPLETED
- **Depends:** T-BASE-002
- **Files:** `phpunit.xml`, `.env.testing`, `config/database.php`
- **Tujuan:** Test memakai database terpisah; tidak menyentuh production.
- **Langkah:**
  1. Buat database `db_helpdesk_test`.
  2. Pastikan `RefreshDatabase`/transaction sesuai.
  3. Siapkan storage fake untuk upload.
- **Acceptance:** Test suite lama berjalan di DB test.
- **Test:** `php artisan test`.

### T-BASE-004 — Selesaikan audit Blade, Flutter, Postman

- **Status:** IN_PROGRESS (belum selesai)
- **Depends:** T-BASE-001
- **Files:** `resources/views/**`, Flutter `lib/**`, `postman/**`
- **Tujuan:** Menutup gap audit yang belum diverifikasi baris per baris.
- **Acceptance:** Daftar field/endpoint yang dipakai klien tercatat di `01-current-architecture.md`.
- **Test:** Grep referensi `ticket`, `order`, `status`, `nomor`, `deep_link`.

---

## B. Database

### T-DB-001 — Tabel jenis, status, kategori

- **Status:** COMPLETED
- **Depends:** T-BASE-002
- **Files:** migration baru `create_jenis_pengajuan_table`, `create_kategori_pengajuan_table`, `create_status_pengajuan_table`
- **Tujuan:** Master jenis dan kategori dinamis.
- **Langkah:**
  1. Buat `jenis_pengajuan`, `kategori_pengajuan`.
  2. Seed `IT`, `IPSRS`.
  3. Tambah `is_system`/`deleted_at` pada roles/permissions.
- **Acceptance:** Kategori baru dapat ditambah tanpa migration.
- **Test:** Feature test CRUD kategori.

### T-DB-002 — Tabel pengajuan dan pendukung

- **Status:** COMPLETED
- **Depends:** T-DB-001
- **Files:** migration baru
- **Tujuan:** Tabel utama dan anak.
- **Langkah:**
  1. `pengajuan`, `pengajuan_order_detail`.
  2. `pengajuan_histori`, `pengajuan_pesan`, `pengajuan_lampiran`.
  3. `pengajuan_penugasan`.
- **Acceptance:** FK/index/unique sesuai `02`.
- **Test:** Migration test + rollback di DB test.

### T-DB-003 — RBAC schema

- **Status:** COMPLETED
- **Depends:** T-DB-001
- **Files:** migration `add_role_id_to_role_permissions`, `create_role_kategori_table`
- **Tujuan:** Role CRUD dan assignment kategori.
- **Langkah:**
  1. Tambah `role_id` ke `role_permissions`; backfill dari slug; unique(role_id, permission_id).
  2. Buat `role_kategori`.
  3. Pertahankan kolom `role` sampai cutover.
- **Acceptance:** Tidak ada role_permission yatim.
- **Test:** Bandingkan count lama vs baru.

### T-DB-004 — Sequence, legacy map, migration log

- **Status:** COMPLETED
- **Depends:** T-DB-002
- **Files:** migration
- **Tujuan:** Penomoran dan jejak migrasi.
- **Langkah:** `document_sequences`, `legacy_map`, `migration_runs`, `migration_orphans`.
- **Acceptance:** Unique constraint mencegah duplikasi.
- **Test:** Insert ganda ditolak.

### T-DB-005 — Index dan constraint

- **Status:** COMPLETED
- **Depends:** T-DB-002, T-DB-003
- **Files:** migration index
- **Tujuan:** Performa list/filter/dashboard.
- **Index minimum:** `pengajuan(pemohon_id,status,created_at)`, `pengajuan(kategori_id,status)`, `pengajuan(nomor)`, `histori(pengajuan_id,created_at)`, `pesan(pengajuan_id,created_at)`, `lampiran(pengajuan_id)`, `role_kategori(role_id)`, `role_kategori(kategori_id)`, `penugasan(pengajuan_id,user_id)`.
- **Acceptance:** EXPLAIN tidak full scan untuk query utama.
- **Test:** Query plan + dataset seed.

### T-DB-006 — Arsip tabel lama

- **Status:** BLOCKED (menunggu cutover)
- **Depends:** T-CUT-003
- **Files:** migration rename
- **Tujuan:** Menyimpan `tickets`/`order_perbaikan` sebagai `*_legacy`.
- **Acceptance:** Tidak ada `DROP`; aplikasi tidak membaca tabel lama.
- **Test:** Smoke test setelah rename.

---

## C. RBAC

### T-RBAC-001 — Role CRUD

- **Status:** COMPLETED
- **Depends:** T-DB-003
- **Files:** `RoleController`, `RoleService`, `StoreRoleRequest`, `UpdateRoleRequest`, view `admin/roles/*`, routes
- **Tujuan:** Role baru dapat dibuat/diubah/dihapus dari UI.
- **Langkah:**
  1. `index/create/store/edit/update/destroy`.
  2. Sync permissions dan kategori.
  3. Soft delete; blokir jika masih dipakai user.
- **Acceptance:** Buat role baru, assign permission/kategori, user memakai role baru, akses sesuai.
- **Test:** Feature CRUD + authorization.

### T-RBAC-002 — Permission CRUD dan guard

- **Status:** COMPLETED
- **Depends:** T-DB-003
- **Files:** `PermissionController`, `PermissionService`, view
- **Tujuan:** Permission dapat dikelola tanpa lockout.
- **Langkah:**
  1. Tambah update; guard `is_system`.
  2. Cegah hapus permission inti.
  3. Audit perubahan.
- **Acceptance:** Permission inti tidak bisa dihapus; permission baru langsung terpakai.
- **Test:** Feature test delete guard.

### T-RBAC-003 — Assignment role-kategori

- **Status:** COMPLETED
- **Depends:** T-RBAC-001, T-DB-001
- **Files:** `RoleKategoriController`/bagian RoleController, view kategori
- **Tujuan:** Satu role banyak kategori; satu kategori banyak role.
- **Acceptance:** Perubahan assignment langsung mengubah scope dan penerima notifikasi.
- **Test:** User role A melihat kategori X; setelah dilepas, tidak melihat.

### T-RBAC-004 — User role dan direct permission

- **Status:** COMPLETED
- **Depends:** T-RBAC-001
- **Files:** `UserManagementController`, `PermissionController`
- **Tujuan:** Tetap satu role/user; direct permission additive.
- **Acceptance:** Perubahan role mencabut permission lama (kecuali direct); direct tetap.
- **Test:** Matrix user/role/permission.

### T-RBAC-005 — Hapus hardcode akses

- **Status:** COMPLETED
- **Depends:** T-RBAC-003
- **Files:** `User.php`, `routes/web.php`, middleware API, `OrderPerbaikanPjScopeService`
- **Tujuan:** Scope hanya dari role_kategori; `isAdminIT/Umum` tidak menentukan data.
- **Acceptance:** Tidak ada query data berdasarkan nama role/dashboard permission.
- **Test:** Grep hardcode; feature scope.

### T-RBAC-006 — Seed RBAC production

- **Status:** COMPLETED
- **Depends:** T-RBAC-003
- **Files:** seeder baru
- **Tujuan:** admin → 5 kategori IT; ipsrs → 14 kategori IPSRS; user → tanpa kategori.
- **Acceptance:** Count role_kategori sesuai; tidak ada role user sebagai PJ.
- **Test:** Query count + akses.

---

## D. Migrasi Data

### T-MIG-001 — Preflight dan orphan report

- **Status:** COMPLETED
- **Depends:** T-BASE-002, T-DB-002
- **Files:** command `migration:preflight`
- **Acceptance:** Laporan count/orphan/duplikasi tersimpan.
- **Test:** Jalankan di prod copy; angka cocok.

### T-MIG-002 — Migrasi kategori

- **Status:** COMPLETED
- **Depends:** T-MIG-001, T-DB-001
- **Files:** command `migration:categories`
- **Langkah:** `categories`→IT, `kategori_order`→IPSRS; mapping table.
- **Acceptance:** 5 + 14 kategori; tidak ada duplikat per jenis.
- **Test:** Count + sample.

### T-MIG-003 — Migrasi tiket

- **Status:** COMPLETED
- **Depends:** T-MIG-002, T-DB-002
- **Files:** command `migration:tickets`
- **Acceptance:** 16/16 tiket → pengajuan; nomor sama; legacy_map lengkap.
- **Test:** Row count, sample 5, unique nomor.

### T-MIG-004 — Migrasi order

- **Status:** COMPLETED
- **Depends:** T-MIG-002, T-DB-002
- **Files:** command `migration:orders`
- **Acceptance:** 7/7 order; detail IPSRS terisi; status benar.
- **Test:** Sample 5; field unit_penerima/kode/nama barang.

### T-MIG-005 — Histori dan pesan

- **Status:** COMPLETED
- **Depends:** T-MIG-003, T-MIG-004
- **Files:** command
- **Acceptance:** 17 histori order pindah; JSON responses/replies menjadi pesan/histori; tidak ada duplikasi.
- **Test:** Count + sampel JSON.

### T-MIG-006 — Lampiran

- **Status:** COMPLETED
- **Depends:** T-MIG-003, T-MIG-004
- **Files:** command
- **Acceptance:** Semua path terhitung; file missing dilaporkan.
- **Test:** Count per sumber vs target; cek storage.

### T-MIG-007 — Penugasan dan role-kategori

- **Status:** COMPLETED
- **Depends:** T-MIG-004, T-RBAC-006
- **Files:** command
- **Acceptance:** `nama_penanggung_jawab` resolve unik; ambigu → orphan; role_kategori sesuai seed.
- **Test:** Count + akses.

### T-MIG-008 — TV development

- **Status:** BLOCKED (production tidak punya TV)
- **Depends:** T-MIG-003/004
- **Files:** command
- **Acceptance:** Rehearsal dev: satu pengajuan, order diarsipkan, tidak ada duplikat.
- **Test:** Data dev 1 link.

### T-MIG-009 — Validasi dan laporan

- **Status:** COMPLETED
- **Depends:** T-MIG-005..008
- **Files:** command `migration:validate`
- **Acceptance:** Laporan memenuhi semua kriteria §13 `04`.
- **Test:** Dry-run + failure injection.

### T-MIG-010 — Idempotensi dan rollback

- **Status:** COMPLETED
- **Depends:** T-MIG-009
- **Files:** command/checkpoint
- **Acceptance:** Jalankan ulang tidak menggandakan; rollback ke kode lama tetap berfungsi.
- **Test:** Run 2x; bandingkan count.

---

## E. Aplikasi

### T-APP-001 — Model dan relasi baru

- **Status:** COMPLETED
- **Depends:** T-DB-002
- **Files:** `app/Models/Pengajuan*.php`, `KategoriPengajuan`, `JenisPengajuan`
- **Acceptance:** Relasi, cast, fillable sesuai `05` §3.
- **Test:** Unit relation test.

### T-APP-002 — Number dan scope service

- **Status:** COMPLETED
- **Depends:** T-APP-001, T-DB-004
- **Files:** `PengajuanNumberService`, `PengajuanScopeService`
- **Acceptance:** Nomor unik di concurrent test; scope sesuai role_kategori.
- **Test:** Parallel create; feature scope.

### T-APP-003 — Create/update/delete pengajuan

- **Status:** COMPLETED
- **Depends:** T-APP-002
- **Files:** `PengajuanService`, requests
- **Acceptance:** IT/IPSRS create; owner edit/delete hanya open; histori tercatat.
- **Test:** Feature test kedua jenis.

### T-APP-004 — Workflow

- **Status:** COMPLETED
- **Depends:** T-APP-003
- **Files:** `PengajuanWorkflowService`
- **Acceptance:** Semua transisi lama dipertahankan; auto-confirm berjalan; reject user kembali in_progress.
- **Test:** State transition matrix.

### T-APP-005 — Attachment service

- **Status:** COMPLETED
- **Depends:** T-APP-003
- **Files:** `PengajuanAttachmentService`
- **Acceptance:** Max 3, ukuran sesuai endpoint, metadata, soft delete tidak hapus file.
- **Test:** Upload/delete.

### T-APP-006 — Notification service

- **Status:** NOT_STARTED
- **Depends:** T-APP-004, T-RBAC-003
- **Files:** `PengajuanNotificationService`, notification classes
- **Acceptance:** Penerima role_kategori; payload lama; deep link.
- **Test:** Notification::fake; FCM job dispatched.

### T-APP-007 — Web controller dan route

- **Status:** COMPLETED
- **Depends:** T-APP-003..006
- **Files:** controller, `routes/web.php`
- **Acceptance:** URL/route name lama tetap; halaman utama berfungsi.
- **Test:** Feature web kedua jenis.

### T-APP-008 — Blade views

- **Status:** COMPLETED
- **Depends:** T-APP-007
- **Files:** `resources/views/**`
- **Acceptance:** Tampilan/istilah lama; field khusus IPSRS; role/kategori CRUD.
- **Test:** Smoke + snapshot text.

### T-APP-009 — Dashboard/filter/statistik

- **Status:** NOT_STARTED
- **Depends:** T-APP-007
- **Files:** dashboard controllers/services
- **Acceptance:** Angka dan filter sama; scope benar.
- **Test:** Compare old vs new di staging.

### T-APP-010 — Export/laporan

- **Status:** NOT_STARTED
- **Depends:** T-APP-009
- **Files:** `app/Exports/*`
- **Acceptance:** Kolom/format lama; status mapping benar.
- **Test:** Buka file hasil export.

### T-APP-011 — Scheduler/auto-confirm

- **Status:** COMPLETED
- **Depends:** T-APP-004
- **Files:** `AutoConfirmStaleSubmissions`, `routes/console.php`
- **Acceptance:** 24 jam; system actor; idempotent; notifikasi.
- **Test:** Freeze time, jalankan 2x.

### T-APP-012 — Audit

- **Status:** COMPLETED
- **Depends:** T-APP-003..011
- **Files:** `AuditObserver`, `AuditService`
- **Acceptance:** CRUD + pivot + workflow tercatat.
- **Test:** Audit log entries.

### T-APP-013 — Admin role/kategori UI

- **Status:** COMPLETED
- **Depends:** T-RBAC-001..003
- **Files:** view/routes
- **Acceptance:** CRUD role, permission, kategori, assignment.
- **Test:** Feature.

### T-APP-014 — Legacy resolver

- **Status:** COMPLETED
- **Depends:** T-MIG-003/004
- **Files:** `LegacyPengajuanResolver`, compatibility resource
- **Acceptance:** ID lama dan baru dapat dibuka.
- **Test:** Old deep link + old API id.

---

## F. API dan Flutter

### T-API-001 — Kontrak v1

- **Status:** NOT_STARTED
- **Depends:** T-MIG-009
- **Files:** API resources, tests
- **Acceptance:** Field/status/ID sama untuk data lama; `can_edit` benar.
- **Test:** Contract test terhadap fixture lama.

### T-API-002 — Endpoint v1 adapter

- **Status:** NOT_STARTED
- **Depends:** T-APP-007
- **Files:** `routes/api.php`, controllers
- **Acceptance:** Semua endpoint lama berfungsi.
- **Test:** Feature API matrix.

### T-API-003 — FCM dan deep link

- **Status:** NOT_STARTED
- **Depends:** T-APP-006
- **Files:** notification/job
- **Acceptance:** Payload lama + resolver.
- **Test:** Job payload assertion.

### T-API-004 — API v2 (opsional)

- **Status:** BLOCKED (menunggu kesiapan Flutter)
- **Depends:** T-API-001
- **Acceptance:** Endpoint baru terdokumentasi; v1 tetap.
- **Test:** Contract v2.

---

## G. Testing

### T-TEST-001 — Unit test service

- **Status:** COMPLETED
- **Depends:** T-APP-002..006
- **Coverage:** number, scope, workflow, attachment, notification.

### T-TEST-002 — Feature web

- **Status:** NOT_STARTED
- **Depends:** T-APP-007/008
- **Coverage:** create IT/IPSRS, edit/delete, confirm, reject, role CRUD.

### T-TEST-003 — API contract

- **Status:** NOT_STARTED
- **Depends:** T-API-002
- **Coverage:** semua endpoint v1 + payload.

### T-TEST-004 — Migration

- **Status:** NOT_STARTED
- **Depends:** T-MIG-009
- **Coverage:** count, orphan, idempotent, rollback.

### T-TEST-005 — Regression dan race

- **Status:** NOT_STARTED
- **Depends:** semua
- **Coverage:** concurrent numbering, repeated auto-confirm, double submit, queue retry.

---

## H. Cutover

### T-CUT-001 — Rehearsal staging

- **Status:** BLOCKED
- **Depends:** T-TEST-004
- **Acceptance:** Migrasi + aplikasi baru berjalan di prod copy.

### T-CUT-002 — Persiapan production

- **Status:** BLOCKED
- **Depends:** T-CUT-001
- **Acceptance:** Maintenance window, freeze, rollback plan.

### T-CUT-003 — Cutover

- **Status:** BLOCKED
- **Depends:** T-CUT-002
- **Acceptance:** Aplikasi baru melayani production; tabel lama read-only.

### T-CUT-004 — Validasi production

- **Status:** BLOCKED
- **Depends:** T-CUT-003
- **Acceptance:** Semua kriteria `04` §13 terpenuhi.

### T-CUT-005 — Arsip dan rollback

- **Status:** BLOCKED
- **Depends:** T-CUT-004
- **Acceptance:** Tabel `*_legacy` dibuat; prosedur rollback diuji.

---

## Ringkasan Urutan

```text
T-BASE-001/002/003/004
  → T-DB-001..005
  → T-RBAC-001..006
  → T-MIG-001..010
  → T-APP-001..014
  → T-API-001..003
  → T-TEST-001..005
  → T-CUT-001..005
```

Task yang memblokir saat ini:

- T-BASE-004 (audit klien belum selesai)
- T-DB-006/T-CUT-* (menunggu implementasi)
- T-MIG-008 (production tidak punya TV)
- T-API-004 (menunggu keputusan Flutter)
