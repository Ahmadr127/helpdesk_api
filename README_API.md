# Helpdesk RS Azra - API & Alur Sistem

> Laravel 12 + Livewire 3 + Sanctum 4 + PostgreSQL `db_helpdesk` (`127.0.0.1:5432` / `postgres:server233`)
> DB sudah `migrate:fresh --seed` (fix `UnitProsesSeeder.php:12` `firstOrCreate` cegah duplicate `LOGF`). Testing DB `db_helpdesk_testing` (`phpunit.xml:25`).

---

## 1. Alur Sistem: Keluhan User Sampai Selesai

### 1.1 Login & Validasi Awal
1. User buka `/` → redirect `login` (`routes/web.php:37`).
2. Login `app/Http/Controllers/Auth/LoginController.php:20` validasi `email+password`, cek `users.status===0` → tolak `Akun dinonaktifkan`, lalu `Auth::attempt`.
3. `redirectBasedOnRole` → permission `admin.dashboard` → `admin.dashboard`, `ipsrs.dashboard` → `admin.ipsrs.dashboard`, else `user.dashboard`.
4. Jika `users.department` kosong → `TicketController.php:44` / `TicketService.php:20` tolak buat tiket: *“Mohon lengkapi data departemen”*.

### 1.2 User Buat Keluhan (2 Jalur Berbeda Unit Proses)

**A. Ticket IT – SIRS** (`SIRS` = Sistem Informasi Rumah Sakit)
- Route Web `user/ticket/create` (`routes/web.php:90`) & API `POST /api/tickets` (`routes/api.php:18`, `User/TicketController.php:40`).
- Input wajib `app/Http/Requests/Api/Ticket/StoreTicketRequest.php:10`: `category_id` harus `exists:categories,id` + closure cek `category.unitProses.code === 'SIRS'` (kategori SIRS di seeder: id `2 Printer`, `3 CCTV`, `4 Software`, `5 Hardware` `categories` join `unit_proses` `SIRS` id 3), `location_id` exists `locations.id` (UGD id1/BPJS id2…), `description` string, `priority low/medium/high`, `photo` image 5MB opsional.
- Logic `TicketService.php:20` transaksi: ambil `Department` user (`IT` id2 / `GENERAL` id1), `Location`+`Building`, generate `ticket_number T-ddmm-xxx` per hari (`T-2808-001` cari `T-date-%` order desc), create `tickets` (`category`, `department`, `building`, `location` string + FK), simpan foto `ticket-photos` disk `public` → `TicketPhoto type=initial`. Status awal `open`.

**B. Order Perbaikan Sarana – IPSRS** (bukan SIRS)
- Route `user/administrasi-umum/order-perbaikan/create` (`AdministrasiUmumController.php:328`) & API `POST /api/order-perbaikan` (`User/OrderPerbaikanController.php:40`).
- Validasi `OrderPerbaikan/StoreOrderPerbaikanRequest.php:10`: `unit_proses_code` exists `unit_proses,code` + `!=SIRS` (valid `SRNS` Sarana, `RTG` IPSRS, `LOGF`), `lokasi` exists, `prioritas RENDAH/SEDANG/TINGGI/URGENT`, `foto` 10MB. (`jenis_barang` dihapus dari alur order perbaikan — kolom DB di-drop, tidak ada di form web/API/mobile.)
- `OrderPerbaikanService.php:20` generate `nomor OP/RTG/MTC-YYYYMMDDxxx` (`OP/RTG/MTC-20250830001`) cari `withTrashed` per hari, simpan `order_perbaikan` (`SoftDeletes`) + `history` `status=open`, `foto` `order-photos`, `status=open`.

#### 1.2 C. Form Input Order Perbaikan (Saat Ini)

**Web** (`user/administrasi-umum/order-perbaikan/create` — `AdministrasiUmumController.php:376-429`, view `resources/views/user/administrasi-umum/order-perbaikan/create.blade.php`):

