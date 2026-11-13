<?php

namespace Tests\Unit\Services;

use App\Models\KategoriPengajuan;
use App\Models\JenisPengajuan;
use App\Models\Pengajuan;
use App\Models\User;
use App\Services\Report\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Kontrak angka report (App\Services\Report\ReportService):
 * - aktif    = open + in_progress (sedang dikerjakan)
 * - menunggu = 'ditutup' (sudah ditutup, MENUNGGU konfirmasi pemohon)
 * - selesai  = confirmed + rejected (sudah final)
 *
 * Status 'ditutup' TIDAK boleh ikut dihitung sebagai "Selesai", dan angka
 * "menunggu" harus dibaca dari status 'ditutup' (bukan status 'pending',
 * yang memang tidak pernah tersimpan di DB).
 */
class ReportServiceSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected KategoriPengajuan $kategori;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $jenis = JenisPengajuan::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Tiket IT / SIRS', 'prefix' => 'T', 'is_active' => true]
        );

        $this->kategori = KategoriPengajuan::firstOrCreate(
            ['name' => 'Printer'],
            ['status' => 1]
        );
        $this->kategori->syncJenis([$jenis->id]);

        $this->user = User::create([
            'name' => 'Pemohon', 'email' => 'pemohon@x.com', 'password' => Hash::make('123'),
            'phone' => '0812', 'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1,
        ]);
    }

    protected function makePengajuan(string $status, string $nomor): Pengajuan
    {
        return Pengajuan::create([
            'nomor' => $nomor,
            'kategori_id' => $this->kategori->id,
            'pemohon_id' => $this->user->id,
            'pemohon_nama' => $this->user->name,
            'deskripsi' => 'Keluhan '.$nomor,
            'prioritas' => 'medium',
            'status' => $status,
            'tanggal' => now(),
            'created_by' => $this->user->id,
        ]);
    }

    public function test_ditutup_tidak_dihitung_sebagai_selesai(): void
    {
        $this->makePengajuan('open', 'R-001');
        $this->makePengajuan('in_progress', 'R-002');
        $this->makePengajuan('ditutup', 'R-003');
        $this->makePengajuan('confirmed', 'R-004');
        $this->makePengajuan('rejected', 'R-005');

        $summary = app(ReportService::class)->summary(Pengajuan::query());

        $this->assertSame(5, $summary['total']);
        // Aktif = open + in_progress (ditutup tidak masuk aktif).
        $this->assertSame(2, $summary['active']);
        // Menunggu konfirmasi dibaca dari status 'ditutup'.
        $this->assertSame(1, $summary['pending']);
        $this->assertSame(1, $summary['ditutup']);
        $this->assertSame(1, $summary['confirmed']);
        $this->assertSame(1, $summary['rejected']);
        // Selesai = confirmed + rejected (tidak termasuk 'ditutup').
        $this->assertSame(2, $summary['done']);
        $this->assertSame(40.0, $summary['completion_rate']);
    }

    public function test_filter_status_alias_legacy_menemukan_ditutup(): void
    {
        $this->makePengajuan('ditutup', 'R-101');
        $this->makePengajuan('confirmed', 'R-102');

        $service = app(ReportService::class);

        foreach (['ditutup', 'closed', 'tutup', 'pending', 'pending_confirmation'] as $alias) {
            $request = Request::create('/reports', 'GET', ['status' => $alias]);

            $this->assertSame(1, $service->query($request)->count(), "Alias status: {$alias}");
        }

        $request = Request::create('/reports', 'GET', ['status' => 'confirmed']);
        $this->assertSame(1, $service->query($request)->count());
    }
}
