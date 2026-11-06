# 09 — AI Handoff

Dokumen ini adalah titik masuk bagi model AI atau engineer berikutnya. Baca bersama `README.md`, `00-project-baseline.md`, dan `06-implementation-tasks.md`.

## 1. Status Terakhir

| Item | Nilai |
|---|---|
| Tanggal handoff | 2026-11-06 |
| Baseline production | `d8de3e9bd8617282398a30096a1931c4cf766613` |
| Branch kerja | `refactor/unified-pengajuan` (disarankan; belum tentu dibuat) |
| Development HEAD saat audit | `004047981f0a5731572a8a171b3cfc9aebf27074` |
| Database lokal | `db_helpdesk` (PG14.5) — **belum** di-restore dari dump production pada saat dokumen ini dibuat |
| Dump production | `db_helpdesk.sql` (PG17.6, 3.1 MB, 33 tabel) |
| Kode aplikasi | **Belum diubah** — task ini masih tahap dokumentasi |
| Migration baru | **Belum dibuat** |
| Test | **Belum dijalankan** |

## 2. Dokumen yang Sudah Selesai

| File | Status | Isi |
|---|---|---|
| `README.md` | ACTIVE | Tujuan, baseline, keputusan, status |
| `00-project-baseline.md` | COMPLETED (evidence) | Commit, repo, DB, gap prod/dev |
| `01-current-architecture.md` | COMPLETED (audit) | Arsitektur lama, dependency map, bug, RBAC |
| `02-target-database-design.md` | DRAFT | ERD, DDL, index, constraint |
| `03-rbac-redesign.md` | DRAFT | Role, permission, assignment, scope |
| `04-data-migration-plan.md` | DRAFT | Mapping, status, TV, validasi, rollback |
| `05-business-logic-refactor.md` | DRAFT | Service, controller, API, UI, notifikasi |
| `06-implementation-tasks.md` | DRAFT | Task T-BASE/T-DB/T-RBAC/T-MIG/T-APP/T-API/T-TEST/T-CUT |
| `07-testing-checklist.md` | COMPLETED (dokumen) | Unit, feature, API, migrasi, regression |
| `08-cutover-and-rollback.md` | COMPLETED (dokumen) | Setup production, cutover, rollback tanpa backup baru |
| `09-ai-handoff.md` | ACTIVE | Dokumen ini |

## 3. Keputusan yang Sudah Disetujui

| ID | Keputusan |
|---|---|
| D-01 | Baseline production `d8de3e9`. |
| D-02 | Perombakan lokal diperbolehkan; task setup data production wajib ada. |
| D-03 | Restore `db_helpdesk.sql` ke database lokal env saat ini. |
| D-04 | Satu user satu role (`users.role`). |
| D-05 | Role > permission; permission hanya untuk menu/feature gate. |
| D-06 | Role yang masih dipakai tidak dihapus keras; soft delete + blokir. |
| D-07 | Satu tabel kategori dengan `jenis_id` (IT/IPSRS). |
| D-08 | Assignment role-kategori adalah core PJ, scope data, dan notifikasi. |
| D-09 | Status mengikuti satu logic saat ini; tidak ada status keseluruhan terpisah. |
| D-10 | Nomor lama dipertahankan; nomor baru per jenis kategori. |
| D-11 | Order hanya untuk kategori non-IT. |
| D-12 | `kategori_order_user` dipetakan ke role user masing-masing. |
| D-13 | Tidak ada TV dua domain di arsitektur baru; `pengajuan_links` dan sinkronisasi TV dideprecate. |
| D-14 | Tabel lama diarsipkan sebagai `*_legacy`, tidak di-drop. |
| D-15 | Tidak ada langkah backup database baru; rollback memakai tabel lama + dump yang ada. |

## 4. Task yang Belum Selesai

Semua task implementasi di `06-implementation-tasks.md` berstatus `NOT_STARTED` atau `BLOCKED`. Prioritas berikutnya:

1. **T-BASE-002** — restore `db_helpdesk.sql` ke PostgreSQL lokal. Dump PG17.6 harus diproses agar kompatibel dengan PG14.5 (hapus/ubah `\restrict` dan `transaction_timeout`), atau gunakan PostgreSQL 17.
2. **T-BASE-003** — siapkan test harness dan database test.
3. **T-BASE-004** — selesaikan audit 98 Blade dan kontrak Flutter/Postman (belum diverifikasi baris per baris).
4. **T-DB-001..005** — migration struktur target + index + constraint.
5. **T-RBAC-001..006** — role CRUD, permission CRUD, assignment role-kategori, seed production.
6. **T-MIG-001..010** — preflight, migrasi tiket/order, histori, pesan, lampiran, PJ, validasi.
7. **T-APP-001..014** — model, service, workflow, web, view, dashboard, export, scheduler, audit, UI role.
8. **T-API-001..004** — kontrak v1, adapter, FCM/deep link, v2 opsional.
9. **T-TEST-001..005** — unit, feature, API, migrasi, regression.
10. **T-CUT-001..005** — rehearsal, persiapan, cutover, validasi, arsip.

## 5. Pertanyaan yang Belum Terjawab

Tidak ada pertanyaan baseline yang memblokir; baseline sudah dikonfirmasi. Namun keputusan implementasi berikut masih perlu konfirmasi saat task terkait dimulai:

1. Format persis nomor baru per jenis: `T-ddmm-seq` untuk IT dan `OP/RTG/MTC-YYYYMMDDseq` untuk IPSRS — konfirmasi prefix final.
2. Apakah permission `order.view_all` dipertahankan/diperbaiki atau dihapus.
3. Apakah `dashboard.it`/`dashboard.ipsrs` tetap menjadi gate menu, atau diganti permission per jenis.
4. Apakah direct permission user tetap additive (rekomendasi: ya).
5. Apakah role `user` yang tidak punya assignment kategori boleh tetap membuat pengajuan (rekomendasi: ya, hanya melihat milik sendiri).
6. Apakah data `kategori_order_user` yang menunjuk user ber-role `user` dianggap orphan atau dipetakan ke role khusus.
7. Apakah status `pending`/`closed` tiket lama yang tidak konsisten perlu dikoreksi saat migrasi atau dipertahankan sebagai `pending_confirmation`.
8. Apakah API v1 wajib dipertahankan untuk seluruh endpoint atau hanya endpoint yang dipakai Flutter.
9. Apakah tabel lama di-rename atau tetap nama lama sampai contract phase.
10. Apakah maintenance window production tersedia dan kapan.

## 6. File yang Sedang Dikerjakan

Tidak ada file kode yang sedang diubah. Dokumen yang baru dibuat:

- `docs/refactor-unified-pengajuan/02-target-database-design.md`
- `docs/refactor-unified-pengajuan/03-rbac-redesign.md`
- `docs/refactor-unified-pengajuan/04-data-migration-plan.md`
- `docs/refactor-unified-pengajuan/05-business-logic-refactor.md`
- `docs/refactor-unified-pengajuan/06-implementation-tasks.md`
- `docs/refactor-unified-pengajuan/07-testing-checklist.md`
- `docs/refactor-unified-pengajuan/08-cutover-and-rollback.md`
- `docs/refactor-unified-pengajuan/09-ai-handoff.md`

## 7. Aturan untuk Model AI Berikutnya

1. Jangan mengubah migration lama yang sudah dijalankan.
2. Jangan membuat asumsi bisnis baru; catat pertanyaan di dokumen ini.
3. Jangan mengubah alur konfirmasi, auto-confirm 24 jam, penolakan, hak edit/hapus, prioritas, penerima notifikasi, atau aturan laporan tanpa persetujuan.
4. Jangan menghapus tabel/kolom lama sebelum contract phase.
5. Gunakan migration baru + expand-contract.
6. Setiap perubahan harus dapat ditelusuri ke task ID.
7. Perbarui status task dan dokumen ini setiap menyelesaikan kelompok task.
8. `COMPLETED` butuh kode + test lokal; `VERIFIED` butuh bukti test di staging dengan data production copy.
9. Jangan mengklaim selesai hanya karena halaman terbuka.
10. Jika menemukan bug di luar scope, buat task baru dan tanyakan prioritasnya.

## 8. Langkah Pertama yang Disarankan

```text
1. Baca 00, 01, 02, 03, 04, 05, 06.
2. Jalankan T-BASE-002: restore dump production ke PostgreSQL lokal.
3. Jalankan T-BASE-003: siapkan database test dan test harness.
4. Jalankan T-BASE-004: audit Blade/Flutter/Postman.
5. Mulai T-DB-001 dan T-DB-002.
6. Setelah struktur siap, lanjut T-RBAC dan T-MIG.
```

Jangan memulai T-APP sebelum T-DB dan T-MIG-001 (preflight) selesai.
