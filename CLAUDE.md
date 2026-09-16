# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Helpdesk RS Azra — Laravel 12 + Livewire 3 + Sanctum 4 + PostgreSQL. Dual stack: Blade web UI (`routes/web.php`) + Sanctum Bearer API (`routes/api.php`, ~70 routes) for Flutter. Full flow spec lives in [README_API.md](README_API.md) — read it before touching tickets/orders/auth.

## Commands

```bash
# DB (dev: pgsql 127.0.0.1:5432 db_helpdesk / postgres; testing DB db_helpdesk_testing per phpunit.xml)
php artisan migrate:fresh --seed --force
php artisan serve                    # http://localhost:8000

# Full dev (server + queue + pail + vite via concurrently)
composer dev
npm run dev                          # vite only
npm run build                        # vite build (input: resources/css/app.css, resources/js/app.js)

# Tests
php artisan test                                         # all (~42 tests)
php artisan test --filter=test_user_can_create_ticket    # single test
./vendor/bin/phpunit --filter=TicketApiTest              # single file

# Format (Pint 1.22)
./vendor/bin/pint              # fix
./vendor/bin/pint --test       # check only

# SIMUTU user import (source: sql/simut... dump)
php artisan users:import-simutu --dry-run
php artisan users:import-simutu --update-existing --only-active --limit=10 --dry-run
```

Postman: import `postman/Helpdesk_API.postman_collection.json` (`{{base_url}}` + `{{admin_token}}/{{user_token}}/{{admin_adm_token}}` auto-set on login). Login order: Admin IT → User → Lookup → Create ticket → Respond → Confirm.

## Architecture — two domains, don't mix them

- **Ticket IT (SIRS)**: `tickets` table. Category must belong to `unit_proses.code='SIRS'`. Number `T-ddmm-xxx` daily. Owner CRUD only when `status=open`.
- **Order Perbaikan IPSRS (non-SIRS)**: `order_perbaikan` table (`SoftDeletes`). `unit_proses_code != SIRS` (SRNS/RTG/LOGF). Number `OP/RTG/MTC-YYYYMMDDxxx` (query `withTrashed`). State in `order_perbaikan_histories`.
- Status loop both: `open → in_progress/closed → user confirm → confirmed` (done) or `reject → back to in_progress` (`rejection_count++`, `last_rejection_at`). Ticket conversation stored as JSON (`admin_responses`, `user_replies`) + `ticket_photos` (`initial|admin_response|user_response|user_rejection`); order uses `follow_up` column + `foto` in `order-photos`.

## Layers

- API controllers (`app/Http/Controllers/Api/`, `User/` + `Admin/`) are thin — logic lives in `app/Services/Api/` (`TicketService`, `OrderPerbaikanService`, `AuthService`, `MasterDataService`, `UserService`, `FeedbackService`). Web controllers (`App\Http\Controllers\{User,Admin,AdministrasiUmum}`) are legacy thick; don't port web logic into services unless asked.
- API responses via `BaseApiController::{success,error,paginated}` (`{success,message,data(,meta,links)}`). Validation in `app/Http/Requests/Api/` (422 JSON); photos `ticket-photos` 5MB / `order-photos` 10MB on `public` disk.
- Master data: `departments, positions, buildings, locations, categories→unit_proses, unit_proses, kategori_order`. Read-only lookup at `GET /api/lookup/*` for all authenticated users.

## Auth & permissions (dynamic roles, fail-closed)

- `roles` table (`slug` PK-logic: `user` | `admin` IT | `ipsrs` IPSRS). `users.role` FK → `roles.slug`; `users.position` display-only, never a gate. `role_permissions` has no `position` column.
- `User::hasPermission()` checks `permission_user`, then `role_permissions` (role-only), **fail-closed** (no fallback grant). Helpers: `hasRole()`, `assignRole()`, `roleModel()`, `scopeAdminIT/AdminUmum` (role + permission fallback for notifications).
- Single source of truth: `RoleService` (labels/badges/`exists()`/`syncPermissions()`), `PermissionService` (`grouped()`/`syncRole()`/`syncUser()`), `PermissionSeeder::rolePermissionsMap()`. Web validation in `app/Http/Requests/Admin/*` (`exists:roles,slug`); API in `Api/User/*` + `Api/Auth/RegisterRequest`.
- Web: single admin shell (`admin.layouts.*`, solid theme, no gradients). `AdminMiddleware` permission-based; `permission:` alias (`CheckPermission`: `|` = OR, `,` = AND). `/dashboard` + login redirect: `admin.dashboard` → `admin.dashboard`, `ipsrs.dashboard` → `admin.ipsrs.dashboard`, else `user.dashboard`. Legacy `administrasi-umum/*` URLs 301 → `admin.*`. `AdministrasiUmumMiddleware` + `ipsrs` alias retained only as unused fallback (no web route uses them).
- API: `auth:sanctum` + `AdminApiMiddleware` (`admin.dashboard|ticket.manage`) / `AdministrasiUmumApiMiddleware` (`ipsrs.dashboard|order.manage`) + owner checks (`ticket.user_id==auth.id`, `order.created_by==auth.id`). API prefixes `admin/*` + `administrasi-umum/*` unchanged for Flutter.
- Web login (`Auth/LoginController`) accepts username OR email, rejects `status=0`, tries both credential pairs. Seed order: `RoleSeeder → PermissionSeeder → AdminSeeder` (pass `rsazra`): `admin` (IT), `administrasi` (`ipsrs`), `user@rsazra.com`.
- Tests: shared fixture `tests/Concerns/SeedsAccessControl.php` (`seedRolePermissions()`); IPSRS fixtures use `role=ipsrs`.

## Notifications (always dual-write)

- DB inbox (`TicketRespondedNotification`, `OrderPerbaikanStatusUpdated`) + FCM push via one-liner `Notify::{ticketToAdmins,ticketToUser,orderToAdmins,orderToUser}` (`app/Support/Notifications/Notify.php`) backed by `FirebaseNotification` facade → `FirebaseNotificationService` → queued jobs. Tokens merged in `User::getAllFcmTokens()` (`device_tokens.is_valid` + legacy `users.fcm_token`); `adminIT()`/`adminUmum()` scopes select recipients.

## Gotchas

- `TicketService::create` requires `users.department` set and auto-creates missing `departments` rows — keep that fallback.
- `routes/web.php:109-111` legacy `user.ticket.reply.legacy`/`destroy` names exist to avoid duplicate route-name cache collisions — don't rename.
- Migrations include odd names (`[timestamp]_...`, `xxxx_xx_xx_...`) — they still run; don't "fix" filenames without checking `migrations` table.
- `.env.example` shows sqlite but real dev/test is pgsql — copy `.env`, don't rely on example defaults.
