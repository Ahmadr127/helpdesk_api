# 08 — Cutover dan Rollback

## 1. Keputusan Dasar

Sesuai keputusan pemilik proyek:

- **Tidak ada langkah backup database baru** dalam prosedur ini.
- Rollback mengandalkan:
  1. tabel lama yang **tidak di-drop** dan tetap utuh sebagai `*_legacy`;
  2. dump production `db_helpdesk.sql` yang sudah ada sebagai referensi;
  3. kemampuan mengembalikan kode ke baseline `d8de3e9`.
- Tabel lama tidak boleh dihapus sampai masa stabil selesai.
- Cutover dilakukan setelah migrasi dan validasi lulus, bukan sebaliknya.

> **Risiko residual:** tanpa backup baru, data yang ditulis ke struktur baru setelah cutover tidak dapat dikembalikan ke struktur lama secara otomatis. Karena itu cutover harus berada pada maintenance window dan tabel lama harus tetap menerima data historis (read-only) sampai rollback window berakhir.

## 2. Strategi

```text
EXPAND → BACKFILL → VALIDATE → FREEZE → CUTOVER → MONITOR → CONTRACT
```

| Fase | Aktivitas | Rollback |
|---|---|---|
| Expand | Buat tabel/kolom/index baru; kode lama tetap jalan | Drop objek baru |
| Backfill | Isi data baru dari tabel lama; idempotent | Hentikan batch; data baru diabaikan |
| Validate | Bandingkan row count, FK, status, nomor, lampiran | Perbaiki mapping; ulangi batch |
| Freeze | Hentikan write ke tabel lama | Buka kembali akses lama |
| Cutover | Arahkan aplikasi ke `pengajuan` | Kembalikan kode + arahkan ke tabel lama |
| Monitor | Pantau error, notifikasi, export, API | Rollback kode |
| Contract | Arsipkan tabel lama sebagai `*_legacy` | Tabel lama masih ada |

## 3. Persiapan Production

### 3.1 Task setup data production

Task ini wajib diselesaikan sebelum cutover:

1. Pastikan dump production yang digunakan adalah `db_helpdesk.sql` dari commit `d8de3e9`.
2. Jalankan migrasi struktur baru (expand) di staging yang berisi salinan production.
3. Jalankan backfill seluruh data production.
4. Jalankan validasi dan simpan laporan.
5. Siapkan data master baru:
   - `jenis_pengajuan`: IT dan IPSRS.
   - `kategori_pengajuan`: 5 kategori IT + 14 kategori IPSRS, dipetakan ke jenis.
   - `role_kategori`: admin → 5 kategori IT; ipsrs → 14 kategori IPSRS.
   - `permissions`: 16 permission lama + permission baru yang disetujui.
   - `document_sequences`: inisialisasi dari nomor terakhir per jenis.
6. Pastikan user `admin` tetap memiliki role `admin` dan akses penuh.
7. Pastikan tidak ada role/kategori yatim.
8. Simpan laporan setup di `storage/app/refactor/`.

### 3.2 Checklist pra-cutover

- [ ] Branch/tag implementasi jelas; commit terakhir dicatat.
- [ ] Semua migration baru lulus di staging dengan dump production.
- [ ] Laporan validasi disetujui.
- [ ] Tidak ada orphan kritis.
- [ ] Tidak ada nomor duplikat.
- [ ] API v1 lulus contract test.
- [ ] Flutter versi lama masih dapat login, list, detail, create, update, confirm.
- [ ] Notifikasi FCM deep link diuji.
- [ ] Export Excel diuji.
- [ ] Scheduler auto-confirm diuji dengan `--dry-run`.
- [ ] Role/permission CRUD diuji.
- [ ] Maintenance window disetujui.
- [ ] Tim support tahu cara rollback.

## 4. Maintenance Window

Rekomendasi jendela: **di luar jam operasional**, dengan durasi 2–4 jam untuk production berukuran 342 user / 16 tiket / 7 order (waktu aktual diukur di staging).

Urutan:

1. Umumkan maintenance.
2. Aktifkan mode read-only atau hentikan write (tergantung kapasitas aplikasi).
3. Pastikan tidak ada job antre yang menulis ke tabel lama.
4. Jalankan migrasi expand.
5. Jalankan backfill batch.
6. Jalankan validasi.
7. Jika gagal → hentikan, buka akses lama, jangan cutover.
8. Jika lulus → deploy kode baru.
9. Smoke test.
10. Buka akses.
11. Monitor.

## 5. Langkah Cutover

### 5.1 Sebelum deploy

