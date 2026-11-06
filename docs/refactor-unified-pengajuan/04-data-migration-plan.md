# 04 — Data Migration Plan

Status: **DRAFT — menunggu approval**
Baseline data: production `d8de3e9`, dump `db_helpdesk.sql` (PostgreSQL 17.6)
Target: `pengajuan` + `kategori_pengajuan` + `role_kategori`

---

## 1. Prinsip Migrasi

1. **Tidak ada `DROP` / `TRUNCATE` pada tabel lama** sampai validasi cutover selesai.
2. **Tabel lama tidak diubah** selama backfill; migrasi hanya menulis ke tabel baru.
3. **Idempotent:** setiap sumber hanya boleh menghasilkan satu baris target.
4. **Dapat dihentikan:** setiap batch memiliki checkpoint dan status.
5. **Dapat ditelusuri:** setiap baris baru memiliki `legacy_map`.
6. **Nomor lama tidak diubah.**
7. **Histori dan lampiran tidak boleh hilang.**
8. **Satu pengajuan = satu proses bisnis.** Tidak ada duplikasi karena pasangan TV.
9. **Tidak ada backup otomatis dalam task ini** (sesuai instruksi). Sumber rollback adalah dump production asli dan tabel lama yang tidak disentuh.
10. **Data production dipulihkan ke database lokal terlebih dahulu**; migration diuji di sana sebelum menyentuh production.

> Catatan: production dump berformat PostgreSQL 17.6. Database lokal 14.5 tidak dapat langsung menerima `\restrict` dan `transaction_timeout`. Preprocessing dump atau restore ke PostgreSQL 17 adalah prasyarat task setup.

---

## 2. Inventaris Awal (Preflight)

Jalankan sebelum membuat tabel baru. Simpan hasilnya sebagai `migration_runs.stats`.

### 2.1 Row count sumber

```sql
SELECT 'users' AS tabel, count(*) FROM users
UNION ALL SELECT 'departments', count(*) FROM departments
UNION ALL SELECT 'locations', count(*) FROM locations
UNION ALL SELECT 'buildings', count(*) FROM buildings
UNION ALL SELECT 'categories', count(*) FROM categories
UNION ALL SELECT 'kategori_order', count(*) FROM kategori_order
UNION ALL SELECT 'tickets', count(*) FROM tickets
UNION ALL SELECT 'ticket_photos', count(*) FROM ticket_photos
UNION ALL SELECT 'order_perbaikan', count(*) FROM order_perbaikan
UNION ALL SELECT 'order_perbaikan_histories', count(*) FROM order_perbaikan_histories
UNION ALL SELECT 'notifications', count(*) FROM notifications;
```

Baseline production yang diketahui:

| Tabel | Jumlah |
|---|---:|
| users | 342 |
| departments | 61 |
| locations | 28 |
| buildings | 3 |
| categories | 5 |
| kategori_order | 14 |
| tickets | 16 |
| ticket_photos | 16 |
| order_perbaikan | 7 |
| order_perbaikan_histories | 17 |

### 2.2 Distribusi status

```sql
SELECT 'ticket' AS sumber, status, count(*) FROM tickets GROUP BY status
UNION ALL
SELECT 'order', status, count(*) FROM order_perbaikan GROUP BY status
ORDER BY 1, 2;
```

### 2.3 Orphan dan integritas

