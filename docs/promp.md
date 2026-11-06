# MASTER TASK — TOTAL DATABASE RESTRUCTURING & APPLICATION REFACTOR
## Helpdesk RS Azra — Unified Pengajuan Architecture

Kamu bertindak sebagai Senior Software Architect, Database Architect, Laravel Engineer, dan Technical Auditor.

Saya ingin melakukan perombakan menyeluruh terhadap struktur database dan logic aplikasi Helpdesk RS Azra.

**PENTING: Aplikasi ini sudah berjalan dan memiliki data produksi. Ini bukan proyek baru. Seluruh perubahan wajib mempertimbangkan kompatibilitas, integritas data, migrasi data lama, dan keberlangsungan fitur yang sudah berjalan.**

Dokumen analisis database dan logic yang saya berikan merupakan referensi awal untuk memahami kondisi sistem saat ini. Namun, kamu WAJIB memverifikasi kondisi repository dan database berdasarkan kode aktual sebelum menyusun rencana implementasi.

---

# PHASE 0 — KONFIRMASI BASELINE PROJECT (WAJIB)

Sebelum melakukan perubahan kode, migration, database, atau konfigurasi apa pun, lakukan pemeriksaan repository.

Periksa:

- Git branch aktif.
- Git commit HEAD.
- Git commit terakhir.
- Git working tree.
- File yang mengalami perubahan tetapi belum di-commit.
- Perubahan yang sudah ada setelah commit baseline.
- Migration terakhir yang sudah dijalankan.
- Kondisi struktur database aktual jika akses tersedia.

**JANGAN langsung memulai implementasi.**

Tanyakan kepada saya terlebih dahulu:

1. Commit hash mana yang akan dijadikan baseline pengerjaan?
2. Apakah baseline tersebut merupakan commit production yang sedang berjalan atau commit development?
3. Apakah terdapat perubahan lokal yang harus dipertahankan?
4. Apakah database yang digunakan untuk pengembangan merupakan salinan database production atau database development?

Jika saya belum memberikan commit yang dimaksud, berhenti pada tahap pemeriksaan dan minta konfirmasi.

Jangan melakukan reset, checkout, rebase, force push, atau membuang perubahan yang sudah ada.

Setelah baseline dikonfirmasi, seluruh analisis dan task harus mengacu pada commit tersebut.

---

# PHASE 1 — AUDIT TOTAL SISTEM (READ ONLY)

Lakukan audit menyeluruh terhadap:

- app/
- routes/
- database/migrations/
- database/seeders/
- resources/views/
- API Resources
- Form Requests
- Middleware
- Policies
- Services
- Models
- Controllers
- Jobs
- Notifications
- Observers
- Scheduler
- Flutter API contract jika tersedia.
- Seluruh logic yang berhubungan dengan role dan permission.

Jangan hanya membaca nama file. Baca implementasi dan hubungan antarfile.

Identifikasi:

1. Seluruh tabel dan relasi database.
2. Seluruh foreign key, unique constraint, index, dan CHECK constraint.
3. Model dan relasi Eloquent.
4. Seluruh endpoint web dan API.
5. Seluruh proses CRUD.
6. Seluruh proses create, update, delete, assign, approve, reject, start, complete, confirm.
7. Semua aturan akses user dan admin.
8. Semua proses notifikasi.
9. Semua proses audit log.
10. Semua scheduler dan background jobs.
11. Seluruh proses upload dan pengelolaan lampiran.
12. Semua dependensi yang mengacu pada tabel lama.

Buat dependency map agar setiap perubahan database dapat ditelusuri dampaknya terhadap aplikasi.

Jangan menganggap suatu fitur tidak digunakan hanya karena tidak ditemukan pada satu controller.

---

# PHASE 2 — TARGET DATABASE ARCHITECTURE

Saya ingin menggabungkan dua domain pengajuan:

- Tiket IT / SIRS.
- Order Perbaikan / IPSRS.

Keduanya harus menggunakan satu tabel utama bernama `pengajuan`.

