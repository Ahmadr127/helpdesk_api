# Konsistensi Lampiran & Timeline — Seluruh Pengajuan

> Tujuan: setelah unifikasi `pengajuan` (tiket IT & order perbaikan jadi satu schema),
> semua form / halaman / API memakai **satu cara** untuk menyimpan pesan, histori,
> dan lampiran. Dokumen ini adalah kontraknya.

## 1. Tiga "channel" yang saling tidak tumpang tindih

| Channel | Table | Isi | Contoh |
|---|---|---|---|
| **Pesan / Chat** | `pengajuan_pesan` | Balasan/notes dari manusia | user reply, admin response, catatan follow-up |
| **Histori status** | `pengajuan_histori` | Perubahan status + siapa aktornya | open → in_progress → ditutup → confirmed |
| **Lampiran (file)** | `pengajuan_lampiran` | File/foto/upload apa pun | nota, foto kerusakan, bukti |

Setiap channel punya tampilan bubble-nya sendiri, jangan dicampur.

## 2. Aturan penempatan lampiran (single source of truth)

```
konteks aksi                                 Monk | Lampiran FK
─────────────────────────────────────────────────────────────────
Balasan/pesan (chat, body teks)               →  pesan_id  (histori_id = NULL)
Perubahan status DENGAN catatan/aksi user     →  histori_id (pesan_id = NULL)
Upload awal saat pengajuan dibuat / diedit    →  kedua-duanya NULL (kind=initial)
```

### Contoh implementasi di code
- `PengajuanService::addMessage()` → selalu `pesan_id`, `kind=admin_response` atau `user_response`.
- `PengajuanWorkflowService::userConfirm()` → `histori_id`, `kind=user_confirmation` (foto di bubble status konfirmasi).
- `PengajuanService::create()/updateOwn()` (foto awal) → tidak punya FK, `kind=initial`.

## 3. Kontrak pengerjaan/akses data (jangan dilewati)

1. **Jangan pernah** `pesans()->create()` / `lampirans()->create()` /
   `historis()->create()` langsung dari controller — selalu lewat service:
   - `PengajuanService::addMessage()` untuk chat.
   - `PengajuanWorkflowService::start/close/adminConfirm/adminReject/userConfirm` untuk status.
   - `PengajuanService::create/updateOwn` untuk pengajuan baru/ubah.
   - `PengajuanAttachmentService::store(...)` untuk lampiran legacy/batch.
2. Controller hanya: validate → permission/scope check → panggil service → `back()`/redirect.
3. Nama `kind` di `pengajuan_lampiran` yang valid:
   - `initial` (upload saat create/edit pengajuan)
   - `admin_response` (dari alur admin, lewat pesan)
   - `user_response` (dari alur user, lewat pesan)
   - `user_confirmation` (foto saat user konfirmasi selesai / tolak)
4. **Jangan** membuat `kind` baru tanpa update `AttachmentService` + doc ini.

## 4. Satu format bubble, satu builder

Semua tampilan daftar pesan:
- Admin tiket → `admin/tickets/show`
- Admin order perbaikan → `admin/order-perbaikan/show`
- User pengajuan → `user/pengajuan/_detail`
- Modal myticket/myorder → `admin/tickets|order-perbaikan/myticket|myorder/component/detail-body`

Semuanya memakai `components.conversation-history` + data dari
`\App\Support\Conversation::responses($pengajuan)`. **Jangan** bikin blok
`pesans->map()` di view lagi — tambahkan ke class `Conversation` jika perlu.

## 5. Aturan validasi lampiran

| Form | Field name | Rules |
|---|---|---|
| Buat/ubah pengajuan (user & admin myorder) | `photos[]` (user) / `foto` (admin myorder) | `array`, `file|max:2048|image`, max 3 by service |
| Balasan chat (user & admin) | `photo` | `nullable|image|max:5120` |
| Konfirmasi selesai user | `photo` | `nullable|image|max:5120` |
| Admin catat status order (order-perbaikan admin) | `lampiran` | `nullable|file|max:5120` (.jpg/.jpeg/.png pattern di view) |

Jika nilai validasi tidak cocok pada kedua sisi (user vs admin), perbaiki di
**service** (`PengajuanAttachmentService::store` — max 3 file & error dinamis).

## 6. Aturan timeout/preview/alt

- Tampilkan foto selalu dengan `Storage::url($lampiran->path)` atau `asset("storage/...")` — konsisten.
- Preview gambar pakai `handleImagePreview()` (admin tiket) atau versi serupa yang sudah ada; jangan inline JS per-halaman.
- `<img>` harus punya `alt` (user/admin sepakat bahasa: "Foto Pengajuan" / "Lampiran").

## 7. Cara cek cepat

```sql
-- Foto admin harusnya pesan_id NOT NULL (lewat chat)
SELECT kind, count(*) FROM pengajuan_lampiran GROUP BY kind;

-- Timeline tanpa foto orphan
SELECT count(*) FROM pengajuan_lampiran
WHERE kind LIKE 'admin_%' AND pesan_id IS NULL AND histori_id IS NULL;
```

## 8. Yang sudah direfactor (inti sesi ini)

- `admin/order-perbaikan/show.blade.php` & `admin/tickets/show.blade.php` &
  user views & modal myticket/myorder → satu `Conversation::responses()` builder.
- Admin responses (tiket & order) → sama: notes = pesan, foto = lampiran (`pesan_id`).
- Konsistensi detail-body antara SIRS & order.

## 9. Yang berikutnya (jika ada waktu)

- `migrate:attachments` — pisahkan `kind=initial` ke tabel sendiri bila makin besar
- API photo untuk admin/confirm endpoint
- Cleanup dead code (TicketController@store/update, ticket-response-history)