```sql
-- tiket tanpa user
SELECT id FROM tickets t WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.id = t.user_id);
-- tiket tanpa kategori
SELECT id FROM tickets t WHERE NOT EXISTS (SELECT 1 FROM categories c WHERE c.id = t.category_id);
-- order tanpa user pembuat
SELECT id FROM order_perbaikan o WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.id = o.created_by);
-- order dengan kategori tidak ada di master
SELECT o.id, o.kategori_order FROM order_perbaikan o
WHERE NOT EXISTS (SELECT 1 FROM kategori_order k WHERE k.name = o.kategori_order);
-- foto tanpa tiket
SELECT id FROM ticket_photos p WHERE NOT EXISTS (SELECT 1 FROM tickets t WHERE t.id = p.ticket_id);
-- histori tanpa order
SELECT id FROM order_perbaikan_histories h WHERE NOT EXISTS (SELECT 1 FROM order_perbaikan o WHERE o.id = h.order_perbaikan_id);
-- departemen/lokasi yang sudah hilang
SELECT id, department_id, lokasi FROM order_perbaikan o
WHERE (department_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM departments d WHERE d.id = o.department_id))
   OR (lokasi IS NOT NULL AND NOT EXISTS (SELECT 1 FROM locations l WHERE l.id = o.lokasi));
```

Semua orphan dicatat ke `migration_orphans`; migrasi tidak boleh menghentikan seluruh batch karena satu orphan tanpa keputusan.

### 2.4 Duplikasi kategori

```sql
SELECT lower(name), count(*) FROM kategori_order
WHERE deleted_at IS NULL GROUP BY lower(name) HAVING count(*) > 1;

SELECT lower(name), count(*) FROM categories
WHERE status = 1 GROUP BY lower(name) HAVING count(*) > 1;
```

Jika ada duplikasi nama dalam satu jenis, hentikan mapping kategori dan minta keputusan.

### 2.5 Deteksi pasangan TV (development only)

```sql
SELECT * FROM pengajuan_links;
SELECT * FROM tickets t JOIN pengajuan_links l ON l.ticket_id = t.id;
SELECT * FROM order_perbaikan o JOIN pengajuan_links l ON l.order_perbaikan_id = o.id;
```

Production **tidak memiliki** `pengajuan_links`; langkah ini hanya untuk rehearsal database development.

---

## 3. Strategi

**Expand → Backfill → Validate → Freeze → Cutover → Contract.**

| Fase | Aktivitas | Tabel lama |
|---|---|---|
| M0 | Restore production dump ke DB staging/local | read-only |
| M1 | Buat tabel baru + kolom transisi | tidak berubah |
| M2 | Seed `jenis_pengajuan`, `status_pengajuan`, permission/role | tidak berubah |
| M3 | Migrasi kategori + role_kategori | tidak berubah |
| M4 | Migrasi `pengajuan` dari tiket/order | tidak berubah |
| M5 | Migrasi histori, pesan, lampiran, penugasan | tidak berubah |
| M6 | Validasi + laporan | tidak berubah |
| M7 | Cutover kode (baca/tulis ke `pengajuan`) | read-only |
| M8 | Rename `*_legacy` setelah masa stabil | arsip |

**Tidak menggunakan dual-write pada fase awal.** Aplikasi lama tetap menulis ke tabel lama sampai cutover. Untuk menghindari data yang masuk saat cutover, gunakan maintenance window singkat atau mode read-only.

**Alasan:** dua domain memiliki logic dan status berbeda; dual-write menambah risiko inkonsistensi sebelum model baru stabil. Data production saat ini kecil (16 tiket, 7 order), tetapi desain tetap harus aman untuk pertumbuhan.

---

## 4. Mapping Tabel