Tidak boleh lagi terdapat dua tabel transaksi utama yang menyimpan proses pengajuan secara terpisah seperti `tickets` dan `order_perbaikan`.

## 2.1 Tabel utama pengajuan

Rancang satu tabel `pengajuan` yang mampu menampung kedua jenis proses secara dinamis.

Identifikasi atribut yang:

- Sama-sama dimiliki kedua jenis pengajuan.
- Hanya dimiliki pengajuan IT.
- Hanya dimiliki pengajuan IPSRS.
- Merupakan atribut tambahan yang berpotensi berubah berdasarkan kategori.
- Harus menjadi relasi ke tabel lain.
- Perlu dipertahankan sebagai snapshot.

Jangan langsung memindahkan semua kolom lama ke tabel baru.

Buat rancangan yang terstruktur, normal, memiliki foreign key, dan tidak menghasilkan banyak kolom nullable tanpa alasan.

Pertimbangkan penggunaan tabel pendukung untuk atribut khusus apabila memang dibutuhkan.

## 2.2 Master kategori

Saat ini terdapat dua kelompok kategori pengajuan.

Rancang satu sumber data kategori yang dapat membedakan kedua kelompok tersebut.

Kategori harus bersifat dinamis.

Jangan mengandalkan hardcode seperti:

- Nama kategori.
- Kode kategori.
- Nama unit.
- Nama modul.
- Kondisi khusus di controller.

Periksa kebutuhan pembeda kategori dan tentukan rancangan relasinya.

Saya ingin dapat menambah kategori baru tanpa harus membuat tabel transaksi baru atau menulis ulang logic berdasarkan nama kategori.

## 2.3 Tabel pendukung

Rancang tabel kecil yang diperlukan untuk mendukung tabel utama pengajuan.

Evaluasi kebutuhan:

- Riwayat status.
- Percakapan atau balasan.
- Lampiran dan foto.
- Penugasan.
- Relasi kategori.
- Audit.
- Nomor dokumen.
- Metadata tambahan jika benar-benar diperlukan.

Gunakan normalisasi database yang tepat.

Jangan membuat tabel tambahan hanya karena terlihat lebih fleksibel. Setiap tabel harus memiliki tanggung jawab yang jelas.

## 2.4 Relasi penugasan berdasarkan ROLE

Ini merupakan perubahan penting.

Sebelumnya terdapat penugasan PJ melalui relasi kategori ke user.

Saya ingin mengevaluasi dan merombak konsep tersebut agar penugasan dapat dilakukan melalui ROLE.

Konsep yang diinginkan:

- Satu role dapat menangani beberapa kategori pengajuan.
- Satu kategori pengajuan dapat ditangani beberapa role.
- Penambahan role baru tidak mengharuskan perubahan kode.
- Penambahan kategori baru tidak mengharuskan perubahan kode.
- User yang mempunyai role tersebut memperoleh akses sesuai aturan kategori yang ditugaskan.
- Penugasan role dan kategori harus dapat dikelola melalui CRUD.

Rancang relasi many-to-many antara role dan kategori pengajuan.

Evaluasi juga bagaimana user dengan lebih dari satu role akan diperlakukan apabila struktur RBAC saat ini masih menggunakan satu role per user.

**Jangan mengubah aturan tersebut secara sepihak.** Identifikasi konsekuensi dan tanyakan kepada saya jika diperlukan keputusan bisnis.

Bedakan dengan tegas:

1. Role sebagai identitas kelompok hak akses.
2. Permission sebagai hak menjalankan fitur.
3. Assignment kategori sebagai ruang lingkup pekerjaan.
4. Penugasan user tertentu pada sebuah pengajuan sebagai pelaksana aktual.

Jangan mencampurkan empat konsep tersebut.

---

# PHASE 3 — PEROMBAKAN TOTAL ROLE & PERMISSION

Saya menemukan kejanggalan pada CRUD role dan permission saat ini.

Saat ini proses penambahan role tidak berjalan sebagaimana mestinya.

Lakukan audit khusus terhadap:

