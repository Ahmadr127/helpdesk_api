# Refactor Role Dinamis + Merge Navigasi Admin

> Dibuat: 2026-09-16. Sumber: permintaan refactor sistem role duplikat → role dinamis,
> permission berbasis sidebar, hapus hardcode, service+request, gabung navigasi admin,
> hapus gradient, lengkapi seeder. Kerjakan berkala per fase, commit kecil.

## Prinsip
- Tidak ada literal role (`'admin'`, `'ipsrs'`, `'user'`) di `app/`, `routes/`, `resources/views/`
  kecuali di seeder/config/RoleService sebagai sumber tunggal.
- Gate akses selalu via `hasPermission()` / middleware `permission:` — tidak via `role ===`.
- Controller web tipis: validasi di `FormRequest`, logika di `Service`.
- Satu shell admin (`admin.layouts.*`) sebagai pusat; tidak ada layout ganda.
- Tema solid, modern, profesional. Tidak ada `bg-gradient-*` / `linear-gradient`.
  Sidebar minim ikon (teks + indikator aktif saja).
- Tidak ada dead code: setiap hapus file harus disertai hapus referensinya
  (route, middleware alias, view include, seeder call).

## Fase 0 — Baseline & audit
- [x] Audit hardcode role/permission (grep + 2 agen Explore)
- [x] Audit navigasi admin vs administrasi-umum + mapping route
- [x] Buat file task ini
- [x] `php artisan test` baseline sebelum ubah inti
- Hasil baseline (2026-09-16): 110 passed, 6 failed — semua gagal di jalur IPSRS
  karena working tree sudah pakai `role=ipsrs` tapi test/seeder masih pakai
  model lama `role=admin + position=Administrasi`:
  `NotifyTest@order_to_admins`, `MasterDataAndUserApiTest@dashboard_endpoints`,
  `OrderNotificationTest` (3x), `OrderPerbaikanApiTest@administrasi_can_manage`.
- Checkpoint batch test-fix (2026-09-16): **116 passed, 0 failed** (full suite, 289 assertions).
  Root cause 7 failure: `User::hasPermission` fail-open saat `permissions` kosong +
  test tanpa seed `role_permissions` + fixture IPSRS model lama. Fix: fail-closed,
  trait `tests/Concerns/SeedsAccessControl.php` di 7 file test, fixture IPSRS → `role=ipsrs`.

## Fase 1 — Core dinamis: tabel `roles`, model, service, request
- [x] Migration `2026_09_16_000006_create_roles_table.php`: tabel `roles` + seed 3 slug,
  drop kolom `position` di `role_permissions`, FK `users.role → roles.slug` (pgsql).
- [x] Model `Role` (`slug` route key, relasi `users`, `permissions` via `role_permissions`).
- [x] Perbaiki `Permission::roles()` (relasi ke `Role::class`).
- [x] `User::roleModel()` belongsTo Role; helper `hasRole()`, `assignRole()`;
  `scopeAdminIT/AdminUmum` + `isAdminIT/isAdminUmum` delegasi ke permission
  (`where('role','admin')` tersisa hanya di dalam scope sebagai fallback seed).
- [x] `app/Services/Access/RoleService.php` — sumber daftar role dinamis (DB).
- [x] `app/Services/Access/PermissionService.php` — `grouped()`, `syncRole()`, `syncUser()`.
- [x] Requests `Admin/*` baru (role `exists:roles,slug`); `Api/User/*` +
  `Api/Auth/RegisterRequest` dinamis + `position` display-only nullable.
  `UserRequest`/`AdminRequest` (unused) dihapus.
- [x] Test: `LoginNavigationTest` 4 passed (2026-09-16).

## Fase 2 — Middleware berbasis permission (hapus cek role literal)
- [x] `AdminMiddleware` → permission-based (fail-closed `hasPermission`).
- [x] `Api/AdminApiMiddleware` + `Api/AdministrasiUmumApiMiddleware` → permission-based, JSON 403.
- [x] `AdministrasiUmumMiddleware` → permission-based (belum dihapus — dipakai Fase 6 redirect).
- [x] Hapus `CheckAdmin` + `babat.php` (dead, tidak dipakai route).
- [x] Test: batch fail-closed hijau — `user cannot access admin master/users/tickets/administrasi`,
  `dashboard endpoints` 403 benar (checkpoint full suite 116 passed, 2026-09-16).

## Fase 3 — Controller tipis (service + request)
- [x] `Admin/UserManagementController` → `UserService` + `Admin/StoreUserRequest`/`UpdateUserRequest`.
- [x] `Admin/PermissionController` → `PermissionService`/`RoleService` + requests.
- [x] `Api/Admin/UserManagementController` request dinamis (Fase 1).
- [x] `Auth/LoginController::redirectBasedOnRole` permission-based, `\Log::info` dibuang.
- [x] `TicketService::create` fallback auto-create department — dipertahankan.
- [x] Test: CRUD user web + API hijau (bagian dari 116 passed).

