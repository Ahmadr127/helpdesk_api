<?php

namespace Tests\Unit\Services;

use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\User;
use App\Services\Pengajuan\PengajuanNumberService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengajuanNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 11, 6, 10, 0, 0));
    }

    public function test_generates_first_it_number(): void
    {
        JenisPengajuan::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Tiket IT / SIRS', 'prefix' => 'T', 'is_active' => true]
        );

        $number = app(PengajuanNumberService::class)->next('IT');

        $this->assertSame('T-0611-001', $number);
    }

    public function test_increments_it_number(): void
    {
        JenisPengajuan::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Tiket IT / SIRS', 'prefix' => 'T', 'is_active' => true]
        );

        $service = app(PengajuanNumberService::class);

        $this->assertSame('T-0611-001', $service->next('IT'));
        $this->assertSame('T-0611-002', $service->next('IT'));
    }

    public function test_generates_first_ipsrs_number(): void
    {
        JenisPengajuan::firstOrCreate(
            ['code' => 'IPSRS'],
            ['name' => 'Order Perbaikan / IPSRS', 'prefix' => 'OP/RTG/MTC', 'is_active' => true]
        );

        $number = app(PengajuanNumberService::class)->next('IPSRS');

        $this->assertSame('OP/RTG/MTC-20261106001', $number);
    }

    public function test_initializes_sequence_from_legacy_number(): void
    {
        $jenis = JenisPengajuan::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Tiket IT / SIRS', 'prefix' => 'T', 'is_active' => true]
        );

        $kategori = KategoriPengajuan::firstOrCreate(
            ['jenis_id' => $jenis->id, 'name' => 'Printer'],
            ['status' => 1]
        );

        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'User',
                'password' => bcrypt('secret'),
                'role' => 'user',
                'status' => 1,
            ]
        );

        Pengajuan::create([
            'nomor' => 'T-0611-005',
            'kategori_id' => $kategori->id,
            'pemohon_id' => $user->id,
            'pemohon_nama' => $user->name,
            'deskripsi' => 'Legacy ticket',
            'prioritas' => 'medium',
            'status' => 'open',
            'tanggal' => now(),
            'created_by' => $user->id,
        ]);

        $number = app(PengajuanNumberService::class)->next('IT');

        $this->assertSame('T-0611-006', $number);
    }
}