| Sumber production | Target | Aturan |
|---|---|---|
| `categories` | `kategori_pengajuan` (`jenis_id = IT`) | nama, status, deleted_at; code `IT-{id}` bila perlu |
| `kategori_order` | `kategori_pengajuan` (`jenis_id = IPSRS`) | nama, code, status, deleted_at |
| `tickets` | `pengajuan` + `pengajuan_histori` | satu tiket = satu pengajuan |
| `order_perbaikan` | `pengajuan` + `pengajuan_order_detail` + histori | satu order = satu pengajuan |
| `pengajuan_links` (dev) | tidak dipakai | TV dideprecate; lihat §7 |
| `ticket_photos` | `pengajuan_lampiran` | type → kind |
| `order_perbaikan.foto` | `pengajuan_lampiran` | JSON/string path |
| `order_perbaikan_histories` | `pengajuan_histori` + lampiran | status/notes/actor |
| `tickets.admin_responses` | `pengajuan_pesan` + `pengajuan_histori` | JSON array |
| `tickets.user_replies` | `pengajuan_pesan` + `pengajuan_histori` | JSON array |
| `order_perbaikan.nama_penanggung_jawab` | `pengajuan_penugasan` atau histori | resolve nama → user; jika gagal, catat orphan |
| `order_perbaikan.unit_penerima` | `pengajuan_order_detail.unit_penerima` | |
| `order_perbaikan.kode_inventaris` | `pengajuan_order_detail.kode_inventaris` | |
| `order_perbaikan.nama_barang` | `pengajuan_order_detail.nama_barang` | |
| `order_perbaikan.unit_proses_id` | `pengajuan_order_detail.unit_proses_id` | FK |
| `kategori_order_user` (dev) | `role_kategori` | user.role; role `user` → orphan |
| Pivot PJ (dev) | `pengajuan_penugasan` | hanya jika ada; production tidak punya |
| `notifications` | tetap | morph lama dibiarkan sebagai arsip |

### 4.1 Mapping kolom `tickets → pengajuan`

| Lama | Baru | Catatan |
|---|---|---|
| `ticket_number` | `nomor` | dipertahankan |
| `user_id` | `pemohon_id` | |
| `user.name` | `pemohon_nama` | snapshot saat migrasi |
| `category_id` | `kategori_id` | melalui mapping kategori IT |
| `department_id` | `department_id` | |
| `building_id` | `building_id` | |
| `location_id` | `lokasi_id` | |
| `description` | `deskripsi` | |
| `priority` | `prioritas` | `low/medium/high` tetap |
| `status` | `status` | lihat §5 |
| `opened_at` | `tanggal` | fallback `created_at` |
| `in_progress_at` | `started_at` | |
| `closed_at` | `closed_at` | |
| `confirmed_at` / `user_confirmed_at` | `confirmed_at` | |
| `rejection_count` | `rejection_count` | |
| `user_confirmation` | `user_confirmation` | |
| `created_at/updated_at` | sama | |
| `deleted_at` | `deleted_at` | soft delete dipertahankan |
| `admin_responses` | `pengajuan_pesan` + histori | §8 |
| `user_replies` | `pengajuan_pesan` + histori | §8 |

Field lama `category`, `department`, `building`, `location`, `photo`, `admin_notes`, `rejection_notes`, `rejection_photo`, `confirmation_notes`, `confirmation_photo`, `user_confirmation_notes` tidak disalin mentah ke header. Nilai yang relevan masuk ke histori/pesan/lampiran; nama snapshot lama disimpan di `pengajuan_histori.metadata` bila diperlukan untuk audit.

### 4.2 Mapping kolom `order_perbaikan → pengajuan`

| Lama | Baru | Catatan |
|---|---|---|
| `nomor` | `nomor` | dipertahankan |
| `tanggal` | `tanggal` | |
| `created_by` | `pemohon_id` | |
| `nama_peminta` | `pemohon_nama` | snapshot |
| `department_id` | `department_id` | |
| `lokasi` | `lokasi_id` | |
| `keluhan` | `deskripsi` | |
| `prioritas` | `prioritas` | map `RENDAH→low`, `SEDANG→medium`, `TINGGI/URGENT→high` |
| `status` | `status` | lihat §5 |
| `follow_up` | `follow_up` | |
| `closed_at` | `closed_at` | production tidak punya; derive dari histori `tutup` atau `updated_at` |
| `kategori_order` | `kategori_id` | cari kategori IPSRS berdasarkan nama; jika tidak ada → orphan |
| `unit_penerima` | `pengajuan_order_detail.unit_penerima` | |
| `kode_inventaris` | `pengajuan_order_detail.kode_inventaris` | |
| `nama_barang` | `pengajuan_order_detail.nama_barang` | |
| `unit_proses_id` | `pengajuan_order_detail.unit_proses_id` | |
| `nama_penanggung_jawab` | penugasan/histori | resolve `users.name`; jika ambigu → orphan |
| `foto` | lampiran | §9 |

