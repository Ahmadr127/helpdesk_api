# Plan: Generalisasi Kategori → SIRS & UMUM

> Status: **APPROVED / SEDANG DIKERJAKAN**
> Sumber kebenaran: pivot `kategori_jenis` (sudah ada). Kode jenis: `IT` = SIRS, `IPSRS` = UMUM, `selain IT` = juga UMUM (umum).

---

## 1. Latar Belakang

Saat ini kategori dibatasi single flavour (ke `jenis_pengajuan.jenis_id` = IT atau IPSRS), padahal kebutuhan:

- **Tiket/SIRS** — hanya untuk kategori flavour **SIRS**.
- **Umum/Order** — untuk semua kategori flavour **selain SIRS** (karena sifatnya umum), termasuk jenis yang mungkin ditambahkan nantinya (mis. UMUM, HUMAS, UMUM2).

Tujuannya: kategori tidak diduplikasi per flavour; cukup **satu tag jenis kolom `jenis_id` di CRUD**, dan **seluruh** select kategori (create ticket / create order) + **semua** list query membaca marker yang sama via helper.

Kolom `pengajuan.jenis_id` **tidak diperlukan** — keputusan user: "prefer jenis kategori tetap di CRUD kategori".

---

## 2. Skema / Arah yang Dipilih

### 2.1. Naming

| jenis code (DB) | label tampilan (name) | Sifat |
|---|---|---|
| `IT` | **SIRS** | flavour Tiket/SIRS (membantu kebutuhan dashboard IT) |
| `IPSRS` | **UMUM** | flavour umum (Ti ket pesan umum / Order Perbaikan / UMUM) |
| jenis lain di masa depan (misal `UMUM_LANJUTAN`) | name bebas | **otomatis flavour UMUM** (selain SIRS = UMUM) |

Kode `IT` & `IPSRS` **tetap** (supaya data/query lama tidak migrasi ulang); yang berubah adalah *arti semantik flavour* dan label tampilan (selain IT → UMUM).

### 2.2. Helper (Eloquent scopes, model)

Model `KategoriPengajuan` & `Pengajuan`:

```php
// Ticket / SIRS
public function scopeSirs(Builder $query): Builder {}

// Umum — selain jenis dengan kode IT (apa pun code non-SIRS)

public function scopeUmum(Builder $query): Builder {}
```

Sifat:

- **Chainable**: `Pengajuan::sirs()->where('status', 'open')->paginate(...)`.
- **Composable**: `Pengajuan::query()->sirs()->visibleTo(...)`.
- Kategori multi-flavour (IT + IPSRS / IT + jenis baru) muncul di **kedua** dropdown dan **kedua** list — sesuai intent multi-flavour.

### 2.3. Form kategori (dipilih di form)

- Create Tiket → dropdown kategori: `collect()->whereField ...->categories()->sirs();`
- Create Order/Umum → dropdown kategori: `...->umum();`

### 2.4. List get-data (digunakan di mana pun query dibaca)

Semua tempat yang berisi `whereHas('kategori.jenis', ...)` diganti dengan:

- `/admin/tiket* → Pengajuan::sirs() ...`
- `/user/ticket → sirs()`
- `/order* / umum → umum()`
- `/report → scopejenis sudah ada via filter dropdown`

---

## 3. Alternative (tidak dipilih)

- **Opsi A — kolom `pengajuan.jenis_id` terpisah**: memungkinkan display berbeda saat submit tapi duplikasi sumber kebenaran (kategori vs pengajuan) — user menolak.
- **Static helper (`JenisScopes::sirs($q)`)**: bisa dipakai, tapi tidak chainable; user memilih yang **paling mudah dimaintain** → model scopes.
- **Hanya rename code `IT→SIRS`, `IPSRS→UMUM`**: tidak dipilih — memadat, migrasi besar, dan tidak menyentuh kasus jenis baru ketemu. Filter sekarang mengandalkan `code != 'IT'` → UMUM secara umum.

---

## 4. Dependencies & Mekanisme

### 4.1. Backfill / Migration

- Pivot `kategori_jenis` sudah backfill; tidak ada migration skema baru.
- Label `jenis.name` → `SIRS`, `UMUM` dijalankan via `UnifiedPengajuanSeeder` (idempotent: `update(['name' => ...])` di akhir run).

### 4.2. Contoh seed

```php
JenisPengajuan::where('code', 'IT')->update(['name' => 'SIRS']);
JenisPengajuan::where('code', 'IPSRS')->update(['name' => 'UMUM']);
```

### 4.3. Kebutuhan cycle

1. Implementasi scopes (sudah dilakukan pada `Pengajuan` & `KategoriPengajuan`).
2. Rename label jenis di seeder (sudah dilakukan pada `UnifiedPengajuanSeeder`).
3. Backfill data (pivot) — sudah done di migration sebelumnya.
4. Refactor semua site (task checklist).
5. Verifikasi list & create Tiket vs Umum (query counts, CSV, Web).

---

## 5. Out of scope

- Renaming route / menu label UI "SIRS" / "Umum" belum dikerjakan (itu kamu tentukan sendiri nanti).

---

## 6. Verifikasi (checklist)

- [ ] Create Tiket hanya menampilkan kategori SIRS.
- [ ] Create Order hanya menampilkan kategori UMUM (atau "semua selain SIRS").
- [ ] Admin list tiket = total tiket SIRS (sesuai data sampel).
- [ ] User list order dan dashboard umum = total UMUM.
- [ ] CSV & PDF export kolom Jenis menampilkan nama baru (SIRS/UMUM).
- [ ] CSV / HTML report (atau filter di admin report) menampilkan group nama yang sama.

---

## 7. Risk & Revert

| Risiko | Mitigasi |
|---|---|
| "UMUM" dirasakan hanya untuk IPSRS-like; jenis lain ditambahkan kemudian | scopeUmum memakai `code != 'IT'` — otomatis meres. |
| Label lama (`IT` / `IPSRS`) masih terlihat di beberapa dropdown | rename di `UnifiedPengajuanSeeder`; jalankan seeder sekali (prod-safe, idempotent update). |
| Filter flavour di reports tetap berfungsi | pakai mekanisme flavour`code` yang sama. |