| Field | Wajib | Tipe Kontrol | Isi / Sumber | Catatan |
|------|------|--------------|--------------|---------|
| `tanggal` | ya (hidden) | hidden | `now()` | otomatis, tidak diisi user |
| `unit_proses_code` | ya (hidden) | hidden | `users.department` user login | otomatis, `!= SIRS` |
| `unit_proses_name` | ya (hidden) | hidden | nama department user | otomatis |
| `prioritas` | ya | select | `RENDAH` / `SEDANG` / `TINGGI/URGENT` | |
| `nama_barang` | ya | text | nama barang/peralatan | |
| `kategori_order` | tidak | select | master `kategori_order` aktif (`KategoriOrderController`) | nilai tersimpan = `kategori_order.name`; validasi web masih `nullable|string` (integer FK belum dipakai) |
| `lokasi` | ya | search-select / terkunci | master `locations` aktif; **terkunci otomatis dari departemen** bila departemen punya lokasi | tersimpan `locations.id`, autocomplete di JS |
| `keluhan` | ya | textarea | deskripsi kerusakan | |
| `foto` | tidak | file | PNG/JPG/GIF ≤ 10MB | `order-photos` disk `public` |
| `kode_inventaris` | tidak | - | tidak ada di form web | validasi web `nullable`; service default `'-'` |

**API** (`POST /api/order-perbaikan` / `PUT /api/order-perbaikan/{id}` — `OrderPerbaikan/StoreOrderPerbaikanRequest.php:10`, `UpdateOrderPerbaikanRequest.php`):

| Field | Wajib | Rule |
|------|------|------|
| `unit_proses_code` | ya | `exists:unit_proses,code` + closure tolak `SIRS` |
| `kode_inventaris` | ya (API) | `string` (berbeda dengan web yang nullable) |
| `nama_barang` | ya | `string` |
| `kategori_order` | tidak | `nullable|string|exists:kategori_order,name` |
| `lokasi` | ya | `exists:locations,id` |
| `keluhan` | ya | `string` |
| `prioritas` | ya | `in:RENDAH,SEDANG,TINGGI/URGENT` |
| `tanggal` | ya | `date` |
| `foto` | tidak | `image|mimes:jpeg,png,jpg,gif|max:10240` |

Sumber pilihan: `GET /api/lookup/unit-proses?exclude_sirs=1`, `GET /api/lookup/locations`, `GET /api/lookup/kategori-order`; CRUD kategori order: `GET|POST|PUT|DELETE /api/admin/master/kategori_order[/{id}]` (+ `bulk-action`). List/filter API mendukung `kategori_order` (`GET /api/order-perbaikan?kategori_order=Perbaikan`).

### 1.3 Proses Admin

**Ticket → Admin IT** (`routes/web.php:148`, `routes/api.php:18` `AdminApiMiddleware`)
- Dashboard `TicketAdminController.php:17` hitung `total/open/in_progress/closed`. Query `with(user,category)` filter `search/status/priority/date`, orderBy status+priority.
- `POST /{ticket}/respond` (`AdminRespondRequest.php`) `notes+status:in_progress/closed (+foto)` → append `admin_responses` JSON, update `in_progress_at/closed_at`, create `TicketPhoto admin_response`, notify user `TicketRespondedNotification`.
- `PUT /{ticket}` (`action=reply` hanya tambah notes tanpa ubah status).

**Order → Admin Administrasi** (`OrderPerbaikanController.php:34`, `Admin/OrderPerbaikanAdminController.php:10` – `AdministrasiUmumApiMiddleware`)
- Statistik `open/in_progress/confirmed/rejected` + `rendah/sedang/tinggi`.
- `PUT /{order}/status` (`UpdateStatusRequest`) `status:in_progress/confirmed/rejected`, `follow_up` wajib, `nama_penanggung_jawab` wajib jika `open→in_progress`, `prioritas` opsional. Update `order_perbaikan` + `history` (`keterangan` + prioritas).
- Tombol `POST /start` (open→in_progress), `POST /confirm` (`confirmed`), `POST /reject` (`rejected`), `POST /complete` – semua tulis `history` + `updated_by`.

### 1.4 Konfirmasi User (Penentu Selesai / Loop)

User buka `tickets/{id}/show` atau `order-perbaikan/{id}/show` lihat `follow_up`+foto admin.

- **Confirm** `POST /tickets/{id}/confirm` (`ConfirmTicketRequest.php:10` `confirmation_notes+action:confirm`) → `TicketService.php:20` `status=confirmed`, `user_confirmation=true`, `user_confirmed_at=now`, `user_replies` JSON append, notify admin `confirmed`. Order `POST /.../confirm` → `status=confirmed`.
- **Reject** `action=reject` → Ticket `status=in_progress`, `rejection_count++`, `last_rejection_at=now`, `user_replies` + foto `user_rejection`, notify admin `rejected`. Loop kembali ke `in_progress` untuk dikerjakan ulang. Order `status=rejected`.
- Aturan: hanya `open` bisa `edit/delete` owner (`TicketController.php:195`, `OrderPerbaikanService.php:20`). `in_progress/closed` terkunci.