Tidak ada `building_id` pada order production; `building_id` dibiarkan NULL. `gedung` tidak ada di production; jika data development memiliki `gedung`, simpan sebagai `metadata` atau lampiran histori.

### 4.3 Mapping kategori

1. Untuk setiap `categories`: buat `kategori_pengajuan` dengan `jenis_id = IT`, `name`, `status`, `deleted_at` (jika ada), `code = 'IT-' || id`.
2. Untuk setiap `kategori_order`: buat `kategori_pengajuan` dengan `jenis_id = IPSRS`, `name`, `code` (jika ada), `status`, `deleted_at`.
3. Unik per `(jenis_id, lower(name))`; nama yang sama di IT dan IPSRS tetap dua baris berbeda karena jenisnya berbeda.
4. Simpan mapping `source_table, source_id → kategori_id` di `legacy_map`? Gunakan tabel bantu `migration_category_map` (dibuat sementara) atau `legacy_map` dengan `source_table='categories'` dan `source_table='kategori_order'`. `legacy_map.pengajuan_id` NOT NULL, jadi untuk kategori gunakan tabel sementara `migration_category_map` yang dihapus setelah validasi.

### 4.4 Mapping role-kategori

Production tidak memiliki `kategori_order_user`. Seed awal:

```text
role admin  → semua kategori_pengajuan dengan jenis IT
role ipsrs  → semua kategori_pengajuan dengan jenis IPSRS
role user   → tidak ada
```

Jika data development `kategori_order_user` ikut direhearsal:

```text
untuk setiap (kategori_order_id, user_id):
    role = users.role
    jika role = 'user' → migration_orphans (bukan PJ)
    selain itu → role_kategori(role, kategori) ON CONFLICT DO NOTHING
```

Konsekuensi: scope melebar dari satu user menjadi seluruh user dengan role tersebut. Ini disetujui pada D-12; catat jumlah role_kategori sebelum/sesudah.

---

## 5. Mapping Status

### 5.1 Aturan umum

- Status baru: `open`, `in_progress`, `pending_confirmation`, `confirmed`, `rejected`.
- `pending` dan `closed` pada tiket sama-sama menjadi `pending_confirmation` **hanya jika** `user_confirmation = false`.
- Jika `user_confirmation = true`, status menjadi `confirmed`.
- Status order `tutup` → `pending_confirmation`.
- Jangan menggabungkan `rejected` dengan status lain.

### 5.2 Tabel mapping

| Sumber | Status lama | Kondisi | Status baru | Aksi tambahan |
|---|---|---|---|---|
| Ticket | `open` | — | `open` | — |
| Ticket | `in_progress` | — | `in_progress` | isi `started_at` |
| Ticket | `closed` | `user_confirmation=false` | `pending_confirmation` | isi `closed_at` |
| Ticket | `closed` | `user_confirmation=true` | `confirmed` | isi `confirmed_at` |
| Ticket | `pending` | `user_confirmation=false` | `pending_confirmation` | isi `closed_at` |
| Ticket | `pending` | `user_confirmation=true` | `confirmed` | isi `confirmed_at` |
| Ticket | `confirmed` | — | `confirmed` | isi `confirmed_at` |
| Order | `open` | — | `open` | — |
| Order | `in_progress` | — | `in_progress` | isi `started_at` |
| Order | `tutup` | — | `pending_confirmation` | isi `closed_at` |
| Order | `confirmed` | — | `confirmed` | isi `confirmed_at` |
| Order | `rejected` | — | `rejected` | isi `rejected_at` |

Status yang tidak dikenal → `migration_orphans`, jangan menebak.

### 5.3 Histori status