- Model Role.
- Model Permission.
- RoleService.
- PermissionService.
- RoleController.
- PermissionController.
- FormRequest.
- Route dan middleware.
- Migration.
- Seeder.
- Pivot role_permissions.
- Relasi users.role.
- Relasi permission_user.
- Seluruh pemanggilan hasPermission().
- effectivePermissionSlugs().
- Pengecekan role langsung di controller dan service.

Cari akar masalah kenapa role baru tidak dapat ditambahkan.

Jangan sekadar memperbaiki error pada tombol tambah role.

Saya menginginkan sistem RBAC yang dapat dikembangkan secara dinamis.

Target:

1. CRUD role berjalan penuh.
2. CRUD permission berjalan penuh.
3. Role dapat memiliki banyak permission.
4. Permission dapat dimiliki banyak role.
5. Role dapat ditugaskan ke kategori pengajuan.
6. Role baru tidak perlu dimasukkan secara hardcode.
7. Penghapusan role tidak merusak data historis.
8. Perubahan permission langsung tercermin pada akses yang relevan.
9. Direct permission user tetap diperlakukan sesuai keputusan desain yang telah dikonfirmasi.
10. Middleware, Policy, API, dan UI menggunakan satu mekanisme otorisasi yang konsisten.

Audit seluruh hardcode seperti:

- role == admin
- role == ipsrs
- isAdminIT()
- isAdminUmum()
- dashboard.it
- dashboard.ipsrs

Jangan menghapus permission yang masih dibutuhkan tanpa analisis dependensi.

Buat matriks:

| Role | Permission | Kategori yang ditangani | Akses data |
|---|---|---|---|

Tentukan mekanisme efektif permission dan scope pengajuan secara konsisten.

---

# PHASE 4 — REFACTOR SELURUH LOGIC APLIKASI

Setelah struktur database target disetujui, seluruh logic aplikasi harus mengikuti struktur database baru.

Periksa dan sesuaikan:

- Models dan relasi.
- Services.
- Controllers.
- FormRequests.
- Policies.
- Middleware.
- Web routes.
- API routes.
- API Resources.
- Blade views.
- Flutter API contract.
- Dashboard.
- Filter dan pencarian.
- Statistik.
- Laporan.
- Export.
- Notifikasi.
- FCM.
- Audit log.
- Scheduler.
- Background jobs.
- Upload dan lampiran.
- Penomoran pengajuan.

Jangan mempertahankan dua implementasi proses bisnis yang sebenarnya sama apabila keduanya sudah digabungkan dalam arsitektur baru.

Gunakan satu sumber logic utama untuk proses pengajuan.

Controller harus tetap berfungsi sebagai penghubung request dan response.

Business logic harus ditempatkan pada service atau komponen domain yang sesuai.

Jangan mengubah tampilan, istilah, alur pengguna, atau perilaku bisnis yang tidak berkaitan langsung dengan perubahan arsitektur tanpa persetujuan saya.

## Aturan penting: Tidak boleh membuat asumsi bisnis

Contoh:

- Jangan mengubah alur konfirmasi.
- Jangan mengubah aturan auto-confirm 24 jam.
- Jangan mengubah mekanisme penolakan.
- Jangan mengubah hak edit dan hapus pemilik.
- Jangan mengubah aturan prioritas.
- Jangan mengubah penerima notifikasi.
- Jangan mengubah mekanisme sinkronisasi data.
- Jangan mengubah aturan laporan.
- Jangan mengubah tampilan dashboard.
- Jangan mengubah alur Flutter.

Kecuali perubahan tersebut memang wajib akibat struktur database baru dan sudah dijelaskan serta disetujui.

Jika suatu logic tidak dapat dipertahankan karena perubahan struktur, identifikasi dampaknya dan tanyakan kepada saya sebelum menentukan perilaku penggantinya.

---

# PHASE 5 — ANALISIS MIGRASI DATA (SANGAT KRITIS)

Database lama sudah berisi data yang harus dipertahankan.

