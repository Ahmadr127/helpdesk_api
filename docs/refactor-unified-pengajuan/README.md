# Refactor Unified Pengajuan — Helpdesk RS Azra

## Tujuan

Menggabungkan dua domain pengajuan yang saat ini terpisah:

- **Tiket IT / SIRS** — tabel `tickets`
- **Order Perbaikan / IPSRS** — tabel `order_perbaikan`

menjadi **satu arsitektur pengajuan**:

- satu tabel transaksi utama: **`pengajuan`**
- satu master kategori: **`kategori_pengajuan`** dengan `jenis_id`
- satu mekanisme penugasan berbasis **role → kategori**
- satu mekanisme histori, pesan, lampiran, penomoran, notifikasi, audit, dan auto-confirm

Perubahan ini **bukan pembuatan aplikasi baru**. Aplikasi sudah berjalan dengan data produksi. Semua perubahan wajib menjaga data lama, nomor dokumen lama, histori, lampiran, perilaku bisnis yang disetujui, dan kompatibilitas API Flutter selama masa transisi.

## Baseline

| Item | Nilai |
|---|---|
| Production commit | `d8de3e9bd8617282398a30096a1931c4cf766613` (`d8de3e9`) |
| Production commit message | `console clear` |
| Production commit date | 2026-11-02 13:12:51 +07:00 |
| Development HEAD saat audit | `004047981f0a5731572a8a171b3cfc9aebf27074` (`0040479`) |
| Branch kerja | `refactor/unified-pengajuan` — dibuat dari `d8de3e9` |
| Database produksi | dump `db_helpdesk.sql` (PostgreSQL 17.6, 33 tabel) |
| Database lokal | `db_helpdesk` (PostgreSQL 14.5) — akan diganti salinan prod |
| Status implementasi | **Belum ada kode/migration yang diubah**; dokumen task selesai |

> `0040479` (branch `refactor`) **bukan ancestor** dari `d8de3e9`. Keduanya divergen. `0040479` hanya referensi fitur yang belum ada di production (TV link, pivot PJ, audit log, `closed_at`, `gedung`). Baseline implementasi adalah **production `d8de3e9`**, lalu fitur yang masih diperlukan ditambahkan kembali secara sadar melalui task baru.

## Keputusan yang Sudah Disetujui

| ID | Keputusan |
|---|---|
| D-01 | Baseline production: `d8de3e9`; branch kerja baru dari commit ini. |
| D-02 | Perombakan lokal diperbolehkan. Task setup data production wajib disediakan. |
| D-03 | Database lokal akan di-restore dari `db_helpdesk.sql` (salinan production). |
| D-04 | Satu user hanya memiliki **satu role** (`users.role`). |
| D-05 | **Role > permission.** Permission hanya untuk tampilan menu/feature gate, bukan penentu scope data. |
| D-06 | Role yang masih dipakai tidak dihapus keras; gunakan soft delete dan blokir penghapusan jika masih terpakai. |
| D-07 | Satu tabel kategori dengan `jenis_id` (IT / IPSRS). Tidak ada dua master kategori terpisah. |
| D-08 | Assignment **role → kategori** adalah inti penentuan PJ, scope data, dan penerima notifikasi. |
| D-09 | Alur status mengikuti satu logic saat ini; tidak ada status keseluruhan terpisah. |
| D-10 | Nomor lama dipertahankan. Nomor baru dibuat berdasarkan jenis kategori. |
| D-11 | Order perbaikan hanya untuk kategori **non-IT**. Kategori IT tidak membuat order. |
| D-12 | Assignment `kategori_order_user` lama dipetakan ke role user masing-masing. |
| D-13 | Tidak ada mekanisme TV dua domain pada arsitektur baru. `pengajuan_links` dan sinkronisasi TV dideprecate. |
| D-14 | Tabel lama diarsipkan sebagai `*_legacy`, tidak langsung di-drop. |
| D-15 | Tidak ada langkah backup database baru; rollback memakai tabel lama yang utuh + dump production yang sudah ada. |

## Ruang Lingkup

### Termasuk

