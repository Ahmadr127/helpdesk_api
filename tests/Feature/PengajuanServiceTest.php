<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\User;
use App\Services\Pengajuan\PengajuanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengajuanServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $jenisIt = JenisPengajuan::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Tiket IT / SIRS', 'prefix' => 'T', 'is_active' => true]
        );

        KategoriPengajuan::firstOrCreate(
            ['jenis_id' => $jenisIt->id, 'name' => 'Printer'],
            ['status' => 1]
        );

        $jenisIpsrs = JenisPengajuan::firstOrCreate(
            ['code' => 'IPSRS'],
            ['name' => 'Order Perbaikan / IPSRS', 'prefix' => 'OP/RTG/MTC', 'is_active' => true]
        );

        KategoriPengajuan::firstOrCreate(
            ['jenis_id' => $jenisIpsrs->id, 'name' => 'Perbaikan'],
            ['status' => 1]
        );
    }

    public function test_creates_it_pengajuan_with_history(): void
    {
        $user = $this->makeUser();
        $kategori = KategoriPengajuan::where('name', 'Printer')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($user, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Printer rusak',
            'prioritas' => 'high',
        ]);

        $this->assertNotNull($pengajuan->id);
        $this->assertStringStartsWith('T-', $pengajuan->nomor);
        $this->assertSame('open', $pengajuan->status);
        $this->assertSame($user->id, $pengajuan->pemohon_id);
        $this->assertSame($user->name, $pengajuan->pemohon_nama);
        $this->assertSame(1, $pengajuan->historis()->count());
        $this->assertNull($pengajuan->orderDetail);
    }

    public function test_creates_ipsrs_pengajuan_with_order_detail(): void
    {
        $user = $this->makeUser();
        $kategori = KategoriPengajuan::where('name', 'Perbaikan')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($user, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'AC tidak dingin',
            'prioritas' => 'medium',
            'unit_penerima' => 'Ruang Rawat Inap',
            'kode_inventaris' => 'INV-001',
            'nama_barang' => 'AC Split',
        ]);

        $this->assertSame('IPSRS', $pengajuan->kategori->jenis->code);
        $this->assertNotNull($pengajuan->orderDetail);
        $this->assertSame('Ruang Rawat Inap', $pengajuan->orderDetail->unit_penerima);
        $this->assertSame('INV-001', $pengajuan->orderDetail->kode_inventaris);
        $this->assertSame('AC Split', $pengajuan->orderDetail->nama_barang);
    }

    public function test_owner_can_update_open_pengajuan(): void
    {
        $user = $this->makeUser();
        $kategori = KategoriPengajuan::where('name', 'Printer')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($user, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Printer rusak',
            'prioritas' => 'medium',
        ]);

        $updated = app(PengajuanService::class)->updateOwn($user, $pengajuan, [
            'deskripsi' => 'Printer sudah diperbaiki sebagian',
            'prioritas' => 'high',
        ]);

        $this->assertSame('Printer sudah diperbaiki sebagian', $updated->deskripsi);
        $this->assertSame('high', $updated->prioritas);
        $this->assertSame(2, $updated->historis()->count());
    }

    public function test_owner_can_delete_open_pengajuan(): void
    {
        $user = $this->makeUser();
        $kategori = KategoriPengajuan::where('name', 'Printer')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($user, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Printer rusak',
        ]);

        app(PengajuanService::class)->deleteOwn($user, $pengajuan);

        $this->assertSoftDeleted('pengajuan', ['id' => $pengajuan->id]);
    }

    public function test_non_owner_cannot_update_pengajuan(): void
    {
        $owner = $this->makeUser('owner@example.com');
        $other = $this->makeUser('other@example.com');
        $kategori = KategoriPengajuan::where('name', 'Printer')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($owner, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Printer rusak',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);

        app(PengajuanService::class)->updateOwn($other, $pengajuan, [
            'deskripsi' => 'Hacked',
        ]);
    }

    public function test_cannot_update_non_open_pengajuan(): void
    {
        $user = $this->makeUser();
        $kategori = KategoriPengajuan::where('name', 'Printer')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($user, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Printer rusak',
        ]);

        $pengajuan->update(['status' => 'in_progress']);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(422);

        app(PengajuanService::class)->updateOwn($user, $pengajuan, [
            'deskripsi' => 'Too late',
        ]);
    }

    public function test_create_stores_initial_attachment(): void
    {
        Storage::fake('public');

        $user = $this->makeUser();
        $kategori = KategoriPengajuan::where('name', 'Printer')->firstOrFail();

        $pengajuan = app(PengajuanService::class)->create($user, [
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Printer rusak',
        ], [
            UploadedFile::fake()->image('foto.jpg'),
        ]);

        $this->assertCount(1, $pengajuan->lampirans);
        $this->assertSame('initial', $pengajuan->lampirans->first()->kind);
        Storage::disk('public')->assertExists($pengajuan->lampirans->first()->path);
    }

    private function makeUser(string $email = 'user@example.com'): User
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'Information Technology', 'status' => 1]);

        return User::firstOrCreate(['email' => $email], [
            'name' => 'Test User',
            'password' => bcrypt('secret'),
            'role' => 'user',
            'position' => 'user',
            'status' => 1,
            'department' => 'IT',
        ]);
    }
}