Selain status akhir, buat histori sintetis:

| Sumber data | Action histori |
|---|---|
| `created_at` | `created` |
| `in_progress_at` / `started_at` | `started` |
| `closed_at` | `closed` |
| `user_confirmed_at` / `confirmed_at` | `confirmed` |
| `last_rejection_at` atau `rejection_count > 0` | `rejected` (satu baris per kejadian yang dapat dibuktikan) |
| `order_perbaikan_histories` | status asli, notes, actor |

Jangan mengarang timestamp. Jika hanya ada `rejection_count` tanpa timestamp, catat satu histori dengan `metadata.rejection_count` dan timestamp `updated_at`, tandai `synthetic=true`.

---

## 6. Migrasi `pengajuan`

### 6.1 Tiket

Untuk setiap tiket:

1. Insert `pengajuan` dengan `legacy_source='ticket'`, `legacy_id=ticket.id`, `nomor=ticket_number`.
2. `kategori_id` dari mapping kategori IT.
3. `pemohon_id = user_id`, `pemohon_nama = users.name` (snapshot).
4. `status` sesuai §5.
5. Insert `legacy_map(source_table='tickets', source_id, pengajuan_id)`.
6. Insert histori minimal `created`.
7. Jika `ticket_photos` ada, lampiran.
8. Jika `admin_responses`/`user_replies` ada, pesan/histori.

### 6.2 Order

Untuk setiap order:

1. Insert `pengajuan` dengan `legacy_source='order'`, `legacy_id=order.id`, `nomor=nomor`.
2. `kategori_id` dari mapping kategori IPSRS.
3. `pemohon_id = created_by`, `pemohon_nama = nama_peminta`.
4. `status` sesuai §5.
5. Insert `pengajuan_order_detail` dengan field khusus production.
6. Insert `legacy_map(source_table='order_perbaikan', source_id, pengajuan_id)`.
7. Histori dari `order_perbaikan_histories`, plus histori sintetis untuk status akhir.
8. Lampiran dari `foto` dan `histories.lampiran`.
9. `nama_penanggung_jawab` → `pengajuan_penugasan` bila resolve unik; jika tidak, histori `assigned` dengan metadata nama.

### 6.3 Urutan eksekusi

```text
1. kategori IT
2. kategori IPSRS
3. tiket (tanpa dependensi order)
4. order
5. histori tiket (dari JSON)
6. histori order
7. pesan
8. lampiran
9. penugasan
10. role_kategori
```

Jika ada pasangan TV development:

```text
11. gabungkan pasangan (lihat §7)
```

Gunakan transaksi per batch 100 baris; commit; update checkpoint.

---

## 7. Penanganan Pasangan TV

### 7.1 Production

Production **tidak memiliki** `pengajuan_links` dan tidak memiliki kategori TV. Tidak ada penggabungan yang diperlukan. Kode migrasi tetap harus aman jika tabel link tidak ada (cek `Schema::hasTable`).

### 7.2 Development / rehearsal

Jika `pengajuan_links` ada dan berisi pasangan:

1. Ambil tiket dan order yang ditautkan.
2. Pilih canonical: **tiket** (karena memiliki pemohon user dan category SIRS).
3. Buat **satu** `pengajuan` dari tiket.
4. Karena keputusan D-13 tidak mempertahankan TV dua domain, order pasangan **tidak** menjadi pengajuan kedua.
5. Data order yang tidak dapat dipetakan (unit_penerima, kode_inventaris, nama_barang, histori, lampiran) disimpan:
   - sebagai `pengajuan_histori` dengan action `legacy_order_merged` dan metadata lengkap, atau
   - di `migration_orphans` bila perlu keputusan bisnis.
