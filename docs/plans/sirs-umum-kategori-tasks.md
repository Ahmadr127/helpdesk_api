# Tasks: Generalisasi Kategori — SIRS & UMUM

> **Status: hampir selesai.** Fase 1 & 2 (scope refactor) sudah dieksekusi.
> `jenis_pengajuan.name` database sekarang `SIRS` (code `IT`) dan `UMUM` (code `IPSRS`).

---

## Fase 1 — Fondasi (SELESAI)

- [x] Pivot `kategori_jenis` + backfill `kategori_pengajuan.jenis_id`.
- [x] `KategoriPengajuan`: `jenisList()` pivot, relasi BC `jenis()`, accessor `jenisDisplay/jenisCodes/jenisNames`, method `syncJenis()`, scope `scopeSirs()` / `scopeUmum()`.
- [x] `Pengajuan`: scope `scopeSirs()` / `scopeUmum()`.
- [x] `UnifiedPengajuanSeeder`: seed labels SIRS/UMUM (+ update present DB labels).
- [x] Admin CRUD master kategori: checkbox multi-jenis (report-list baru).
- [x] CSV report/fitur: verifikasi join column.

## Fase 2 — Refactor semua site (SELESAI)

- [x] `PengajuanDashboardService`: baseQuery('sirs'|'umum'), raw statistics IPSRS → join pivot whereExists code != IT.
- [x] `PengajuanNotificationService` / `PengajuanAttachmentService`: tidak berubah (BC tetap valid).
- [x] `PengajuanService`: tidak berubah (BC `jenis()` dipertahankan).
- [x] `app/Providers/AppServiceProvider.php`: popup reminder pending close → `sirs()` / `umum()`.
- [x] `app/Http/Controllers/User/UserController.php`: `sirs()` / `umum()`.
- [x] `app/Http/Controllers/User/TicketController.php`: `itQuery()` → `sirs()`. Categories dropdown: `sirs()`.
- [x] `app/Http/Controllers/User/AdministrasiUmumController.php`: `ipsrsQuery()` → `umum()` + dashboard order query + `$kategoriOrders`/`$kategoris` → `umum()` include legacy single-jenis fallback (`umum()->first()`).
- [x] `app/Http/Controllers/AdministrasiUmum/OrderPerbaikanController.php`: `ipsrsQuery()` → `umum()`.
- [x] `app/Http/Controllers/Admin/TicketController.php`, `TicketAdminController.php`, `AdminController.php`: `sirs()`.
- [x] `app/Http/Controllers/Api/V1/PengajuanController.php`: baseQuery → join `kategori.jenisList` where code=$jenis; guardJenis → membership joint.
- [x] `app/Http/Controllers/Api/V1/AdminPengajuanController.php`: baseQuery & ensureVisible → joint membership codes, + relations() tetap.
- [x] Seluruh `whereHas('kategori.jenis', ...)` tersisa = 0.
- [x] `routes/web.php`: route `user.pengajuan.show` eksplisit; `admin/ pengajuan/{pengajuan}` dipisah ke route name `admin.pengajuan.show`.
- [x] `config/admin_menu.php`: label menu "Report Umum" (prefix route `admin.reports.*`) & "Report SIRS" (prefix route `admin.report-sirs.*`).
- [x] `php artisan view:cache` berhasil.

## Fase 3 — Verifikasi database (SELESAI)

- [x] `jenis_pengajuan` label: `IT→SIRS`, `IPSRS→UMUM`.
- [x] `Pengajuan::sirs()->count() == 29`, `umum()->count() == 11`, total `40`.
- [x] Multi-flavour: kategori dengan jenis [IT, IPSRS] akan ikut di **kedua** scope & dropdown (by design).

## Fase 4 — Clean-up (opsional, setelah stabil / belum diputuskan)

- [ ] Hapus fungsi BC (`jenis()` LEGACY & column `kategori_pengajuan.jenis_id`), setelah memastikan seluruh callsite memakai `jenisList()`.
- [ ] `Pengajuan::create` klopf BC → jenis_id first-linked; jika ingin tight scope, gunakan `jenis` kolom di `pengajuan` (opsi yang user tolak sebelumnya).
- [ ] Header judul halaman admin form order (seperti "Administrasi Order Perbaikan") di-rebrand sesuai SIRS/UMUM — menunggu instruksi eksplisit user.

---

## Instruksi eksekusi / membaca ulang

- Dokumentasi utama: `docs/plans/sirs-umum-kategori.md`
- Remembering: role kategori pada `roles/_form.blade.php` memakai joint display (SIRS / IPSRS / SIRS & IPSRS) — perlu dicek UI roles setelah label rename (kemungkinan menampilkan label lokasi grup joint).
