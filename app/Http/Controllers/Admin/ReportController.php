<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Services\Report\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Satu-satunya controller report admin (berbasis model Pengajuan).
 * /reports → default jenis IPSRS, /report-sirs → default jenis IT; keduanya kolom & tampilan sama.
 */
class ReportController extends Controller
{
    public function __construct(
        protected ReportService $report,
    ) {}

    /** /reports — report berbasis Pengajuan, default jenis IPSRS. */
    public function index(Request $request): View
    {
        return $this->render($request, 'IPSRS', 'admin.reports.index', 'admin.reports.export');
    }

    public function sirs(Request $request): View
    {
        return $this->render($request, 'IT', 'admin.report-sirs.index', 'admin.report-sirs.export');
    }

    public function export(Request $request)
    {
        return $this->streamCsvSelected($request, 'IPSRS', 'full');
    }

    public function exportSirs(Request $request)
    {
        return $this->streamCsvSelected($request, 'IT', 'full');
    }

    /**
     * Render halaman report — satu implementasi untuk /reports & /report-sirs.
     * Bedanya hanya: default jenis, view, dan route export.
     */
    protected function render(Request $request, string $defaultJenis, string $view, string $exportRoute): View
    {
        // _filter=1 menandakan form di-submit; tanpa itu, pakai default jenis halaman.
        // Tidak ada checkbox jenis yang dicentang = semua jenis.
        if (! $request->filled('_filter')) {
            // Default filter: bulan berjalan + jenis default halaman.
            $request->merge([
                'jenis' => [$defaultJenis],
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->endOfMonth()->toDateString(),
            ]);
        }

        $reqJenis = (array) $request->input('jenis', [$defaultJenis]);
        $reqJenis = array_values(array_filter(array_map('strval', array_map('trim', $reqJenis))));

        $query = $this->report->query($request);
        $summary = $this->report->summary($query);
        $performance = $this->report->performance($query);
        $pengajuans = $this->report->paginate(clone $query, $request);

        // years filter juga pakai pivot jenisList agar joint kategori (dua jenis) terhitung di tahunnya.
        $reqJenisIds = \App\Models\KategoriPengajuan::whereHas('jenisList', fn (Builder $k) => $k->whereIn('code', $reqJenis))
            ->pluck('id');

        $years = Pengajuan::query()
            ->when($reqJenisIds->isNotEmpty(), fn (Builder $q) => $q->whereIn('kategori_id', $reqJenisIds))
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM COALESCE(tanggal, created_at)) AS year')
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view($view, array_merge([
            'pengajuans' => $pengajuans,
            'years' => $years,
            'summaryCards' => [
                [
                    'label' => 'Total Pengajuan',
                    'value' => number_format($summary['total'], 0, ',', '.'),
                    'sub' => $summary['active'].' aktif',
                    'color' => 'blue',
                ],
                [
                    'label' => 'Waktu Respond ≤ 60 Menit',
                    'value' => number_format($performance['under_60_response'], 0, ',', '.'),
                    'sub' => $performance['responded'].' tiket sudah direspon',
                    'color' => 'green',
                ],
                [
                    'label' => 'Waktu Proses ≤ 60 Menit',
                    'value' => number_format($performance['under_60_process'], 0, ',', '.'),
                    'sub' => $performance['processed'].' tiket sudah diproses',
                    'color' => 'yellow',
                ],
                [
                    'label' => 'Tingkat Penyelesaian',
                    'value' => round($summary['completion_rate'], 1).'%',
                    'sub' => $summary['done'].' selesai dari '.$summary['total'],
                    'color' => 'purple',
                ],
            ],
            'columns' => ['nomor', 'pemohon', 'kategori', 'created_at', 'response_time', 'waktu_proses', 'closed_at', 'kinerja', 'pj'],
            'route' => route($view),
            'exportRoute' => $exportRoute,
            'exportAction' => 'exportReport',
            'showJenis' => true,
            'showDepartment' => false,
            'showYear' => true,
            'selectable' => true,
            'defaultJenis' => $defaultJenis,
            'detailRoute' => 'admin.pengajuan.show',
        ], $this->report->options()));
    }

    /** Export CSV mengikuti filter aktif (+ baris terpilih). */
    protected function streamCsvSelected(Request $request, string $defaultJenis)
    {
        if (! $request->filled('_filter')) {
            // Default filter: bulan berjalan + jenis default halaman.
            $request->merge([
                'jenis' => [$defaultJenis],
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->endOfMonth()->toDateString(),
            ]);
        }

        $ids = collect($request->input('selected_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->all();

        $query = $this->report->exportQuery($request, $ids);

        $slug = implode('-', array_values(array_filter(array_map(
            fn ($jenis) => strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $jenis)),
            (array) $request->input('jenis', [])
        ))));

        $slug = $slug !== '' ? $slug : 'semua';

        return $this->streamCsv($query, "report-{$slug}-".now()->format('Ymd-His').'.csv');
    }

    protected function streamCsv(Builder $query, string $fileName)
    {
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            // BOM agar Excel membaca UTF-8 dengan benar.
            fwrite($out, "\xEF\xBB\xBF");

            // Kolom: persis seperti tabel web, lalu kolom tambahan di luar web.
            fputcsv($out, [
                'Nomor',
                'Pemohon',
                'Kategori',
                'Tanggal Dibuat',
                'Waktu Respond',
                'Waktu Proses',
                'Tanggal Selesai',
                'Status Kinerja',
                // Tambahan:
                'Jenis',
                'Departemen',
                'Gedung',
                'Lokasi',
                'Prioritas',
                'Status',
                'Penanggung Jawab',
                'Tindak Lanjut',
                'Deskripsi',
            ]);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $pengajuan) {
                    fputcsv($out, [
                        $pengajuan->nomor,
                        $pengajuan->pemohon_nama ?? $pengajuan->pemohon?->name,
                        $pengajuan->kategori?->name,
                        optional($pengajuan->created_at)->format('d/m/Y H:i'),
                        $this->report->durationLabel($pengajuan->created_at, $pengajuan->started_at),
                        $this->report->durationLabel($pengajuan->started_at, $pengajuan->closed_at),
                        $pengajuan->closed_at?->format('d/m/Y H:i') ?? '-',
                        $this->report->kinerjaLabel($pengajuan),
                        $pengajuan->kategori?->jenisNames,
                        $pengajuan->department?->name,
                        $pengajuan->building?->name,
                        $pengajuan->lokasi?->name,
                        ReportService::PRIORITAS[$pengajuan->prioritas] ?? $pengajuan->prioritas,
                        ReportService::STATUSES[$pengajuan->status] ?? $pengajuan->status,
                        $pengajuan->nama_penanggung_jawab,
                        $pengajuan->follow_up,
                        $pengajuan->deskripsi,
                    ]);
                }
            });

            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
