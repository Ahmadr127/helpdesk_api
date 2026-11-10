<?php

namespace App\Services\Report;

use App\Models\Department;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Sumber tunggal seluruh query report (berbasis model baru: Pengajuan).
 *
 * Dipakai oleh:
 * - Admin\ReportController          → /reports, /report-sirs, export
 * - User\PengajuanController        → daftar pengajuan user
 *
 * Tidak ada lagi perhitungan report yang memakai model legacy Report/Ticket.
 */
class ReportService
{
    public const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    public const STATUSES = [
        'open' => 'Dibuka',
        'in_progress' => 'Dalam Proses',
        'pending' => 'Menunggu',
        'ditutup' => 'Ditutup',
        'confirmed' => 'Selesai',
        'rejected' => 'Ditolak',
    ];

    public const PRIORITAS = [
        'low' => 'Rendah',
        'medium' => 'Sedang',
        'high' => 'Tinggi',
    ];

    public const JENIS = [
        'IT' => 'Tiket IT / SIRS',
        'IPSRS' => 'Order Perbaikan / IPSRS',
    ];

    /** Kolom yang boleh dipakai sorting (whitelist anti SQL injection). */
    public const SORTABLE = ['nomor', 'tanggal', 'status', 'prioritas', 'created_at', 'closed_at'];

    /** Target SLA response time tiket (menit). */
    public const RESPONSE_SLA_MINUTES = 60;

    /**
     * Query dasar report dari parameter request (filter jenis & kategori array).
     */
    public function query(Request $request): Builder
    {
        return $this->applyFilters(Pengajuan::query(), $request);
    }