6. Simpan nomor order di `pengajuan_nomor_legacy` (jika tabel dipakai) atau `pengajuan_histori.metadata.legacy_order_nomor`.
7. Simpan `legacy_map` untuk kedua sumber, keduanya menunjuk ke pengajuan yang sama. **Namun** unique `(source_table, source_id)` tetap unik; `pengajuan_id` boleh sama.
8. Lampiran order digabung ke pengajuan; deduplikasi path.
9. Histori digabung dan diurutkan berdasarkan timestamp.
10. Catat di laporan: jumlah pasangan, pasangan tidak lengkap, data order yang diarsipkan.

Jika keputusan berubah dan TV harus tetap dua track, migrasi harus dihentikan; desain `pengajuan_pekerjaan` sudah dihapus dari target (D-13), sehingga perubahan ini memerlukan desain ulang.

### 7.3 Pasangan tidak lengkap

- Link hanya punya tiket → migrasikan tiket; catat order hilang.
- Link hanya punya order → migrasikan order; catat tiket hilang.
- Link menunjuk record soft-deleted → migrasikan dengan `deleted_at`; catat.
- Link ganda pada sumber yang sama → orphan; hentikan.

---

## 8. Histori dan Pesan dari JSON Tiket

`admin_responses` dan `user_replies` adalah array JSON. Setiap elemen:

```json
{
  "notes": "...",
  "timestamp": "...",
  "status": "in_progress",
  "admin_id": 1,
  "admin_name": "Admin",
  "photo": "path/atau/array",
  "type": "confirm|reject",
  "sync": true
}
```

Aturan:

1. Selalu buat `pengajuan_pesan` jika ada `notes`/`message`; `sender_type` = `admin` untuk admin_responses, `user` untuk user_replies, `system` jika `sync=true` dan tanpa sender.
2. Jika ada `status`, buat `pengajuan_histori` dengan action sesuai status.
3. Jika `type=confirm` → histori `confirmed`; `type=reject` → histori `rejected`.
4. `photo`/`photo_urls` → lampiran; hubungkan ke `pesan_id` atau `histori_id` jika dapat dipetakan.
5. Jangan menyimpan JSON mentah di kolom baru; simpan metadata di `metadata` hanya untuk field yang tidak punya kolom.
6. Deduplikasi pesan berdasarkan `(pengajuan_id, sender_type, timestamp, body)` untuk mencegah Import ganda.
7. `admin_name` disimpan di `metadata` jika user tidak ditemukan.

---

## 9. Lampiran

### 9.1 Sumber

| Sumber | Jenis | Path |
|---|---|---|
| `ticket_photos.photo_path` | initial/admin_response/user_response/user_rejection | string |
| `order_perbaikan.foto` | initial | string atau JSON array |
| `order_perbaikan_histories.lampiran` | history | string atau JSON array (production: varchar) |
| `admin_responses[].photo` | admin_response | string/array |
| `user_replies[].photo` | user_response | string/array |

### 9.2 Aturan

1. Normalisasi setiap nilai: jika JSON array, decode; jika string, satu path.
2. Abaikan null/string kosong.
3. Simpan `path` apa adanya (jangan ubah URL storage).
4. Deduplikasi berdasarkan `pengajuan_id + path`; jika path sama dari dua sumber, pertahankan satu baris dan catat sumber di metadata.
5. Isi `kind` sesuai asal; `uploaded_by` dari `user_id`/`created_by` jika ada.
6. Jangan memindahkan file fisik pada tahap migrasi database; file tetap di disk. Verifikasi keberadaan file di storage sebelum cutover.
7. Untuk file yang tidak ditemukan, buat baris lampiran dengan metadata `missing_file=true` agar tidak hilang dari laporan.

### 9.3 Validasi jumlah

Hitung:

```text
jumlah_path_sumber = ticket_photos + path order.foto + path histories.lampiran + path JSON
jumlah_lampiran_target = pengajuan_lampiran
selisih = jumlah_path_sumber - duplikat - path_kosong
```

Target harus sama dengan `jumlah_lampiran_target` (setelah deduplikasi). Selisih dicatat.

---

## 10. Penugasan dan PJ

