# 00 — Project Baseline

## 1. Baseline Resmi

| Item | Nilai |
|---|---|
| Repository | `E:\kerjaan\Programming\helpdesk\helpdesk_app` |
| Production commit | `d8de3e9bd8617282398a30096a1931c4cf766613` |
| Short hash | `d8de3e9` |
| Commit message | `console clear` |
| Commit date | 2026-11-02 13:12:51 +07:00 |
| Author | Ahmadr127 |
| Remote | `https://github.com/Ahmadr127/helpdesk_api.git` |
| Branch kerja baru | `refactor/unified-pengajuan` dari `d8de3e9` |

## 2. Kondisi Development yang Ditemukan

| Item | Nilai |
|---|---|
| Branch saat audit | `refactor` |
| HEAD saat audit | `004047981f0a5731572a8a171b3cfc9aebf27074` |
| Commit message | `singkron tv` |
| Tanggal | 2026-11-04 16:28:05 +07:00 |
| `origin/refactor` | sama dengan HEAD |
| Branch `main` | `85b3d57` — `migrate null building id` |

### Hubungan production dan development

`0040479` **bukan ancestor** dari `d8de3e9`. Keduanya berada pada lineage yang divergen.

```text
main 85b3d57
      │
      ├── production d8de3e9  (2026-11-02, "console clear")
      │
      └── refactor 0040479    (2026-11-04, "singkron tv")
```

`git diff --stat 0040479 d8de3e9` menunjukkan 118 file berubah, +3094 / −5275. Production **tidak memiliki** fitur development berikut:

- `pengajuan_links` dan sinkronisasi TV.
- Pivot `ticket_penanggung_jawab` dan `order_perbaikan_penanggung_jawab`.
- `kategori_order_user` dan `kategori_order_department`.
- `audit_logs` dan permission `audit`.
- `order_perbaikan.closed_at`.
- `order_perbaikan.gedung`.
- Penghapusan kolom lama `unit_penerima`, `kode_inventaris`, `nama_barang`, `unit_proses_id` dari order.

**Konsekuensi:** implementasi dimulai dari production `d8de3e9`, bukan dengan mencampur seluruh branch `refactor`. Fitur development yang masih dibutuhkan ditambahkan kembali melalui task baru dan diuji terhadap data production.

## 3. Working Tree Saat Audit

| File | Status | Isi |
|---|---|---|
| `README.md` | Modified | Menambah perintah `php artisan queue:work --queue=notifications` |
| `app/Http/Controllers/Admin/AdminController.php` | Modified | Hanya menghapus 3 komentar, tidak ada perubahan logic |
| `docs/DATABASE-DESIGN.md` | Untracked | Dokumen analisis sesi sebelumnya |

Tidak ada staged changes. Perubahan lokal tidak menjadi bagian baseline dan harus disimpan sebagai patch/stash sebelum branch kerja baru dibuat.

## 4. Kondisi Database

### 4.1 Database lokal saat audit

| Item | Nilai |
|---|---|
| Engine | PostgreSQL 14.5 |
| Database | `db_helpdesk` |
| Host | `127.0.0.1:5432` |
| User | `postgres` |
| Jumlah tabel | 38 |
| Ukuran | 2.20 MB |
| Migration | Semua `Ran`, tidak ada pending |
| Batch terakhir | 7 — `2026_11_05_000005_create_pengajuan_links_table`, `2026_11_05_000003_backfill_order_photo_arrays` |

Data lokal saat audit: 4 users, 7 tickets, 17 order, 1 pengajuan_link. Ini **bukan** data production.

### 4.2 Dump production

| Item | Nilai |
|---|---|
| File | `E:\kerjaan\Programming\helpdesk\helpdesk_app\db_helpdesk.sql` |
| Ukuran | 3,147,791 bytes |
| Versi dump | PostgreSQL 17.6 |
| Jumlah tabel | 33 |
| Migration | 48 |

Data production: 342 users, 16 tickets, 7 order, 17 histori, 16 ticket photos, 14 kategori order, 5 kategori SIRS, 536 notifications, 29 device tokens, 292 failed jobs, 0 feedback, 0 report.

### 4.3 Masalah restore

Dump dibuat dengan PostgreSQL 17.6 dan mengandung:

- `\restrict` / `\unrestrict`
- GUC `transaction_timeout` (tidak dikenal PostgreSQL 14)

Restore langsung ke PostgreSQL 14.5 akan gagal. Pilihan:

1. **Rekomendasi:** sediakan PostgreSQL 17.x untuk database development yang identik dengan production.
2. Jika tetap memakai PostgreSQL 14.5, preprocessing dump:
   - hapus baris `\restrict` / `\unrestrict`;
   - hapus `SET transaction_timeout`;
   - pastikan tidak ada fitur PG17 lain yang dipakai.
3. Jangan mengedit dump asli; simpan hasil preprocessing sebagai `db_helpdesk.pg14.sql`.

### 4.4 Backup wajib sebelum restore

```powershell
# Backup database lokal saat ini
pg_dump -U postgres -Fc -f "backup_before_restore_$(Get-Date -Format yyyyMMdd_HHmmss).dump" db_helpdesk
```

Restore hanya boleh dilakukan setelah backup berhasil dan diverifikasi.

## 5. Environment Aplikasi

| Item | Nilai |
|---|---|
| Laravel | 12.10.2 |
| PHP | 8.3.15 |
| `APP_ENV` | local |
| `APP_DEBUG` | true |
| `APP_URL` | `http://127.0.0.1:8000` |
| Timezone | Asia/Jakarta |
| Queue | `notifications` |

## 6. Baseline Testing

Audit sebelumnya mencatat suite 149 test lulus / 511 assertion pada branch development. Angka ini **belum menjadi bukti baseline production**. Setelah restore database production, jalankan ulang suite dan catat hasil aktual di `09-ai-handoff.md`.

## 7. Aturan Baseline

1. Jangan mengubah migration yang sudah pernah dijalankan di production.
2. Semua perubahan skema memakai migration baru.
3. Jangan menjalankan `migrate:fresh`, `db:wipe`, atau drop database pada environment production.
4. Jangan melakukan reset/checkout/rebase yang membuang pekerjaan lokal.
5. Tabel lama diarsipkan, bukan langsung di-drop.
6. Semua perubahan harus dapat ditelusuri ke task ID di `06-implementation-tasks.md`.

## 8. Status

| Task | Status | Bukti |
|---|---|---|
| T-BASE-001 — Audit baseline git & DB | COMPLETED | Dokumen ini |
| T-BASE-002 — Backup DB lokal | NOT_STARTED | — |
| T-BASE-003 — Restore dump production | NOT_STARTED | — |
| T-BASE-004 — Buat branch kerja & tag | NOT_STARTED | — |