    public function applyFilters(Builder $query, Request $request): Builder
    {
        $query->when($request->filled('q'), function (Builder $q) use ($request) {
            $term = '%'.mb_strtolower(trim((string) $request->input('q'))).'%';

            $q->where(function (Builder $inner) use ($term) {
                $inner->whereRaw('LOWER(nomor) LIKE ?', [$term])
                    ->orWhereRaw("LOWER(COALESCE(pemohon_nama, '')) LIKE ?", [$term])
                    ->orWhereRaw('LOWER(deskripsi) LIKE ?', [$term])
                    ->orWhereHas('pemohon', fn (Builder $user) => $user->whereRaw('LOWER(name) LIKE ?', [$term]));
            });
        });

        $query->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->input('status')));
        // Kategori (checkbox): bisa multi-select; string/int tunggal tetap didukung.
        $rawKategori = $request->input('kategori_id');
        $kategoris = is_array($rawKategori) ? $rawKategori : ($rawKategori !== null && $rawKategori !== '' ? [$rawKategori] : []);
        $kategoris = array_values(array_filter(array_map('intval', $kategoris)));
        if ($kategoris !== []) {
            $query->whereIn('kategori_id', $kategoris);
        }
        $query->when($request->filled('prioritas'), fn (Builder $q) => $q->where('prioritas', $request->input('prioritas')));
        $query->when($request->filled('department_id'), fn (Builder $q) => $q->where('department_id', (int) $request->input('department_id')));

        // Jenis (checkbox): gunakan pivot kategori_jenis. Jika kategori punya dua jenis
        // (mis. TV = IPSRS + IT), pengajuannya tampil di KEDUA report.
        $rawJenis = $request->input('jenis');
        $jenis = is_array($rawJenis) ? $rawJenis : ($rawJenis !== null && $rawJenis !== '' ? [$rawJenis] : []);
        $jenis = array_values(array_filter(array_map('strval', $jenis)));
        if ($jenis !== []) {
            $query->whereHas('kategori.jenisList', fn (Builder $q) => $q->whereIn('code', $jenis));
        }

        if ($request->filled('year')) {
            $query->whereRaw('EXTRACT(YEAR FROM COALESCE(tanggal, created_at)) = ?', [$request->input('year')]);
        }

        $query->when($request->filled('date_from'), fn (Builder $q) => $q->whereRaw('COALESCE(tanggal, created_at)::date >= ?', [$request->input('date_from')]));
        $query->when($request->filled('date_to'), fn (Builder $q) => $q->whereRaw('COALESCE(tanggal, created_at)::date <= ?', [$request->input('date_to')]));

        return $query;
    }

    public function applySort(Builder $query, Request $request): Builder
    {
        $sort = (string) $request->input('sort', 'tanggal');
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, self::SORTABLE, true)) {
            $sort = 'tanggal';
        }

        if ($sort === 'tanggal') {
            $query->orderByRaw('COALESCE(tanggal, created_at) '.$direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        return $query->orderBy('id', 'desc');
    }

    public function paginate(Builder $query, Request $request): LengthAwarePaginator
    {
        $this->applySort($query, $request);

        return $query
            ->with($this->relations())
            ->paginate($this->perPage($request))
            ->withQueryString();
    }

    /**
     * Ringkasan status dari query yang sudah difilter.
     *
     * @return array{total:int,open:int,in_progress:int,pending:int,ditutup:int,confirmed:int,rejected:int,active:int,done:int,completion_rate:float}
     */
    public function summary(Builder $query): array
    {
        $row = (clone $query)
            ->reorder()
            ->selectRaw(
                "COUNT(*) AS total,
                 SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open,
                 SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                 SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                 SUM(CASE WHEN status = 'ditutup' THEN 1 ELSE 0 END) AS ditutup,
                 SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed,
                 SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected"
            )
            ->first();

        $total = (int) ($row->total ?? 0);
        $open = (int) ($row->open ?? 0);
        $inProgress = (int) ($row->in_progress ?? 0);
        $pending = (int) ($row->pending ?? 0);
        $ditutup = (int) ($row->ditutup ?? 0);
        $confirmed = (int) ($row->confirmed ?? 0);
        $rejected = (int) ($row->rejected ?? 0);
        $done = $ditutup + $confirmed;

        return [
            'total' => $total,
            'open' => $open,
            'in_progress' => $inProgress,
            'pending' => $pending,
            'ditutup' => $ditutup,
            'confirmed' => $confirmed,
            'rejected' => $rejected,
            'active' => $open + $inProgress + $pending,
            'done' => $done,
            'completion_rate' => $total > 0 ? round(($done / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * Metrik SIRS (respon & proses):
     * - respond: durasi dari created_at → started_at (tiket pertama kali direspon).
     * - proses : durasi dari started_at → closed_at (tiket diselesaikan).
     * Tiket tanpa timestamp acuan tidak ikut hitungan.
     *
     * @return array{responded:int,processed:int,under_60_response:int,under_60_process:int,response_pct:float,process_pct:float,avg_response_minutes:int,avg_process_minutes:int,under_60:int,percentage:float,avg_minutes:int}
     */
    public function performance(Builder $query): array
    {
        $rows = (clone $query)->reorder()->get(['id', 'status', 'created_at', 'started_at', 'closed_at']);

        $respDurations = $rows
            ->filter(fn (Pengajuan $pengajuan) => $pengajuan->created_at && $pengajuan->started_at)
            ->map(fn (Pengajuan $pengajuan) => (int) $pengajuan->created_at->diffInMinutes($pengajuan->started_at))
            ->values();

        $procDurations = $rows
            ->filter(fn (Pengajuan $pengajuan) => $pengajuan->started_at && $pengajuan->closed_at)
            ->map(fn (Pengajuan $pengajuan) => (int) $pengajuan->started_at->diffInMinutes($pengajuan->closed_at))
            ->values();

        $responded = $respDurations->count();
        $processed = $procDurations->count();
        $under60Response = $respDurations->filter(fn (int $m) => $m <= self::RESPONSE_SLA_MINUTES)->count();
        $under60Process = $procDurations->filter(fn (int $m) => $m <= self::RESPONSE_SLA_MINUTES)->count();

        $result = [
            'responded' => $responded,
            'processed' => $processed,
            'under_60_response' => $under60Response,
            'under_60_process' => $under60Process,
            'response_pct' => $responded > 0 ? round(($under60Response / $responded) * 100, 2) : 0.0,
            'process_pct' => $processed > 0 ? round(($under60Process / $processed) * 100, 2) : 0.0,
            'avg_response_minutes' => $responded > 0 ? (int) round($respDurations->avg()) : 0,
            'avg_process_minutes' => $processed > 0 ? (int) round($procDurations->avg()) : 0,
        ];

        // Key lama dipertahankan untuk kompatibilitas tempat lain.
        $result['under_60'] = $under60Response;
        $result['percentage'] = $result['response_pct'];
        $result['avg_minutes'] = $result['avg_response_minutes'];

        return $result;
    }

    /** Format durasi "x hari y jam z menit" (fallback "0 menit"). */
    public function durationLabel($from, $to): string
    {
        if (! $from || ! $to) {
            return '-';
        }

        $duration = $from->diff($to);
        $parts = [];

        if ($duration->days > 0) {
            $parts[] = $duration->days.' hari';
        }
        if ($duration->h > 0) {
            $parts[] = $duration->h.' jam';
        }
        if ($duration->i > 0) {
            $parts[] = $duration->i.' menit';
        }

        return $parts === [] ? '0 menit' : implode(', ', $parts);
    }

    /** Label Status Kinerja (sama dengan kolom tabel web). */
    public function kinerjaLabel(Pengajuan $pengajuan): string
    {
        if ($pengajuan->created_at && $pengajuan->started_at) {
            return $pengajuan->created_at->diffInMinutes($pengajuan->started_at) <= self::RESPONSE_SLA_MINUTES
                ? 'Response ≤ 60 Menit'
                : 'Response > 60 Menit';
        }

        return $pengajuan->created_at ? 'Belum Direspon' : '-';
    }

    public function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 10);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }

    /** Kategori multi-jenis aktif untuk dropdown filter. */
    public function options(?string $jenis = null): array
    {
        return [
            'kategoris' => KategoriPengajuan::with('jenis')
                ->where('status', 1)
                ->when($jenis, fn (Builder $q) => $q->whereHas('jenis', fn (Builder $j) => $j->where('code', $jenis)))
                ->orderBy('name')
                ->get(),
            'jenisList' => JenisPengajuan::orderBy('code')->get(),
            'departments' => Department::orderBy('name')->get(),
        ];
    }

    /**
     * Query untuk export (filter aktif + optional id terpilih).
     *
     * @param  array<int, int|string>  $ids
     */
    public function exportQuery(Request $request, array $ids = []): Builder
    {
        $query = $this->query($request);

        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }

        $this->applySort($query, $request);

        return $query->with($this->relations());
    }

    /** Relasi wajib agar tabel & export tidak N+1. */
    public function relations(): array
    {
        return ['kategori.jenisList', 'kategori.jenisList', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user'];
    }
}
