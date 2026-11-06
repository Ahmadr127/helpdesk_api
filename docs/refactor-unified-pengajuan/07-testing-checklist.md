# 07 — Testing Checklist

Dokumen ini adalah kontrak pengujian untuk refactor unified pengajuan. Tidak ada task yang boleh berstatus `COMPLETED` hanya karena kode sudah ditulis. Status `VERIFIED` hanya diberikan setelah bukti eksekusi test dicatat.

## 1. Prinsip

1. Setiap task di `06-implementation-tasks.md` wajib memiliki minimal satu test atau bukti validasi.
2. Test dijalankan pada database hasil restore `db_helpdesk.sql` (production copy), bukan database dev lama.
3. Test tidak boleh bergantung pada kategori/role yang di-hardcode. Data uji dibuat melalui factory/seeder.
4. Test migrasi harus dapat dijalankan ulang tanpa menggandakan data.
5. Test API harus memakai kontrak yang sama dengan Flutter v1.
6. Test UI minimal memastikan halaman utama, filter, detail, form, dan aksi status tetap dapat dibuka.
7. Setiap bug yang ditemukan saat test harus dicatat sebagai task baru atau regression test.

## 2. Jenis Test

| Jenis | Tool | Cakupan |
|---|---|---|
| Unit | PHPUnit / Pest | Service, scope, numbering, status mapping |
| Feature web | Laravel HTTP test | Route, middleware, controller, Blade response |
| Feature API | Laravel HTTP test + Sanctum | Kontrak v1/v2, authorization, resource |
| Integration | PHPUnit + DB transaksi | Service + model + histori + notifikasi + audit |
| Migration | Artisan command/test DB | Backfill, idempotensi, orphan, row count |
| Regression | PHPUnit + smoke test | Perilaku lama yang tidak boleh berubah |
| Performance | EXPLAIN + seed data | Query list/dashboard/filter |
| Security | Feature test | IDOR, permission fail-closed, upload, mass assignment |

## 3. Test Unit

### T-TEST-001 — Unit test service

- [ ] `PengajuanNumberService` menghasilkan nomor unik untuk jenis IT dan IPSRS.
- [ ] Nomor mengikuti prefix/format per jenis.
- [ ] Dua pemanggilan bersamaan tidak menghasilkan nomor yang sama (simulasi row lock/sequence).
- [ ] Nomor lama tetap dapat dicari melalui `legacy_map`/`pengajuan_nomor_legacy`.
- [ ] `PengajuanScopeService` mengembalikan hanya kategori yang di-assign ke role user.
- [ ] User dengan role tanpa assignment tidak melihat data kategori lain.
- [ ] `view_all` (jika dipertahankan) membuka seluruh data.
- [ ] Owner selalu dapat melihat pengajuan sendiri.
- [ ] `PengajuanWorkflowService` hanya mengizinkan transisi yang sah.
- [ ] Aksi `start`, `close`, `confirm`, `reject`, `assign` menolak user di luar scope/penugasan.
- [ ] `autoConfirmStale(24)` hanya memproses status `pending_confirmation` dan `closed_at <= now-24h`.
- [ ] Auto-confirm menulis aktor `system`, bukan `null` ke kolom NOT NULL.
- [ ] `PengajuanNotificationService` mengirim ke role-kategori yang tepat, bukan seluruh admin.
- [ ] Notifikasi tidak dikirim ke aktor yang melakukan aksi.
- [ ] `AttachmentService` menyimpan path, mime, size, dan kind dengan benar.
- [ ] Attachment lama tetap dapat diakses setelah migrasi.

### T-TEST-005 — Race condition dan transaksi

- [ ] Dua request create bersamaan tidak menghasilkan nomor duplikat.
- [ ] Kegagalan di tengah transaksi tidak meninggalkan pengajuan/histori/lampiran setengah jadi.
- [ ] Auto-confirm yang dijalankan dua kali tidak menggandakan histori.
- [ ] Migrasi yang dijalankan dua kali tidak menggandakan `pengajuan`.
- [ ] Sinkronisasi/penugasan berulang bersifat idempotent.

## 4. Feature Test Web

### T-TEST-002 — Feature web

