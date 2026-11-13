<?php

namespace App\Services\Pengajuan;

use App\Models\Department;
use App\Models\KategoriPengajuan;
use App\Support\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Sumber tunggal filter daftar pengajuan (tiket) untuk halaman admin.
 *
 * Dipakai bareng oleh controller (query) dan partial filter/tabel (UI) supaya
 * nama parameter, opsi dropdown, dan hitungan filter aktif selalu sama di
 * semua halaman: admin.tickets.all / open / in-progress / closed.
 *
 * Parameter filter (semua dikirim via GET):
 * - q             : pencarian global (nomor, pemohon, deskripsi, kategori, departemen)
 * - nomor         : kolom "No Tiket"
 * - pemohon       : kolom "Pengguna" (nama pemohon)
 * - kategori_id   : kolom "Kategori"
 * - department_id : kolom "Departemen"
 * - deskripsi     : kolom "Deskripsi"
 * - status        : kolom "Status" (alias lama dinormalisasi lewat TicketStatus)
 * - date_from     : kolom "Tanggal Dibuat" (dari)
 * - date_to       : kolom "Tanggal Dibuat" (sampai)
 * - prioritas     : tambahan (tidak ditampilkan sebagai kolom tabel)
 * - confirmation  : tambahan, konfirmasi user (confirmed / pending)
 *
 * Alias lama tetap dibaca supaya bookmark lama tidak rusak:
 * search → q, start_date → date_from, end_date → date_to,
 * month + year → rentang tanggal bulan tersebut.
 */
class PengajuanListFilter
{
    /** Parameter yang ditampilkan sebagai filter kolom pada tabel. */
    public const COLUMN_PARAMS = [
        'nomor', 'pemohon', 'kategori_id', 'department_id', 'deskripsi', 'status', 'date_from', 'date_to',
    ];

    /** Parameter tambahan (bukan kolom tabel). */
    public const EXTRA_PARAMS = ['q', 'prioritas', 'confirmation'];

    /** Parameter baru → sumber alias lama. */
    protected const ALIASES = [
        'q' => 'search',
        'date_from' => 'start_date',
        'date_to' => 'end_date',
    ];

    /**
     * Terapkan semua filter aktif ke query.
     *
     * @param  string|null  $lockedStatus  status yang dikunci halaman (mis. "open");
     *                                     bila diisi, filter status dari request diabaikan.
     */
    public function apply(Builder $query, Request $request, ?string $lockedStatus = null): Builder
    {
        $query->when($this->term($request, 'q'), function (Builder $q, string $term) {
            $like = $this->like($term);
            $q->where(function (Builder $inner) use ($like) {
                $inner->whereRaw('LOWER(nomor) LIKE ?', [$like])
                    ->orWhereRaw("LOWER(COALESCE(pemohon_nama, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(deskripsi, '')) LIKE ?", [$like])
                    ->orWhereHas('pemohon', fn (Builder $u) => $u->whereRaw('LOWER(name) LIKE ?', [$like]))
                    ->orWhereHas('kategori', fn (Builder $k) => $k->whereRaw('LOWER(name) LIKE ?', [$like]))
                    ->orWhereHas('department', fn (Builder $d) => $d->whereRaw('LOWER(name) LIKE ?', [$like]));
            });
        });

        $query->when($this->term($request, 'nomor'), fn (Builder $q, string $term) => $q->whereRaw('LOWER(nomor) LIKE ?', [$this->like($term)]));

        $query->when($this->term($request, 'pemohon'), function (Builder $q, string $term) {
            $like = $this->like($term);
            $q->where(function (Builder $inner) use ($like) {
                $inner->whereRaw("LOWER(COALESCE(pemohon_nama, '')) LIKE ?", [$like])
                    ->orWhereHas('pemohon', fn (Builder $u) => $u->whereRaw('LOWER(name) LIKE ?', [$like]));
            });
        });

        $query->when($this->term($request, 'deskripsi'), fn (Builder $q, string $term) => $q->whereRaw("LOWER(COALESCE(deskripsi, '')) LIKE ?", [$this->like($term)]));

        $query->when($request->filled('kategori_id'), fn (Builder $q) => $q->where('kategori_id', (int) $request->input('kategori_id')));
        $query->when($request->filled('department_id'), fn (Builder $q) => $q->where('department_id', (int) $request->input('department_id')));

        // Status: halaman open/in-progress/closed mengunci statusnya sendiri.
        if ($lockedStatus === null) {
            $status = TicketStatus::normalize($request->input('status'));
            $query->when($status, fn (Builder $q) => $q->where('status', $status));
        }

        $query->when($request->filled('prioritas'), fn (Builder $q) => $q->where('prioritas', $request->input('prioritas')));

        // Konfirmasi user — independen dari filter status.
        $confirmation = (string) $request->input('confirmation');
        if ($confirmation === 'confirmed') {
            $query->where('user_confirmation', true);
        } elseif ($confirmation === 'pending') {
            $query->where('user_confirmation', false);
        }

        // Rentang tanggal "Tanggal Dibuat" (created_at).
        $dateFrom = $this->date($request, 'date_from');
        $dateTo = $this->date($request, 'date_to');

        // Legacy: month + year → rentang tanggal bulan tersebut.
        if ($dateFrom === null && $dateTo === null && $request->filled('month')) {
            $month = (int) $request->input('month');
            $year = (int) ($request->input('year') ?: now()->year);
            if ($month >= 1 && $month <= 12) {
                $dateFrom = sprintf('%04d-%02d-01', $year, $month);
                $dateTo = (new \DateTime($dateFrom))->format('Y-m-t');
            }
        }

        $query->when($dateFrom, fn (Builder $q) => $q->whereDate('created_at', '>=', $dateFrom));
        $query->when($dateTo, fn (Builder $q) => $q->whereDate('created_at', '<=', $dateTo));

        return $query;
    }

    /** Opsi dropdown untuk partial filter & tabel. */
    public function options(): array
    {
        return [
            'kategoris' => KategoriPengajuan::where('status', 1)->sirs()->orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ];
    }

    /** Opsi status (label siap tampil). */
    public function statusOptions(): array
    {
        return TicketStatus::options();
    }

    /**
     * Jumlah filter yang sedang aktif (untuk badge "N filter aktif").
     *
     * @param  string|null  $lockedStatus  bila status dikunci halaman, filter status tidak dihitung.
     */
    public function activeCount(Request $request, ?string $lockedStatus = null): int
    {
        $count = 0;

        foreach (array_merge(self::COLUMN_PARAMS, self::EXTRA_PARAMS) as $key) {
            if ($key === 'status' && $lockedStatus !== null) {
                continue;
            }

            if ($this->value($request, $key) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /** Nilai filter aktif (dengan fallback alias lama) — dipakai partial filter. */
    public function value(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        if (($value === null || $value === '') && isset(self::ALIASES[$key])) {
            $value = $request->input(self::ALIASES[$key]);
        }

        return ($value === null || $value === '') ? null : (string) $value;
    }

    /** Filter teks (sudah dibersihkan & lowercase). */
    protected function term(Request $request, string $key): ?string
    {
        $value = $this->value($request, $key);

        return $value === null ? null : mb_strtolower(trim($value));
    }

    /** Filter tanggal (YYYY-MM-DD) — tidak sah diabaikan. */
    protected function date(Request $request, string $key): ?string
    {
        $value = $this->value($request, $key);

        if ($value === null) {
            return null;
        }

        try {
            return (new \DateTime($value))->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    protected function like(string $term): string
    {
        return '%'.mb_strtolower(trim($term)).'%';
    }
}