```bash
php artisan down --render="errors::503"
php artisan queue:restart
php artisan schedule:interrupt
```

Catat waktu, commit, dan jumlah row sebelum.

### 5.2 Migrasi struktur

```bash
php artisan migrate --force
```

Verifikasi:

```sql
SELECT COUNT(*) FROM jenis_pengajuan;
SELECT COUNT(*) FROM kategori_pengajuan;
SELECT COUNT(*) FROM pengajuan;
SELECT COUNT(*) FROM pengajuan_order_detail;
SELECT COUNT(*) FROM pengajuan_histori;
SELECT COUNT(*) FROM pengajuan_lampiran;
SELECT COUNT(*) FROM pengajuan_penugasan;
SELECT COUNT(*) FROM role_kategori;
```

### 5.3 Backfill

Jalankan command bertahap (dibuat pada task T-MIG-*):

```bash
php artisan refactor:backfill --stage=categories
php artisan refactor:backfill --stage=roles
php artisan refactor:backfill --stage=tickets
php artisan refactor:backfill --stage=orders
php artisan refactor:backfill --stage=histories
php artisan refactor:backfill --stage=attachments
php artisan refactor:backfill --stage=assignments
php artisan refactor:validate --report=storage/app/refactor/validation.json
```

Setiap stage harus idempotent dan mencatat checkpoint.

### 5.4 Deploy kode

- Deploy kode yang membaca/menulis `pengajuan`.
- Pertahankan route lama sebagai adapter.
- Aktifkan feature flag `unified_pengajuan=true`.
- Jangan hapus route/model lama pada hari yang sama.

### 5.5 Smoke test production

- [ ] Login admin/user.
- [ ] Dashboard IT dan IPSRS.
- [ ] List tiket lama tampil.
- [ ] List order lama tampil.
- [ ] Detail tiket/order lama tampil dengan histori dan lampiran.
- [ ] Create pengajuan IT baru.
- [ ] Create pengajuan IPSRS baru.
- [ ] Nomor baru sesuai jenis.
- [ ] Notifikasi diterima penerima yang benar.
- [ ] Update status dan konfirmasi.
- [ ] Export.
- [ ] Audit log.

### 5.6 Buka akses

```bash
php artisan up
```

Pantau log, queue, failed jobs, dan notifikasi selama minimal 1×24 jam.

## 6. Rollback

Rollback bukan `migrate:rollback` dan bukan pembalikan penggabungan data. Rollback adalah **kembali ke kode dan tabel lama**.

### 6.1 Trigger rollback

- Data pengajuan tidak lengkap/duplikat.
- Nomor duplikat atau hilang.
- Histori/lampiran hilang.
- API v1 rusak untuk Flutter.
- Admin kehilangan akses.
- Notifikasi salah penerima secara masif.
- Error rate di atas ambang yang disepakati.

### 6.2 Langkah rollback

1. Aktifkan maintenance.
2. Hentikan queue dan scheduler.
3. Deploy kode baseline `d8de3e9` (atau tag terakhir yang stabil).
4. Arahkan konfigurasi feature flag ke tabel lama.
5. Pastikan tabel lama masih utuh dan dapat ditulis.
6. Jalankan smoke test lama.
7. Buka akses.
8. Catat insiden dan data yang ditulis ke struktur baru selama cutover untuk rekonsiliasi manual.

### 6.3 Batasan rollback

- Pengajuan baru yang dibuat setelah cutover tidak otomatis kembali ke `tickets`/`order_perbaikan`.
- Histori yang sudah digabung tidak otomatis terurai.
- Karena itu, rollback window harus singkat dan write baru sebaiknya ditahan selama validasi awal.
- Tabel `*_legacy` tidak boleh di-drop sampai rollback window berakhir.

## 7. Pasca-Cutover

- Monitor error log, failed jobs, queue notifications.
- Bandingkan jumlah pengajuan baru vs lama setiap hari.
- Validasi nomor baru.
- Validasi penerima notifikasi.
- Validasi export.
- Kumpulkan feedback admin/user.
- Setelah masa stabil (disepakati, minimal 2 minggu), jalankan T-DB-006 untuk arsip tabel lama.
- Jangan drop tabel lama; rename menjadi `*_legacy` dan cabut akses aplikasi.

## 8. Komunikasi

- Sebelum: beri tahu downtime dan dampak.
- Saat cutover: status per 30 menit.
- Setelah: konfirmasi selesai dan kanal bantuan.
- Jika rollback: jelaskan dampak dan data yang perlu dicek ulang.