1. Production order memiliki `nama_penanggung_jawab` (string). Cari user dengan nama persis; jika satu hasil → `pengajuan_penugasan`.
2. Jika lebih dari satu user dengan nama sama → orphan.
3. Jika tidak ada → histori `assigned` dengan metadata nama, tanpa penugasan.
4. Pivot PJ development (`ticket_penanggung_jawab`, `order_perbaikan_penanggung_jawab`) → `pengajuan_penugasan` dengan `role_id = users.role` saat migrasi.
5. `pengajuan_penugasan` bukan pengganti `role_kategori`; ini pelaksana aktual.
6. Jangan membuat penugasan untuk semua user role-kategori; itu scope, bukan penugasan.

---

## 11. Nomor dan ID

- `pengajuan.nomor` = nomor lama untuk semua data migrasi.
- `legacy_source` + `legacy_id` menyimpan asal.
- `legacy_map` menyimpan pemetaan.
- Jangan mengubah `ticket_number`/`nomor` lama.
- Nomor baru setelah cutover memakai `document_sequences` per jenis/periode.
- Untuk periode yang sudah memiliki nomor lama, sequence dimulai dari angka tertinggi yang dapat diparse; jika format tidak dapat diparse, mulai dari 1 dan cek unique.
- API v1 tetap mengembalikan `ticket_number`/`nomor` lama melalui resource compatibility.
- Deep link notifikasi lama (`helpdesk://ticket/{id}`) harus dipetakan: simpan `legacy_id` dan sediakan resolver; jangan mengganti ID pada payload lama tanpa compatibility.

---

## 12. Idempotensi

- `legacy_map` unique(`source_table`,`source_id`).
- Sebelum insert, cek `legacy_map`; jika ada, skip.
- Untuk baris anak (histori/pesan/lampiran), gunakan natural key sementara:
  - histori: `(pengajuan_id, action, timestamp, notes_hash)`
  - pesan: `(pengajuan_id, sender_type, timestamp, body_hash)`
  - lampiran: `(pengajuan_id, path)`
- Simpan `migration_runs` dan `migration_checkpoints` (batch, last_source_id).
- Jangan memakai `truncate` untuk mengulang; gunakan delete terbatas pada baris yang dibuat run tersebut (dicatat di `migration_runs`).

---

## 13. Validasi

### 13.1 Kriteria wajib

| Kriteria | Target |
|---|---|
| Semua tiket menjadi pengajuan | 16/16 (production) |
| Semua order menjadi pengajuan | 7/7 |
| Semua `legacy_map` menunjuk pengajuan valid | 100% |
| Tidak ada pengajuan tanpa kategori | 0 |
| Tidak ada pengajuan tanpa pemohon | 0 |
| Nomor pengajuan unik | 0 duplikat |
| Status hanya 5 nilai | 0 invalid |
| Semua histori order pindah | ≥ 17 baris |
| Semua lampiran sumber terhitung | selisih = 0 atau terjelaskan |
| Role-kategori terisi | admin 5, ipsrs 14 (production) |
| Orphan tercatat | 100% di `migration_orphans` |
| Tidak ada duplikasi pasangan | 0 |

### 13.2 Query validasi

```sql
-- jumlah sumber vs target
SELECT (SELECT count(*) FROM tickets) AS tiket_sumber,
       (SELECT count(*) FROM order_perbaikan) AS order_sumber,
       (SELECT count(*) FROM pengajuan WHERE legacy_source IS NOT NULL) AS pengajuan_target;

-- pengajuan tanpa legacy map
SELECT p.id, p.nomor FROM pengajuan p
WHERE p.legacy_source IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM legacy_map m WHERE m.pengajuan_id = p.id);

-- kategori invalid
SELECT p.id FROM pengajuan p
LEFT JOIN kategori_pengajuan k ON k.id = p.kategori_id
WHERE k.id IS NULL;

-- status invalid (seharusnya tidak mungkin karena CHECK)
SELECT status, count(*) FROM pengajuan GROUP BY status;

-- lampiran per sumber vs target
SELECT 'ticket_photos' AS sumber, count(*) FROM ticket_photos
UNION ALL SELECT 'order_foto_paths', count(*) FROM order_perbaikan WHERE foto IS NOT NULL
UNION ALL SELECT 'history_lampiran', count(*) FROM order_perbaikan_histories WHERE lampiran IS NOT NULL
UNION ALL SELECT 'target', count(*) FROM pengajuan_lampiran;
```

