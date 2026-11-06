# 03 — RBAC dan Assignment Role-Kategori

Status: **DRAFT**
Keputusan user: satu role per user; role > permission; permission hanya untuk menu/feature gate; role-kategori adalah inti penugasan/PJ/notifikasi; role di-soft-delete; mapping `kategori_order_user` dipertahankan ke role.

---

## 1. Kondisi Saat Ini

| Komponen | Kondisi | Masalah |
|---|---|---|
| `users.role` | Satu slug role, FK ke `roles.slug` | Tidak ada multi-role (sesuai keputusan: tetap) |
| `roles` | 3 baris: user, admin, ipsrs | Tidak ada CRUD; label hardcode |
| `role_permissions` | `role` varchar + `permission_id` | Tidak ada FK; rename role tidak konsisten |
| `permission_user` | Direct permission additive | Tidak teraudit |
| `kategori_order_user` | User ↔ kategori (dev only) | Salah konsep: assignment seharusnya role, bukan user |
| Scope order | `OrderPerbaikanPjScopeService` | Fallback kategori tanpa PJ; hardcode logika |
| Scope ticket | Admin melihat semua tiket | Tidak ada scope kategori |
| Menu | `hasPermission()` | Sesuai: permission = menu |
| Dashboard | `dashboard.it`/`dashboard.ipsrs` hardcode | Perlu dipertahankan untuk menu, bukan scope data |

### Akar masalah role tidak bisa ditambah

1. Tidak ada `RoleController`.
2. Tidak ada route CRUD role.
3. Tidak ada view create/edit role.
4. `StoreRoleRequest`/`UpdateRoleRequest` tidak dipakai.
5. `RoleService` tidak punya `create/update/delete`.
6. `role_permissions.role` bukan FK.
7. Seeder meng-hardcode dan menghapus semua `role_permissions`.
8. UI hanya mengedit permission per role yang sudah ada.
9. Permission inti bisa dihapus.
10. Perubahan pivot tidak masuk audit.

---

## 2. Target Konsep

| Konsep | Menjawab | Tabel | Contoh |
|---|---|---|---|
| Role | Kelompok identitas | `roles`, `users.role` | `admin`, `ipsrs` |
| Permission | Menu/fitur yang boleh dibuka | `permissions`, `role_permissions`, `permission_user` | `ticket`, `master` |
| Assignment kategori | Ruang lingkup pekerjaan role | `role_kategori` | role `ipsrs` menangani AC, Listrik |
| Penugasan aktual | Pelaksana pada pengajuan | `pengajuan_penugasan` | Budi ditugaskan ke pengajuan #123 |

**Aturan:** permission **tidak** menentukan data mana yang terlihat. Data scope hanya dari `role_kategori` (dan ownership untuk user biasa).

---

## 3. Skema Target

```text
roles
  id, slug (unique, immutable), name, description, is_system, deleted_at

permissions
  id, name, slug (unique), group, description, is_system, deleted_at

role_permissions
  id, role_id FK roles, permission_id FK permissions,
  unique(role_id, permission_id)

permission_user
  id, user_id FK users, permission_id FK permissions,
  unique(user_id, permission_id)

role_kategori
  id, role_id FK roles, kategori_id FK kategori_pengajuan,
  unique(role_id, kategori_id)

users
  role -> roles.slug (satu role per user)

pengajuan_penugasan
  id, pengajuan_id, user_id, role_id, assigned_by, assigned_at, released_at
```

`role_permissions.role` (varchar lama) dipertahankan hanya selama transisi, lalu dihapus setelah semua kode memakai `role_id`.

---

## 4. Matriks Akses Awal

Matriks ini adalah **konfigurasi data**, bukan hardcode. Bisa diubah lewat CRUD.

| Role | Permission (menu) | Kategori yang ditangani | Akses data |
|---|---|---|---|
| `admin` | `dashboard.it`, `layout.admin`, `ticket`, `ticket.manage`, `myticket`, `master`, `user`, `permission`, `report.sirs`, `feedback`, `knowledge`, `notification`, `fcm` | 5 kategori IT: Printer, CCTV, Software, Hardware, Teramedik | Semua pengajuan IT pada kategori tersebut |
| `ipsrs` | `dashboard.ipsrs`, `layout.admin`, `order`, `order.manage`, `myorder`, `report`, `feedback`, `knowledge`, `notification` | 14 kategori IPSRS: Perbaikan, Pengadaan, Kalibrasi, Penggantian, Lainnya, Electrical/Listrik, Plumbing, Facility/Sipil, Medical Gas, Medical Equipment, Lamp, Others, AC Trouble, Rumah Tangga | Semua pengajuan IPSRS pada kategori tersebut |
| `user` | `ticket`, `myticket`, `order`, `myorder`, `knowledge`, `notification`, `feedback`, `report` | Tidak ada | Hanya pengajuan milik sendiri (`pemohon_id`) |

