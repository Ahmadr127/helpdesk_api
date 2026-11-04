# Kategori Order

Master data untuk mengklasifikasikan **Order Perbaikan** (order barang/perbaikan IPSRS) dan menentukan **Petugas Penanggung Jawab (PJ)** secara otomatis.

## Konsep

- Setiap `KategoriOrder` memiliki `name`, `code`, `status`.
- Relasi many-to-many ke `User` (tabel `kategori_order_user`): user yang ditugaskan sebagai **PJ** untuk kategori tersebut.
- (Sebelumnya ada relasi ke `Department` via `kategori_order_department`, tetapi sudah dihapus — department tidak membatasi pilihan kategori.)

## Fungsi

1. **Klasifikasi order**: field `kategori_order` pada `order_perbaikan` menyimpan nama kategori.
2. **Penugasan PJ otomatis**: saat order dibuat, `OrderPerbaikanService::defaultPjIdsFor($kategori)` mencari `KategoriOrder` by name (status aktif) dan mengambil user yang ditugaskan → di-sync ke `penanggungJawab` order.
3. **Notifikasi**: order dengan PJ → notifikasi ke PJ tersebut. Order tanpa PJ (kategori kosong/tidak ada assignment) → fallback ke **Admin Umum**.
4. **Scoping data admin**: admin hanya melihat order dari kategori yang di-assign ke dia sebagai PJ. User tanpa assignment melihat order yang kategorinya tidak punya PJ (fallback).

## Validasi

- `kategori_order` wajib (`required`) pada create/update order.
- Hanya kategori aktif yang valid (`KategoriOrderAllowed` rule).

## Admin UI

- `admin.master.kategori-order.index`: CRUD kategori + penugasan user (PJ).
- Tombol **"Assign User ke Semua Kategori"**: menugaskan satu user ke semua kategori aktif (syncWithoutDetaching).

## Scoping (Partial Service)

`OrderPerbaikanPjScopeService` (extends `OrderPerbaikanService`) menyediakan:
- `assignedCategoryNames(User)`: daftar kategori yang di-assign ke user.
- `listForAdmin()`: filter `whereIn('kategori_order', names)` atau fallback `whereNotExists` (no PJ).
- `statistics()`: stat berdasarkan kategori yang sama.

Dipakai oleh web controller dan API admin controller.

## Dashboard IPSRS

`DashboardController` juga menerapkan scoping PJ pada statistik, tren, dan recent orders per user login (cache key menyertakan user id).

## File terkait

- Model: `app/Models/KategoriOrder.php`
- Service: `app/Services/Api/OrderPerbaikanService.php`, `app/Services/Api/OrderPerbaikanPjScopeService.php`
- Controller: `app/Http/Controllers/Admin/KategoriOrderController.php`, `app/Http/Controllers/AdministrasiUmum/OrderPerbaikanController.php`, `app/Http/Controllers/AdministrasiUmum/DashboardController.php`
- API: `app/Http/Controllers/Api/Admin/OrderPerbaikanAdminController.php`
- View: `resources/views/admin/master/kategori-order.blade.php`
- Migration: `database/migrations/2026_11_03_000001_create_kategori_order_table.php`, `2026_11_03_000003_create_kategori_order_user_table.php`, `2026_11_05_000001_drop_kategori_order_department_table.php`