- Desain database target dan migrasi data production.
- Refactor RBAC: role CRUD, permission CRUD, role-kategori assignment.
- Refactor model, service, controller, request, middleware, route, resource.
- Refactor notifikasi, audit, scheduler, penomoran, lampiran.
- Kompatibilitas API v1 selama transisi + API v2.
- Task setup data di production.
- Testing dan cutover/rollback.

### Tidak termasuk tanpa persetujuan baru

- Perubahan istilah/tampilan yang tidak terkait restrukturisasi.
- Perubahan alur bisnis di luar pemetaan status yang disepakati.
- Penghapusan fitur yang masih dipakai.
- Perubahan kontrak Flutter yang memutus versi lama.
- Drop tabel/kolom lama sebelum masa stabil selesai.

## Status Dokumentasi

| File | Status | Isi |
|---|---|---|
| `README.md` | ACTIVE | Tujuan, baseline, keputusan, status |
| `00-project-baseline.md` | COMPLETED (evidence) | Commit, repo, DB, gap prod/dev |
| `01-current-architecture.md` | COMPLETED (audit) | Arsitektur lama, dependency map, bug, RBAC |
| `02-target-database-design.md` | DRAFT — menunggu review | ERD, DDL, index, constraint |
| `03-rbac-redesign.md` | DRAFT — menunggu review | Role, permission, assignment, scope |
| `04-data-migration-plan.md` | DRAFT — menunggu review | Mapping, status, TV, validasi, rollback |
| `05-business-logic-refactor.md` | DRAFT — menunggu review | Service, controller, API, UI, notifikasi |
| `06-implementation-tasks.md` | DRAFT — task list | Task T-BASE/T-DB/T-RBAC/T-MIG/T-APP/T-API/T-TEST/T-CUT |
| `07-testing-checklist.md` | COMPLETED (dokumen) | Unit, feature, API, migrasi, regression |
| `08-cutover-and-rollback.md` | COMPLETED (dokumen) | Setup production, cutover, rollback tanpa backup baru |
| `09-ai-handoff.md` | ACTIVE | Handoff untuk model AI berikutnya |

Legenda status task: `NOT_STARTED`, `IN_PROGRESS`, `BLOCKED`, `COMPLETED`, `VERIFIED`.

## Cara Melanjutkan Pekerjaan

1. Baca `00-project-baseline.md` dan `09-ai-handoff.md` terlebih dahulu.
2. Jangan mengubah migration lama yang sudah pernah dijalankan.
3. Buat branch kerja dari baseline `d8de3e9`.
4. Jalankan task sesuai urutan di `06-implementation-tasks.md`.
5. Setiap task harus diperbarui statusnya di file task dan `09-ai-handoff.md`.
6. `COMPLETED` hanya boleh diberikan setelah kode ada dan test terkait lulus.
7. `VERIFIED` hanya boleh diberikan setelah ada bukti test/validasi yang dapat diperiksa.
8. Jangan menganggap fitur tidak dipakai hanya karena tidak ditemukan di satu controller. Cek route, view, resource, export, notifikasi, dan job.
9. Jika menemukan keputusan bisnis baru yang belum dijawab, catat di `09-ai-handoff.md` dan ajukan pertanyaan. Jangan memilih sendiri.

## Struktur Dokumen

```text
docs/refactor-unified-pengajuan/
├── README.md
├── 00-project-baseline.md
├── 01-current-architecture.md
├── 02-target-database-design.md
├── 03-rbac-redesign.md
├── 04-data-migration-plan.md
├── 05-business-logic-refactor.md
├── 06-implementation-tasks.md
├── 07-testing-checklist.md
├── 08-cutover-and-rollback.md
└── 09-ai-handoff.md
```

## Prinsip Kerja

- **Read before write.** Verifikasi kode dan data sebelum menyimpulkan.
- **No silent behavior change.** Perubahan perilaku harus tercatat di task dan disetujui.
- **Expand and contract.** Tambah struktur baru, backfill, validasi, cutover, baru arsipkan yang lama.
- **Idempotent migration.** Migrasi harus aman dijalankan ulang.
- **Legacy tables stay.** Rollback mengandalkan tabel lama yang tidak di-drop, bukan asumsi `migrate:rollback`.
- **Evidence over claims.** Status `VERIFIED` memerlukan laporan validasi.