Catatan:

- Permission baru seperti `ticket.manage`/`order.manage` boleh ditambahkan sebagai menu/aksi; permission lama tetap dipertahankan untuk kompatibilitas.
- Role `admin` dan `ipsrs` **wajib** diberi kategori pada setup, agar scope tidak kosong.
- Role baru otomatis memiliki akses data sesuai kategori yang di-assign, tanpa perubahan kode.

---

## 5. Algoritma Otorisasi

```text
Request
  │
  ├─ 1. auth
  ├─ 2. hasPermission(slug) → menu/route gate (fail-closed)
  │
  ├─ 3. Jika route hanya untuk owner:
  │       pemohon_id == user.id
  │
  └─ 4. Jika route admin/PJ:
          role = user.role
          kategori_ids = role_kategori WHERE role_id = role
          jika kategori_ids kosong:
              fallback = kategori yang tidak punya role_kategori (perilaku lama)
          query pengajuan WHERE kategori_id IN kategori_ids
          aksi status:
              pastikan pengajuan.penugasan aktif berisi user
              ATAU user adalah role PJ kategori (boleh assign/ubah status)
```

Aturan tambahan:

- `user` role tidak boleh mengakses daftar admin meskipun memiliki permission menu.
- Penugasan aktual (`pengajuan_penugasan`) memberi hak aksi pada satu pengajuan; role-kategori memberi hak scope.
- Super admin tidak di-hardcode. Jika dibutuhkan, buat role dengan semua kategori dan permission, atau permission `view_all`.

---

## 6. CRUD Role

### 6.1 Route

```php
Route::prefix('roles')->name('roles.')->middleware('permission:permission')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::get('/create', [RoleController::class, 'create'])->name('create');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
    Route::put('/{role}', [RoleController::class, 'update'])->name('update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
});
```

### 6.2 Controller/Service

`RoleController` hanya memanggil `RoleService`:

```text
RoleService::create(array $data): Role
RoleService::update(Role $role, array $data): Role
RoleService::delete(Role $role): void
RoleService::syncPermissions(Role $role, array $permissionIds): void
RoleService::syncCategories(Role $role, array $kategoriIds): void
```

### 6.3 Validasi

| Field | Aturan |
|---|---|
| `slug` | required, lowercase, alpha_dash, unique, immutable saat update |
| `name` | required, max 100 |
| `description` | nullable, max 255 |
| `permissions` | array, exists:permissions,id |
| `kategori_ids` | array, exists:kategori_pengajuan,id |
| Delete | tolak jika role `is_system` atau masih dipakai `users.role` |

### 6.4 Penghapusan

- Soft delete (`roles.deleted_at`).
- Tolak jika ada user dengan `users.role = role.slug`.
- Jika masih ada `role_kategori`, hapus relasi setelah konfirmasi atau tolak.
- Jangan pernah hard delete role yang punya histori `pengajuan_penugasan.role_id`.

---

## 7. CRUD Permission

- `PermissionController` menyediakan index, store, update, destroy.
- `is_system = true` untuk permission inti; tidak bisa dihapus/diubah slug.
- Permission yang masih dipakai `role_permissions`/`permission_user` tidak dihapus keras; soft delete atau tolak.
- Perubahan permission role langsung terlihat karena `hasPermission()` membaca pivot setiap request (dengan cache pendek opsional).
- Direct permission user tetap **additive**; tidak ada mekanisme deny.

---

## 8. Assignment Role-Kategori

### 8.1 CRUD

Dikelola dari halaman Role (multi-select kategori) dan/atau halaman Kategori (multi-select role). Satu sumber kebenaran: tabel `role_kategori`.

### 8.2 Validasi

- Role dan kategori harus aktif.
- Kategori hanya boleh di-assign ke role yang sesuai jenis? **Tidak wajib**; role `admin` bisa saja menangani kategori IPSRS jika bisnis menghendaki. Namun UI memberi peringatan jika jenis role tidak sesuai.
- Satu role boleh punya banyak kategori; satu kategori boleh punya banyak role.

### 8.3 Dampak

| Perubahan | Dampak |
|---|---|
| Role ditambah kategori | Semua user dengan role itu melihat pengajuan kategori tersebut |
| Role dihapus dari kategori | User kehilangan scope kategori tersebut |
| Role baru | Langsung bisa dipakai setelah diberi permission + kategori |
| Kategori baru | Langsung bisa di-assign ke role tanpa ubah kode |

