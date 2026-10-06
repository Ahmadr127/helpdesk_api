# Matriks Permission ↔ Menu Sidebar Admin

Sumber kebenaran menu: `config/admin_menu.php` (label, route, pola aktif, permission OR, ikon).
Pilihan layout shell: permission `layout.admin` (seed ke role `admin` + `ipsrs`).
Prinsip: **satu modul = satu permission** (tanpa pecahan view/manage/create/edit).

## Sidebar (shell admin)

| Menu | Route | Permission | admin | ipsrs |
|---|---|---|---|---|
| Dashboard IT | `admin.dashboard` | `dashboard.it`, `ticket` | ✓ | – |
| Dashboard IPSRS | `admin.ipsrs.dashboard` | `dashboard.ipsrs`, `order` | – | ✓ |
| Tickets | `admin.tickets.index` | `ticket` | ✓ | – |
| Tiket Saya | `admin.tickets.myticket` | `myticket` | ✓ | ✓ |
| Order Perbaikan | `admin.order-perbaikan.index` | `order` | ✓ | ✓ |
| Order Saya | `admin.order-perbaikan.myorder` | `myorder` | ✓ | ✓ |
| Master Data | `admin.master.index` | `master` | ✓ | – |
| Users Management | `admin.users.index` | `user` | ✓ | – |
| Permissions | `admin.permissions.index` | `permission` | ✓ | – |
| Reports | `admin.reports.index` | `report` | ✓ | ✓ |
| Report SIRS | `admin.report-sirs.index` | `report.sirs`, `report` | ✓ | – |
| Feedback | `admin.feedback.index` | `feedback` | ✓ | ✓ |
| Notifications | `admin.notifications.index` | `notification` | ✓ | ✓ |
| FCM Monitoring | `fcm.index` | `fcm` | ✓ | – |

Catatan: `Tiket Saya`/`Tickets` berbagi `ticket`; `Order Saya`/`Order Perbaikan` berbagi `order`.
Batas data milik-sendiri vs semua diatur owner-check di controller (`user_id`/`created_by`),
bukan permission terpisah. Area admin tetap digerbangi `layout.admin` (403 bila tak pegang).

## Daftar permission kanonis (seed)

`ticket`, `order`, `myticket`, `myorder`, `master`, `user`, `permission`, `report`, `report.sirs`,
`feedback`, `knowledge`, `notification`, `fcm`,
`dashboard.it`, `dashboard.ipsrs`, `layout.admin`.

Slug lama yang di-prune saat seed — lihat `PermissionSeeder::obsoleteSlugs()`
(`ticket.view/create/edit.own/manage`, `order.*`, `master.*`, `user.*`,
`report.*`, `feedback.*`, `faq.view`, `knowledge.view`, `notification.view`,
`fcm.manage`, `admin.dashboard`, `ipsrs.dashboard`, dll).

## Peta role (seed)

- **user**: `ticket`, `order`, `knowledge`, `notification`, `feedback`, `report`.
- **admin**: `dashboard.it`, `layout.admin`, `ticket`, `order`, `master`, `user`,
  `permission`, `report`, `report.sirs`, `feedback`, `knowledge`, `notification`, `fcm`.
- **ipsrs**: `dashboard.ipsrs`, `layout.admin`, `order`, `ticket`, `feedback`,
  `knowledge`, `notification`, `report`.