- [ ] User membuat pengajuan kategori IT → berhasil, nomor IT, status `open`, track IT.
- [ ] User membuat pengajuan kategori IPSRS → berhasil, nomor IPSRS, status `open`, detail order.
- [ ] Kategori non-IT tidak membuat track/order IT.
- [ ] Kategori IT tidak membuat order IPSRS.
- [ ] User hanya melihat pengajuan milik sendiri di halaman `myorder`/`myticket`.
- [ ] Admin IT melihat pengajuan kategori yang di-assign ke role-nya.
- [ ] Admin IPSRS melihat pengajuan kategori yang di-assign ke role-nya.
- [ ] Admin tanpa assignment tidak melihat data di luar scope.
- [ ] Admin dapat start/close/confirm/reject sesuai aturan lama.
- [ ] User dapat mengonfirmasi selesai dan menolak penyelesaian sesuai aturan lama.
- [ ] Edit hanya untuk pemilik dan status `open`.
- [ ] Hapus hanya untuk pemilik dan status `open`.
- [ ] Histori tercatat untuk setiap perubahan status.
- [ ] Lampiran tampil dan dapat diunduh.
- [ ] Popup konfirmasi tetap muncul untuk pengajuan `pending_confirmation`.
- [ ] Filter status, prioritas, tanggal, kategori, dan pencarian berfungsi.
- [ ] Statistik dashboard sama dengan perhitungan lama (kecuali bug yang disetujui diperbaiki).
- [ ] Export Excel menghasilkan baris dan kolom yang sama dengan versi lama.
- [ ] Halaman role/permission dapat dibuka hanya oleh pemegang permission terkait.
- [ ] Role baru dapat dibuat dari UI tanpa mengubah kode.
- [ ] Permission baru dapat dibuat dan ditugaskan ke role.
- [ ] Role dapat diberi kategori; perubahan langsung memengaruhi scope.
- [ ] Role yang masih dipakai tidak dapat dihapus.
- [ ] Permission inti tidak dapat dihapus.
- [ ] Audit log mencatat create/update/delete role, permission, assignment, dan pengajuan.

## 5. Feature Test API

### T-TEST-003 — API contract

- [ ] `GET /api/tickets` v1 tetap mengembalikan field lama yang dipakai Flutter.
- [ ] `GET /api/order-perbaikan` v1 tetap mengembalikan field lama.
- [ ] Detail tiket/order v1 tetap menyertakan `id` lama atau `legacy_id`.
- [ ] Create tiket/order v1 tetap menerima payload lama.
- [ ] Update status/confirm/reject/start v1 tetap berfungsi.
- [ ] Pagination meta tetap sama.
- [ ] Error 401/403/422 tetap memiliki format `success/message`.
- [ ] Resource baru tidak membocorkan data di luar scope.
- [ ] Endpoint lookup kategori mengembalikan kategori sesuai jenis.
- [ ] Endpoint lookup role/kategori (jika ada) hanya untuk admin.
- [ ] Deep link FCM tetap membuka detail yang benar.
- [ ] Token Sanctum lama tetap valid setelah refactor.
- [ ] API v2 (jika dibuat) tidak mengubah v1.

## 6. Test Migrasi

### T-TEST-004 — Migration

Preflight:

- [ ] Row count sumber dicatat: users, tickets, orders, ticket_photos, histories, kategori, role, pivot.
- [ ] Orphan terdeteksi: foto tanpa tiket, histori tanpa order, PJ tanpa user, kategori tanpa master.
- [ ] Duplikasi kategori berdasarkan nama ternormalisasi terdeteksi.
- [ ] Nomor duplikat terdeteksi.
- [ ] Pasangan TV (dev only) terdeteksi; production tidak memiliki TV.

Eksekusi:

- [ ] Migrasi kategori membuat satu baris `kategori_pengajuan` per kategori unik.
- [ ] Relasi jenis benar: kategori IT → jenis IT; kategori IPSRS → jenis IPSRS.
- [ ] Setiap tiket menjadi satu `pengajuan` + track IT.
- [ ] Setiap order menjadi satu `pengajuan` + track IPSRS.
- [ ] Tidak ada pengajuan ganda untuk pasangan TV (dev).
- [ ] Histori tiket (`admin_responses`) dan order (`order_perbaikan_histories`) pindah tanpa kehilangan urutan/timestamp.
- [ ] `user_replies` menjadi pesan/histori dengan tipe yang benar.
- [ ] Semua foto tiket dan order menjadi `pengajuan_lampiran`.
- [ ] Lampiran JSON `order_perbaikan.foto` dan `histories.lampiran` diekstrak, bukan disimpan sebagai string.
- [ ] PJ tiket/order menjadi `pengajuan_penugasan`.
- [ ] `kategori_order_user` menjadi `role_kategori` sesuai keputusan D-12.
- [ ] Nomor lama tersimpan di `pengajuan_nomor_legacy`.
- [ ] `legacy_map` mengisi semua sumber.
- [ ] `migration_orphans` berisi data yang tidak dapat dipetakan.