## Fase 4 — Seeder & tabel user/role/permission
- [x] `RoleSeeder`: `user`/`admin`/`ipsrs` + label dinamis.
- [x] `PermissionSeeder`: map via `rolePermissionsMap()` + skip role tak dikenal,
  tulis tanpa `position` bila kolom sudah di-drop.
- [x] `AdminSeeder`: `administrasi → role=ipsrs`, `position` display-only,
  `username` diisi, komentar mati dibuang.
- [x] Hapus `AdminSeed.php` + seeder dead (`EnsureItPositionSeeder`,
  `AdministrasiPositionSeeder` kosong, `NamaSeeder`, `testseeder`, `UpdateCategoryUnitProsesSeeder`).
- [x] `DatabaseSeeder`: urutan `RoleSeeder → PermissionSeeder → AdminSeeder`.
- [ ] `migrate:fresh --seed` di pgsql dev + login 3 akun (`admin`/`administrasi`/`user@rsazra.com`).

## Fase 5 — Navigasi tunggal + tema solid (pusat: admin)
- [x] `admin/layouts/navigation.blade.php` satu-satunya sidebar: seksi UTAMA
  (Dashboard IT, Dashboard IPSRS), TIKET & ORDER, MASTER DATA, USERS, PERMISSIONS,
  LAPORAN, LAINNYA — gate permission sama seperti sebelumnya, tanpa SVG per-menu
  (indikator `border-l-4` aktif), tanpa gradient/badge warna-warni.
- [x] `admin/layouts/app.blade.php`: body/`.card-header`/header/avatar/back-to-top solid.
- [x] `resources/views/administrasi-umum/` dihapus seluruhnya (layout + 11 page +
  partial yatim `_priority-groups`, `_table`, `orders/index` kosong, `edit` tanpa route).
  Controller yatim `AdministrasiUmum/OrderController` (model `OrderBarang` tak ada) +
  `AdministrasiUmumController` (view tak ada) dihapus; method mati
  `DashboardController@profile/settings/getStats` dibuang; import yatim dibersihkan.
- [x] Test: `AdminIpsrsNavigationTest` (render 7 halaman di shell admin + redirect legacy
  + redirect login) hijau; `LoginNavigationTest` 4 passed.

## Fase 6 — Route & page IPSRS pindah ke admin (logic utuh)
- [x] `admin/ipsrs-dashboard` + `admin/ipsrs-dashboard/stats` + `admin/order-perbaikan/*`
  (16 route, 1:1 dengan legacy; `/{orderPerbaikan}` paling bawah, export POST,
  update-status PUT). Controller tetap `AdministrasiUmum/*` via `viewNamespace()`
  (param `time_filter/status_filter` + variabel `order` vs `orderPerbaikan` utuh).
- [x] Legacy `administrasi-umum/*` 301 → `admin.*` (GET; termasuk profile/settings/dokumen
  dkk yang view-nya memang tak pernah ada).
- [x] `/dashboard` dispatcher + `LoginController` redirect IPSRS → `admin.ipsrs.dashboard`.
- [x] API tidak diubah (kontrak Flutter utuh; middleware sudah permission-based di Fase 2).
- [x] Test: `AdminIpsrsNavigationTest` hijau (render + redirect + login).

## Fase 7 — Bersih-bersih & verifikasi akhir
- [x] Grep hardcode: nol `role ===`/`in:user,admin` di `app/`+`routes/`+`resources/views/`;
  `where('role',…)` tersisa hanya di `User::scopeAdminIT/AdminUmum` (fallback notif)
  + `UserService::list` (filter eksplisit) — diizinkan.
- [x] Grep gradient: nol di `resources/views/admin/**`.
- [x] Grep dead: `CheckAdmin`/`AdminSeed`/`backup`/`roleAccess`/`roleList` nol;
  `AdministrasiUmumMiddleware` + alias `ipsrs` hanya definisi tanpa pemakai route.
- [x] Pint: pre-existing failures di seluruh repo (bukan dari refactor) → tidak di-fix massal.
- [x] `php artisan test` full hijau — checkpoint Fase 5–6: **119 passed** (2026-09-16).
  Baseline Fase 0: 110 passed + 6 failed.
- [x] `CLAUDE.md` + `README_API.md` (matrix role + redirect + legacy 301) diperbarui.
- [ ] Sisa opsional: `migrate:fresh --seed` di pgsql dev + login 3 akun.

## Catatan keputusan
- FK memakai `slug` (bukan `role_id` integer) agar `users.role` string tetap kompatibel
  dengan API/Flutter & migrasi minimal. Bila di masa depan mau `role_id`, buat fase terpisah.
- `users.position` tetap ada sebagai display string (`exists:positions,code`, nullable),
  tidak pernah dipakai untuk gate akses.
- `role_permissions.position` dihapus (selalu `null`, dead column).
