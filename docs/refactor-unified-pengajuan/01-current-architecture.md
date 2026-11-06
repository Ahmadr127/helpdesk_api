# 01 — Current Architecture

## 1. Stack

- Laravel 12.10.2, PHP 8.3.15
- PostgreSQL 14.5 (production dump: 17.6)
- Laravel Sanctum untuk API token
- Blade untuk web admin/user
- Flutter untuk client API
- Firebase Cloud Messaging untuk push notification
- Queue `notifications`

## 2. Dua Domain Transaksi

### 2.1 Tiket IT / SIRS

| Tabel | Fungsi |
|---|---|
| `tickets` | Header tiket |
| `ticket_photos` | Foto tiket |
| `ticket_penanggung_jawab` | PJ tiket (hanya di development, tidak ada di production) |

Alur:

```text
open → in_progress → closed / pending → confirmed
                         ↑               │
                         └── rejected ───┘
```

Catatan: kode memakai `pending` dan `closed` secara bergantian untuk "menunggu konfirmasi user". Ini sumber bug.

### 2.2 Order Perbaikan / IPSRS

| Tabel | Fungsi |
|---|---|
| `order_perbaikan` | Header order |
| `order_perbaikan_histories` | Histori status |
| `order_perbaikan_penanggung_jawab` | PJ order (hanya development) |

Alur:

```text
open → in_progress → tutup → confirmed
  │                     ↑
  └── rejected          └── user menolak → in_progress
```

### 2.3 Pengajuan gabungan TV (development only)

- `pengajuan_links` menautkan satu tiket dan satu order.
- `PengajuanSinkronService` membuat pasangan otomatis untuk kategori `TV`.
- Sinkron status dua arah dengan guard `static bool`.
- **Tidak ada di production** dan **tidak akan dipertahankan** pada arsitektur baru.

## 3. Struktur Direktori Penting

```text
app/
├── Models/                 Ticket, OrderPerbaikan, KategoriOrder, User, Role, Permission, ...
├── Services/
│   ├── Api/TicketService.php
│   ├── Api/OrderPerbaikanService.php
│   ├── Api/OrderPerbaikanPjScopeService.php
│   ├── Access/RoleService.php
│   ├── Access/PermissionService.php
│   └── PengajuanSinkronService.php
├── Http/Controllers/
│   ├── Admin/              TicketAdminController, UserManagementController, PermissionController, ...
│   ├── AdministrasiUmum/   OrderPerbaikanController
│   ├── Api/Admin/          TicketAdminController, OrderPerbaikanAdminController
│   ├── Api/User/           TicketController, OrderPerbaikanController
│   └── User/               TicketController, ...
├── Http/Requests/          FormRequest per domain
├── Http/Resources/Api/     TicketResource, OrderPerbaikanResource, UserResource
├── Observers/AuditObserver.php
├── Notifications/
├── Jobs/Notifications/SendFirebaseNotificationJob.php
└── Support/Notifications/Notify.php
routes/
├── web.php
├── api.php
└── console.php
database/
├── migrations/             60 di development, 48 di production
└── seeders/
```

## 4. Peta Dependency

| Komponen | Bergantung pada | Dampak perubahan |
|---|---|---|
| Routes web/API | Controller, middleware permission | Semua URL, menu, kontrak Flutter |
| `CheckPermission` | `users.role`, `role_permissions`, `permission_user` | Seluruh akses fitur |
| `AdminApiMiddleware` | permission `dashboard.it` | Akses API admin IT |
| `AdministrasiUmumApiMiddleware` | permission `dashboard.ipsrs` | Akses API IPSRS |
| `TicketService` | `tickets`, `ticket_photos`, `users`, `departments`, `categories`, `Notify` | Pembuatan, edit, konfirmasi, notifikasi tiket |
| `OrderPerbaikanService` | `order_perbaikan`, histories, pivot PJ, `kategori_order_user`, `Notify` | Pembuatan, edit, status, konfirmasi, notifikasi order |
| `OrderPerbaikanPjScopeService` | `kategori_order_user`, `order_perbaikan.kategori_order` | Scope daftar order admin |
| `PengajuanSinkronService` | `pengajuan_links`, `tickets`, `order_perbaikan` | TV sync (akan dideprecate) |
| `AuditObserver` | Semua model di `app/Models` | Audit logs |
| `TicketsExport` | Kolom/status tiket lama | Laporan Excel |
| `OrderPerbaikanExport` | Kolom/status order lama | Laporan Excel |
| `Notify` | `tickets`, `order_perbaikan`, device tokens | Isi dan deep link notifikasi |
| Scheduler | Status dan `closed_at` | Auto-confirm 24 jam |

## 5. RBAC Saat Ini

### 5.1 Skema

```text
users.role (varchar) ──FK──> roles.slug
roles
  └── role_permissions.role (varchar, TANPA FK) ──> permissions
users
  └── permission_user (direct grant, additive)
```