Validasi:

- [ ] Jumlah pengajuan = jumlah tiket + order - pasangan TV yang digabung.
- [ ] Jumlah track IT = jumlah tiket.
- [ ] Jumlah track IPSRS = jumlah order.
- [ ] Jumlah histori = histori order + entri status tiket yang dimigrasikan.
- [ ] Jumlah lampiran = foto tiket + path foto order + lampiran histori.
- [ ] Tidak ada FK yatim.
- [ ] Tidak ada nomor duplikat.
- [ ] Tidak ada pengajuan tanpa track.
- [ ] Tidak ada track tanpa status valid.
- [ ] Semua pengajuan dapat ditelusuri ke `legacy_map`.
- [ ] Migrasi dijalankan ulang → row count tidak bertambah.

## 7. Regression Test

- [ ] Login/logout web dan API.
- [ ] Register (jika masih aktif).
- [ ] Dashboard user, IT, IPSRS.
- [ ] Halaman tiket: index, create, show, edit, confirm, reply.
- [ ] Halaman order: index, create, show, edit, konfirmasi selesai.
- [ ] Halaman myticket/myorder.
- [ ] Halaman master data: kategori, jenis, role, permission, user.
- [ ] Halaman laporan dan export.
- [ ] Halaman feedback, knowledge, FAQ, notification.
- [ ] FCM monitoring dan test send.
- [ ] Scheduler `pengajuan:auto-confirm` dengan `--dry-run`.
- [ ] Audit log tampil dan dapat difilter.
- [ ] Semua route lama (URL) tidak 404 kecuali yang memang dihapus dengan persetujuan.
- [ ] Semua nama route lama tetap ada atau dialihkan.
- [ ] Bahasa/istilah UI tidak berubah tanpa persetujuan.

## 8. Security Test

- [ ] User A tidak dapat melihat/mengubah pengajuan User B (IDOR).
- [ ] Admin kategori A tidak dapat melihat kategori B.
- [ ] User tanpa permission tidak dapat membuka route (fail-closed).
- [ ] Direct permission tidak bocor ke user lain.
- [ ] Role deletion tidak meninggalkan user tanpa akses.
- [ ] Permission deletion tidak mengunci admin.
- [ ] Upload menolak ekstensi/MIME/ukuran yang tidak sah.
- [ ] Mass assignment tidak dapat mengubah `status`, `role`, `created_by`, `nomor`.
- [ ] API tidak membocorkan data melalui filter `per_page`/`page`/`search`.
- [ ] CSRF web tetap aktif.
- [ ] Sanctum token lama tidak dapat mengakses endpoint admin baru tanpa permission.

## 9. Performance Test

- [ ] `EXPLAIN ANALYZE` untuk daftar pengajuan per role/kategori.
- [ ] Query daftar memakai index `(kategori_id, status, created_at)` atau yang setara.
- [ ] Tidak ada N+1 pada relasi kategori, pemohon, track, lampiran, penugasan.
- [ ] Dashboard stats < 500 ms pada data production (342 user, 16 tiket, 7 order; uji juga dengan seed 10.000 baris).
- [ ] Export 10.000 baris tidak kehabisan memori.
- [ ] Auto-confirm batch 1.000 baris selesai dalam waktu wajar.
- [ ] Pencarian teks tidak melakukan full table scan tanpa index yang disepakati.

## 10. Bukti dan Pelaporan

Setiap eksekusi test harus mencatat:

```yaml
task_id: T-TEST-00x
environment: local|staging|production
database: db_helpdesk (restored d8de3e9 dump)
command: php artisan test --filter=...
result: passed|failed
counts:
  tests: 0
  assertions: 0
  failures: 0
evidence: path/to/report-or-log
```

Status task:

- `COMPLETED` — kode dan test ada, test lulus di lokal.
- `VERIFIED` — test lulus di staging dengan data production copy, plus validasi migrasi.
- `BLOCKED` — menunggu keputusan bisnis atau environment.

## 11. Gate Cutover

Cutover tidak boleh dilakukan jika salah satu belum terpenuhi:

- [ ] T-TEST-004 lulus dengan laporan row count.
- [ ] T-TEST-003 lulus untuk kontrak v1.
- [ ] T-TEST-002 lulus untuk alur utama web.
- [ ] Tidak ada orphan kritis.
- [ ] Tidak ada duplikasi nomor.
- [ ] Rollback procedure sudah diuji di staging.
- [ ] Task setup data production sudah dijalankan dan diverifikasi.