Jangan melakukan migrasi dengan cara langsung menghapus tabel lama kemudian membuat tabel baru.

Lakukan analisis migrasi terlebih dahulu.

## 5.1 Mapping data lama ke baru

Buat mapping lengkap:

- tickets → pengajuan.
- order_perbaikan → pengajuan.
- ticket_photos → attachments.
- order_perbaikan.foto → attachments.
- order_perbaikan_histories → tabel histori baru.
- ticket admin_responses → struktur histori/pesan yang disepakati.
- ticket user_replies → struktur histori/pesan yang disepakati.
- ticket_penanggung_jawab → struktur penugasan baru.
- order_perbaikan_penanggung_jawab → struktur penugasan baru.
- kategori_order_user → assignment role-kategori sesuai hasil keputusan dan pemetaan data yang tersedia.
- pengajuan_links → relasi pengajuan yang baru atau mekanisme pengganti yang disetujui.

Mapping di atas merupakan kandidat awal. Verifikasi seluruh tabel dan dependensinya sebelum menetapkan mapping final.

## 5.2 Analisis khusus data TV

Saat ini terdapat mekanisme pengajuan gabungan kategori TV.

Satu pengajuan dapat menghasilkan pasangan tiket dan order melalui `pengajuan_links`.

Analisis secara khusus agar proses migrasi tidak menghasilkan dua pengajuan duplikat untuk satu proses bisnis.

Tentukan:

- Bagaimana mengidentifikasi pasangan tiket dan order.
- Bagaimana menggabungkan pasangan menjadi satu pengajuan.
- Bagaimana menangani pasangan yang tidak lengkap.
- Bagaimana mempertahankan histori dari kedua sumber.
- Bagaimana mempertahankan foto dan lampiran.
- Bagaimana mempertahankan nomor dokumen lama.
- Bagaimana mempertahankan ID referensi jika masih digunakan aplikasi atau sistem eksternal.

Jangan mengasumsikan semua tiket dan order mempunyai pasangan.

## 5.3 Mapping status

Buat tabel mapping seluruh status lama ke status baru.

Periksa:

- Status tiket.
- Status order.
- Status pengajuan yang berpasangan.
- Status yang tidak memiliki padanan.
- Data yang tidak konsisten.
- Status yang sudah dikonfirmasi.
- Status yang sedang dikerjakan.
- Status yang menunggu konfirmasi.

Jangan menggabungkan status yang berbeda makna hanya karena namanya terlihat mirip.

Jika terdapat keputusan bisnis yang belum jelas, tanyakan terlebih dahulu.

## 5.4 Strategi migrasi

Bandingkan strategi:

- One-time migration dengan maintenance window.
- Expand-and-contract.
- Dual-write selama masa transisi.
- Backfill bertahap menggunakan batch.
- Cutover setelah validasi.

Pilih pendekatan berdasarkan kondisi aplikasi aktual dan risiko produksi.

Rancang migration yang:

- Aman dijalankan.
- Dapat diverifikasi.
- Memiliki pencatatan hasil.
- Memiliki mekanisme penghentian ketika terjadi ketidaksesuaian.
- Tidak menggandakan data jika dijalankan ulang.
- Tidak kehilangan histori dan lampiran.
- Mempertahankan integritas foreign key.

Jangan menganggap rollback migration otomatis dapat mengembalikan data yang sudah diubah atau digabungkan.

Buat strategi rollback berbasis backup dan prosedur pemulihan data yang nyata.

## 5.5 Validasi migrasi

Buat mekanisme pembuktian:

- Row count sebelum dan sesudah.
- Jumlah tiket yang dimigrasikan.
- Jumlah order yang dimigrasikan.
- Jumlah pasangan TV yang berhasil digabung.
- Jumlah pasangan yang tidak lengkap.
- Jumlah histori yang dipindahkan.
- Jumlah attachment yang berhasil dipindahkan.
- Jumlah relasi user/role/kategori yang berhasil dimigrasikan.
- Jumlah orphan records.
- Jumlah duplikasi.
- Validitas foreign key.
- Konsistensi status.
- Konsistensi nomor pengajuan.