### 8.4 Mapping data lama

`kategori_order_user` (dev) berisi user → kategori. Mapping:

```text
untuk setiap kategori_order_user:
    role = user.role
    jika role == 'user': catat orphan (bukan PJ)
    selain itu: insert role_kategori(role, kategori) ON CONFLICT DO NOTHING
```

Efek: akses bisa melebar dari satu user menjadi seluruh user dengan role tersebut. Ini konsekuensi yang disetujui; catat di laporan migrasi.

Untuk production, tabel ini tidak ada; lakukan seed awal:
- `admin` → semua kategori IT.
- `ipsrs` → semua kategori IPSRS.

---

## 9. Notifikasi Berbasis Role-Kategori

Penerima notifikasi pengajuan baru/perubahan:

```text
users WHERE status = 1
  AND users.role IN (
      SELECT r.slug
      FROM roles r
      JOIN role_kategori rk ON rk.role_id = r.id
      WHERE rk.kategori_id = pengajuan.kategori_id
        AND r.deleted_at IS NULL
  )
  AND users.id != actor_id
```

- Jika tidak ada penerima: fallback ke role yang memiliki permission dashboard sesuai jenis (perilaku lama), lalu log.
- `pengajuan_penugasan` menambah penerima spesifik untuk pengajuan tersebut.
- User pemohon tetap menerima notifikasi status (perilaku lama).
- Jangan mengubah isi/jenis notifikasi; hanya sumber penerima yang berubah dari `kategori_order_user` ke `role_kategori`.

---

## 10. Penghapusan Hardcode

| Lokasi | Hardcode | Pengganti |
|---|---|---|
| `User::isAdminIT()` | `role === 'admin' OR dashboard.it` | `hasPermission('dashboard.it')` untuk menu; scope dari `role_kategori` |
| `User::isAdminUmum()` | `role === 'ipsrs' OR dashboard.ipsrs` | `hasPermission('dashboard.ipsrs')` untuk menu; scope dari `role_kategori` |
| `scopeAdminIT/Umum` | role/permission | query via `role_kategori` |
| `OrderPerbaikanPjScopeService` | nama kategori + fallback | `PengajuanScopeService` berbasis `role_kategori` |
| `PengajuanSinkronService::TARGET_KATEGORI` | `['TV']` | dihapus; tidak ada dual-domain |
| `RoleService::labels/badgeClasses` | 3 role | label dari DB + fallback slug |
| `routes/web.php` dashboard | dashboard.it/ipsrs | permission tetap untuk menu, redirect berdasarkan permission |
| API middleware | dashboard.it/ipsrs | permission tetap untuk gerbang modul; data scope via policy |

Catatan: `dashboard.it`/`dashboard.ipsrs` **tidak dihapus** karena masih dipakai menu dan kompatibilitas. Yang dihapus adalah penggunaannya sebagai penentu scope data.

---

## 11. Matriks Hak Aksi

| Aksi | User pemohon | PJ role-kategori | Penugasan aktif | Admin lain |
|---|---|---|---|---|
| Lihat pengajuan | ya | ya (kategori) | ya | ya (kategori) |
| Edit pengajuan | hanya status `open` | tidak (kecuali diizinkan) | tidak | ya (submission) |
| Hapus pengajuan | hanya status `open` | tidak | tidak | tidak (soft delete via admin?) |
| Start | tidak | ya | ya | ya (kategori) |
| Close/complete | tidak | ya | ya | ya (kategori) |
| Confirm admin | tidak | ya | ya | ya (kategori) |
| Reject | tidak | ya | ya | ya (kategori) |
| Assign pelaksana | tidak | ya | ya | ya (kategori) |
| Konfirmasi user | hanya pemohon | tidak | tidak | tidak |

Hak edit/hapus pemilik dan aturan status **tidak diubah**.

---

## 12. Test Wajib RBAC

1. Role baru bisa dibuat tanpa mengubah kode.
2. Permission baru bisa dibuat dan dicentang ke role.
3. Role baru + kategori langsung memberi scope.
4. Menghapus role yang masih dipakai ditolak.
5. Soft delete role tidak menghapus histori.
6. User role `user` tidak bisa melihat data admin.
7. User dengan role `ipsrs` hanya melihat kategori yang di-assign.
8. Perubahan `role_kategori` langsung mengubah daftar.
9. Direct permission tetap menambah akses menu.
10. Permission inti tidak bisa dihapus.
11. Notifikasi hanya ke role yang di-assign kategori.
12. Audit mencatat perubahan role/permission/kategori.