### 1.5 Selesai & Arsip

- Ticket `confirmed` masuk `GET /admin/tickets/history` (`TicketAdminController.php:140`) filter `date_from/to`, paginate `user_confirmed_at desc`, export `TicketsExport` (`exportHistory`). Order `confirmed/rejected` masuk list `admin/order-perbaikan/confirmed/rejected` (legacy URL `administrasi-umum/*` 301 → `admin/*`) bisa export `OrderPerbaikanExport`.
- **Feedback** `POST /feedback` (`StoreFeedbackRequest` `rating 1-5`), Admin IT `POST /feedback/{id}/reply` (`admin_reply`).
- **Master Data** Admin IT `POST /admin/master/{type}` (`categories/departments/buildings/locations/unit-proses/positions`) + bulk `activate/deactivate/delete`.
- **Dashboard** `DashboardController.php:10` User: `my_tickets/my_orders`, Admin: `users/tickets_by_priority/orders`, Administrasi: `orders by status`.

```
[User] --open--> [Admin IT/MTC] --in_progress--> [User Review] --confirm--> [History Selesai]
                                      \--reject--> [in_progress Loop]
```

---

## 2. Role & Permission Matrix

| Role | `users.role` (FK → `roles.slug`) | Gate | Akses |
|------|-------------------------------|------|-------|
| User | `user` | `auth:sanctum` + owner check `ticket.user_id==auth.id` | CRUD own Ticket/Order, Reply/Confirm, Feedback create, Lookup read, User Dashboard |
| Admin IT | `admin` | `AdminApiMiddleware` (`admin.dashboard`/`ticket.manage`) / `AdminMiddleware` + `permission:` | Master Data CRUD+bulk, User Management CRUD, Ticket Admin all, Feedback manage, Admin Dashboard |
| Admin IPSRS | `ipsrs` | `AdministrasiUmumApiMiddleware` (`ipsrs.dashboard`/`order.manage`) + `permission:` | Order Perbaikan manage all, Statistik, Dashboard IPSRS (`admin.ipsrs.dashboard`) |
| Guest | - | - | `POST /api/auth/login` (cek `status=0`), `POST /api/auth/register` |

`users.position` display-only (tidak pernah jadi gate). Seeder (`RoleSeeder → PermissionSeeder → AdminSeeder`, pass `rsazra`): `admin` (IT), `administrasi` (`ipsrs`), `user@rsazra.com` (user). Web admin satu shell (`admin.layouts.*`); legacy `administrasi-umum/*` 301 → `admin/*`; API `admin/*` + `administrasi-umum/*` tetap untuk Flutter.

---

## 3. Struktur API (Tidak Merusak Controller Web Lama)

### 3.1 Service Layer `app/Services/Api/`
- `AuthService.php:12` login cek `Hash::check` + `status`, `createToken('api-token')`.
- `TicketService.php:20` `listForUser/listForAdmin/create/updateUserTicket/deleteUserTicket/reply/confirm/adminRespond/adminUpdate` (DB::transaction, nomor `T-`, foto `Storage::disk('public')`, notify `TicketRespondedNotification`).
- `OrderPerbaikanService.php:20` `listForUser/listForAdmin/statistics/create/update/delete/updateStatus/confirm/reject/start` (nomor `OP/RTG/MTC-` withTrashed).
- `MasterDataService.php` map `categories/departments/buildings/locations/unit-proses/positions` → Model + paginate + bulk.
- `UserService.php`, `FeedbackService.php`.

### 3.2 Request Validation `app/Http/Requests/Api/`
- `Auth/LoginRequest, RegisterRequest` → `email`/`password` + `exists:positions,code`/`departments,code`.
- `Ticket/StoreTicketRequest.php:10` SIRS check, `UpdateTicketRequest`, `ReplyTicketRequest`, `ConfirmTicketRequest (action confirm/reject)`, `AdminRespondRequest (notes+status)`, `AdminUpdateRequest`.
- `OrderPerbaikan/StoreOrderPerbaikanRequest.php:10` (`!=SIRS`), `UpdateOrderPerbaikanRequest`, `UpdateStatusRequest (status+follow_up+prioritas)`.
- `MasterData/StoreMasterDataRequest (match type)`, `User/StoreUserRequest`, `Feedback/StoreFeedbackRequest`.

### 3.3 Resources `app/Http/Resources/Api/`
- `UserResource.php:10`, `TicketResource.php:10` (photos `Storage::url`, `admin_responses/user_replies` decode), `OrderPerbaikanResource.php` (foto_url + history), `FeedbackResource.php`.