Hasil migrasi harus menghasilkan laporan yang bisa diperiksa.

Jangan melanjutkan cutover jika hasil validasi belum memenuhi kriteria yang disetujui.

---

# PHASE 6 — TESTING DAN VERIFIKASI

Buat test untuk seluruh proses yang terdampak.

Minimal meliputi:

1. User membuat pengajuan kategori IT.
2. User membuat pengajuan kategori IPSRS.
3. Pengajuan baru memperoleh nomor unik.
4. Pengajuan mendapatkan assignment role yang benar.
5. Admin dengan role yang sesuai dapat melihat pengajuan.
6. Admin tanpa assignment tidak memperoleh akses yang tidak semestinya.
7. Role baru dapat dibuat.
8. Permission baru dapat dibuat.
9. Role dapat ditambahkan permission.
10. Role dapat ditambahkan kategori.
11. Perubahan assignment memengaruhi scope yang benar.
12. Pengajuan dapat diedit sesuai aturan lama yang disetujui.
13. Penugasan dapat diperbarui.
14. Histori tetap tercatat.
15. Lampiran tetap dapat diakses.
16. Notifikasi tetap berjalan.
17. Auto-confirm tetap sesuai aturan yang disepakati.
18. Audit log tetap berfungsi.
19. API tetap kompatibel dengan kontrak yang disepakati.
20. Data hasil migrasi dapat ditelusuri kembali ke sumbernya.

Tambahkan pengujian untuk race condition, transaksi database, dan pemanggilan berulang.

Jangan mengklaim fitur selesai hanya karena halaman berhasil dibuka.

---

# PHASE 7 — DOKUMENTASI TASK UNTUK AI LAIN

Ini merupakan salah satu deliverable utama.

Saya ingin hasil analisis dan rencana implementasi dibuat menjadi file Markdown yang rinci, sehingga dapat dilanjutkan oleh model AI lain tanpa harus mengulang seluruh proses analisis dari awal.

Simpan dokumentasi di folder khusus, misalnya:

docs/refactor-unified-pengajuan/

Struktur yang diharapkan:

1. README.md
   - Tujuan proyek.
   - Baseline commit.
   - Ruang lingkup.
   - Status pengerjaan.
   - Cara melanjutkan pekerjaan.

2. 00-project-baseline.md
   - Commit hash.
   - Branch.
   - Kondisi repository.
   - Kondisi database.
   - Referensi analisis.

3. 01-current-architecture.md
   - Struktur database lama.
   - Dependency map.
   - Logic saat ini.
   - Temuan bug.
   - RBAC saat ini.

4. 02-target-database-design.md
   - Struktur database baru.
   - Data dictionary.
   - ERD.
   - Primary key.
   - Foreign key.
   - Index.
   - Constraint.
   - Relasi antartabel.

5. 03-rbac-redesign.md
   - Role.
   - Permission.
   - Assignment role-kategori.
   - User-role.
   - Scope pengajuan.
   - Matriks akses.
   - CRUD dan validasi.

6. 04-data-migration-plan.md
   - Mapping tabel.
   - Mapping kolom.
   - Mapping status.
   - Mapping histori.
   - Mapping attachment.
   - Penggabungan data TV.
   - Strategi backfill.
   - Validasi.
   - Rollback.

7. 05-business-logic-refactor.md
   - Perubahan service.
   - Controller.
   - Model.
   - Policy.
   - API.
   - UI.
   - Notifikasi.
   - Audit.
   - Scheduler.

8. 06-implementation-tasks.md
   - Daftar pekerjaan rinci.
   - Task ID unik.
   - File yang harus diubah.
   - Tujuan.
   - Dependensi.
   - Langkah implementasi.
   - Acceptance criteria.
   - Testing.

9. 07-testing-checklist.md
   - Unit test.
   - Feature test.
   - Integration test.
   - Migration test.
   - Regression test.