### 5.2 Cara kerja

```php
$user->hasPermission($slug)
= direct permission_user
  OR role_permissions where role = users.role
```

- Fail-closed.
- `effectivePermissionSlugs()` = union role + direct.
- `isAdminIT()` = role `admin` OR permission `dashboard.it`.
- `isAdminUmum()` = role `ipsrs` OR permission `dashboard.ipsrs`.
- Tidak ada multi-role.
- Tidak ada Policy; otorisasi tersebar di middleware dan service/controller.

### 5.3 Root cause role tidak bisa ditambah

1. Tidak ada `RoleController`.
2. Tidak ada route CRUD role.
3. Tidak ada view create/edit/delete role.
4. `StoreRoleRequest` dan `UpdateRoleRequest` dead code.
5. `RoleService` tidak memiliki `create/update/delete`.
6. `role_permissions.role` string tanpa FK.
7. Seeder menghapus dan mengisi ulang `role_permissions`.
8. UI hanya mengatur permission per role yang sudah ada.
9. `PermissionController::destroy()` tidak melindungi permission inti.
10. Pivot tidak teraudit.

## 6. Temuan Bug dan Risiko

| ID | Temuan | Dampak |
|---|---|---|
| F-01 | `pending` vs `closed` vs `tutup` tidak konsisten | Auto-confirm tiket tidak berjalan |
| F-02 | Auto-confirm order menulis `created_by = null` ke kolom NOT NULL | Proses gagal |
| F-03 | `tickets.admin_confirmation*` ditulis controller tetapi tidak ada di tabel | Data konfirmasi admin hilang |
| F-04 | Transisi status order tidak divalidasi | Status dapat melompat |
| F-05 | `TicketService::confirm()` tanpa guard status | Konfirmasi tidak sah |
| F-06 | Nomor `MAX + 1` tanpa lock | Duplikasi nomor |
| F-07 | `kategori_order.name` tidak unik, tetapi join key | PJ/link putus saat rename |
| F-08 | `foto` dan `lampiran` varchar(255) untuk array JSON | Path terpotong |
| F-09 | Tidak ada index sekunder tabel domain | Performa menurun |
| F-10 | `tickets.department_id` NOT NULL vs order nullable | Aturan tidak seragam |
| F-11 | Tiket mewajibkan string `users.department` | User valid bisa gagal membuat tiket |
| F-12 | `Category::parent_id` tidak ada di tabel | Relasi mati |
| F-13 | `Report::$fillable` memuat `status` yang tidak ada | Mass assignment diabaikan |
| F-14 | `order.view_all` tidak dipakai | Permission mati |
| F-15 | Lookup/export memakai status yang tidak ada | Filter/export salah |
| F-16 | Target TV hardcode | Kategori baru butuh ubah kode |
| F-17 | Guard sinkronisasi `static` | Tidak aman multi-process |
| F-18 | `role_permissions.role` tanpa FK | Role yatim |
| F-19 | Pivot tidak teraudit | Perubahan akses tidak terlacak |
| F-20 | Tidak ada RoleController | Role baru tidak bisa dibuat |
| F-21 | `PermissionController::destroy()` tanpa guard | Admin bisa mengunci diri |
| F-22 | `isAdminIT`/`isAdminUmum` hardcode role | Role baru tidak dikenali |
| F-23 | Notifikasi/deep link memakai ID tiket/order | ID berubah saat migrasi |

## 7. Perbedaan Production vs Development

| Fitur | Production `d8de3e9` | Development `0040479` |
|---|---|---|
| `pengajuan_links` | Tidak ada | Ada |
| Pivot PJ tiket/order | Tidak ada | Ada |
| `kategori_order_user` | Tidak ada | Ada |
| `audit_logs` | Tidak ada | Ada |
| `order_perbaikan.closed_at` | Tidak ada | Ada |
| `order_perbaikan.gedung` | Tidak ada | Ada |
| `unit_penerima` pada order | Ada, NOT NULL | Sudah di-drop |
| `kode_inventaris`, `nama_barang` | Ada | Sudah di-drop |
| `nama_penanggung_jawab` | Ada (string) | Sudah di-drop |
| Jumlah permission | 16 | 18 (`order.view_all`, `audit`) |
| Kategori TV | Tidak ada | Ada |

Baseline implementasi: **production**. Fitur development yang masih diperlukan ditambahkan melalui task baru, bukan dengan merge buta.

## 8. Cakupan Audit

Sudah:

- Semua service inti, model inti, controller inti, route web/API, migration, seeder RBAC, middleware, observer, notifikasi, scheduler.
- Skema production dan development.

Belum baris per baris:

- Seluruh 98 Blade.
- Kontrak Flutter di `E:\kerjaan\Programming\flutter\helpdesk`.
- Postman collection.

Task audit lanjutan ada di `06-implementation-tasks.md` (T-APP-000, T-API-000).