### 3.4 Controllers API `app/Http/Controllers/Api/`
- `BaseApiController.php:10` `success/error/paginated`.
- `AuthController.php:15` login/register→`UserResource+token`, `me/logout/logoutAll`.
- `User/TicketController.php:19` (index→`TicketResource::collection`, store/update/destroy/reply/confirm/filterByStatus).
- `User/OrderPerbaikanController.php:10` (index/store/show/update/destroy/konfirmasi/rejected).
- `Admin/TicketAdminController.php:17` (index/show/respond/update/history/all/open/inProgress/closed).
- `Admin/OrderPerbaikanAdminController.php:10` (index/show/updateStatus/confirm/reject/start/statistics).
- `Admin/MasterDataController.php:10` (index/show/store/update/destroy/bulkAction/lookup).
- `Admin/UserManagementController.php:10`, `FeedbackController.php`, `LookupController.php` (categories by `unit_proses_code`, buildings, locations...), `DashboardController.php:10`.

### 3.5 Routes `routes/api.php:18` (70 routes) + `bootstrap/app.php:8` `api: __DIR__/../routes/api.php`
- `POST /api/auth/login` | `POST /api/auth/register` (guest)
- `GET /api/auth/me`, `POST /api/auth/logout` (`auth:sanctum`)
- `GET /api/dashboard/user` | `GET /api/dashboard/admin` (Admin IT) | `GET /api/dashboard/administrasi` (Administrasi)
- `GET /api/lookup/{categories,departments,buildings,locations,unit-proses,positions,kategori-order,priorities,ticket-statuses,order-statuses}`
- `GET|POST /api/tickets`, `GET /api/tickets/filter/{status}`, `GET|PUT|DELETE /api/tickets/{ticket}`, `POST /api/tickets/{ticket}/reply`, `POST /api/tickets/{ticket}/confirm`
- `GET|POST /api/order-perbaikan`, `GET /api/order-perbaikan/konfirmasi|rejected`, `GET|PUT|DELETE /api/order-perbaikan/{id}` (payload order: optional `kategori_order` harus `exists:kategori_order,name`)
- `GET|POST /api/feedback`, `GET /api/feedback/{id}`, `POST /api/feedback/{id}/reply` (Admin), `DELETE /api/feedback/{id}` (Admin)
- `GET|POST|PUT|DELETE /api/admin/master/{type}/{id}`, `POST /api/admin/master/{type}/bulk-action` (Admin IT, `type` juga `kategori-order`/`kategori_order`)
- `GET|POST|PUT|DELETE /api/admin/users/{user}` (Admin IT)
- `GET /api/admin/tickets/*`, `POST /api/admin/tickets/{ticket}/respond`, `PUT /api/admin/tickets/{ticket}`
- `GET /api/administrasi-umum/order-perbaikan/*`, `PUT /api/administrasi-umum/order-perbaikan/{id}/status`, `POST /.../confirm|reject|start` (Administrasi)
- `GET /api/health`

---

## 4. Cara Pakai

```bash
# DB
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=db_helpdesk
DB_USERNAME=postgres
DB_PASSWORD=server233

php artisan migrate:fresh --seed --force
php artisan serve # http://localhost:8000
php artisan test  # 42 passed
```

**Postman:** Import `postman/Helpdesk_API.postman_collection.json:1` (variable `{{base_url}}` `http://localhost:8000`, `{{admin_token}}/{{user_token}}/{{admin_adm_token}}` auto-set via Test script di `POST Login`). Urutan: `01 Auth → POST Login - Admin IT (admin/123)` → `POST Login - User (user@example.com/123 alias user@rsazra.com)` → `03 Lookup` cek `category 2 Printer SIRS`, `location 1 UGD` → `04 Tickets POST Create` (`category_id:2, location_id:1`) → Admin respond → User confirm.

**CURL:**
```bash
curl -X POST http://localhost:8000/api/auth/login -H "Accept: application/json" -d '{"email":"user@example.com","password":"123"}'
# → {"data":{"token":"1|..."}}
curl -H "Authorization: Bearer <token>" http://localhost:8000/api/tickets
```

---

## 5. Validasi & Keamanan

- Sanctum Bearer (`config/sanctum.php:36`, `User.php:14` `HasApiTokens`), `auth:sanctum`.
- FormRequest 422 JSON, foto `image|max:5120` (ticket) / `10240` (order), `Storage::disk('public')`.
- Owner check 403, status check 422, FK validasi `exists:categories,id` dll.