### 13.3 Laporan

Setiap run menghasilkan:

- `migration_runs.stats` (JSON): count, durasi, batch, error.
- `migration_orphans`: sumber, alasan, payload.
- File CSV/Markdown `migration-report-{timestamp}.md` di `storage/app/migration/` untuk diperiksa manusia.
- Checksum sampel: 5 tiket + 5 order, bandingkan field kunci sebelum/sesudah.

**Cutover tidak boleh dilanjutkan jika salah satu kriteria wajib gagal.**

---

## 14. Rollback dan Pemulihan

Karena backup tidak diminta:

1. **Tabel lama tidak diubah** selama M1–M6; rollback = hentikan proses, hapus tabel baru, aplikasi lama tetap berjalan.
2. **Sebelum cutover**, dump production asli tetap tersedia; jika cutover gagal, restore dump ke instance bersih dan arahkan aplikasi ke sana.
3. **Setelah cutover**, rollback kode ke baseline `d8de3e9`; tabel lama masih ada sampai M8.
4. Data baru yang dibuat setelah cutover tidak otomatis kembali ke tabel lama. Jika rollback setelah ada data baru:
   - ekspor `pengajuan` baru ke CSV,
   - petakan balik ke `tickets`/`order_perbaikan` untuk data yang memiliki `legacy_source`,
   - data tanpa `legacy_source` memerlukan prosedur manual; ini risiko yang harus disetujui.
5. Jangan pernah menjalankan `DROP TABLE` pada M8 sebelum laporan validasi disetujui.

---

## 15. Risiko Migrasi

| Risiko | Tingkat | Mitigasi |
|---|---|---|
| Dump PG17 tidak bisa restore ke PG14 | Tinggi | Preprocess atau gunakan PG17 untuk staging |
| Kategori order tidak ditemukan | Sedang | Orphan + keputusan manual; jangan buat kategori otomatis tanpa approval |
| Nama PJ ambigu | Sedang | Resolve unik; jika tidak, histori metadata |
| Status `closed`/`pending` tidak konsisten | Tinggi | Mapping berbasis `user_confirmation`; validasi manual sampel |
| Lampiran hilang | Tinggi | Hitung path, cek file, laporkan missing |
| Nomor duplikat | Sedang | Unique + sequence; migrasi nomor lama apa adanya |
| Role scope melebar | Sedang | Laporkan jumlah role_kategori; uji akses |
| Cutover dengan data baru | Tinggi | Maintenance window/read-only |
| Tidak ada backup | Tinggi | Tabel lama utuh + dump asli; batasi cutover |

---

## 16. Checklist Sebelum Cutover

- [ ] Production dump berhasil direstore ke staging.
- [ ] Semua migration baru dijalankan di staging.
- [ ] Backfill selesai tanpa error.
- [ ] Laporan validasi memenuhi semua kriteria.
- [ ] Sampel 5 tiket + 5 order dicek manual.
- [ ] Semua lampiran terverifikasi ada di storage.
- [ ] Role-kategori production terisi (admin 5 IT, ipsrs 14 IPSRS).
- [ ] Login dan menu RBAC diuji.
- [ ] API v1 compatibility diuji.
- [ ] Notifikasi dan deep link diuji.
- [ ] Auto-confirm 24 jam diuji.
- [ ] Maintenance window disetujui.
- [ ] Tabel lama tetap utuh.