10. 08-cutover-and-rollback.md
    - Persiapan produksi.
    - Backup.
    - Maintenance window jika diperlukan.
    - Migrasi.
    - Verifikasi.
    - Cutover.
    - Rollback.

11. 09-ai-handoff.md
    - Progress terakhir.
    - Task selesai.
    - Task belum selesai.
    - Keputusan yang sudah disetujui.
    - Pertanyaan yang belum terjawab.
    - File yang sedang dikerjakan.
    - Commit terakhir.
    - Instruksi untuk model AI berikutnya.

Setiap task wajib memiliki status:

- NOT_STARTED
- IN_PROGRESS
- BLOCKED
- COMPLETED
- VERIFIED

Jangan menandai task COMPLETED hanya karena kode sudah dibuat. Harus ada bukti pengujian.

---

# PHASE 8 — ATURAN KERJA AI

Ini adalah aturan yang wajib dipatuhi selama seluruh pengerjaan.

1. Jangan melakukan perubahan sebelum baseline commit dikonfirmasi.
2. Jangan mengasumsikan aturan bisnis yang belum dijelaskan.
3. Jangan mengubah alur aplikasi yang tidak berkaitan dengan restrukturisasi.
4. Jangan menghapus tabel atau kolom lama sebelum migrasi tervalidasi.
5. Jangan mengubah migration lama yang sudah pernah dijalankan.
6. Gunakan migration baru dan strategi transisi yang aman.
7. Jangan menghilangkan fitur yang sudah berjalan.
8. Jangan mengubah kontrak API secara diam-diam.
9. Jangan menghapus permission atau role tanpa memeriksa dependensinya.
10. Jangan hardcode nama kategori atau role untuk menentukan perilaku dinamis.
11. Jangan membuat asumsi terhadap data produksi.
12. Jangan menjalankan operasi destruktif pada database produksi.
13. Jangan membuat perubahan yang tidak tercatat dalam task.
14. Setiap perubahan harus dapat ditelusuri ke task ID.
15. Jika menemukan kebutuhan di luar ruang lingkup, dokumentasikan dan tanyakan kepada saya.
16. Jika menemukan keputusan yang ambigu, berhenti pada bagian tersebut dan ajukan pertanyaan spesifik.
17. Jangan meminta konfirmasi berulang untuk keputusan yang sudah saya setujui.
18. Perbarui dokumentasi handoff setiap kali menyelesaikan kelompok task.

## Batas kewenangan

Kamu boleh melakukan analisis, membaca kode, membuat dependency map, mengidentifikasi bug, mengusulkan struktur, dan menyusun task.

Namun, keputusan mengenai perubahan perilaku bisnis tetap berada pada saya.

Jika ada beberapa alternatif desain, jelaskan kelebihan, kekurangan, risiko migrasi, dan rekomendasi teknisnya. Jangan langsung memilih dan mengimplementasikannya sebelum mendapat persetujuan.

---

# OUTPUT PERTAMA YANG SAYA INGINKAN

Pada tahap awal, jangan implementasikan apa pun.

Berikan hasil berupa:

1. Konfirmasi baseline commit yang diperlukan.
2. Ringkasan kondisi repository.
3. Hasil audit arsitektur aktual.
4. Daftar masalah CRUD role dan permission.
5. Usulan ERD unified pengajuan.
6. Usulan desain RBAC dan assignment role-kategori.
7. Analisis migrasi data lama ke baru.
8. Risiko terhadap data produksi.
9. Daftar keputusan bisnis yang perlu saya jawab.
10. Rancangan seluruh file task Markdown.

**Prioritaskan akurasi, integritas data, dan tidak berubahnya perilaku bisnis yang tidak saya setujui.**

Tujuan akhirnya adalah satu arsitektur pengajuan yang dinamis, database ternormalisasi, RBAC yang benar-benar dapat dikelola melalui CRUD, serta aplikasi web dan Flutter yang tetap berjalan dengan perilaku yang telah disetujui.

Dokumentasi task harus cukup rinci agar model AI lain dapat meneruskan implementasi tanpa membuat asumsi sendiri atau mengulang analisis dari awal.