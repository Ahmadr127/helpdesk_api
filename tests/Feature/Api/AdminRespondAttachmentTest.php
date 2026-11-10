<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Location;
use App\Models\Pengajuan;
use App\Models\PengajuanLampiran;
use App\Models\PengajuanPesan;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

/**
 * Regresi: lampiran yang dikirim mobile saat admin memberi tanggapan /
 * mengubah status sebelumnya dibuang diam-diam oleh AdminPengajuanController
 * (parameter $files di-hardcode []).
 */
class AdminRespondAttachmentTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    protected User $admin;

    protected Pengajuan $pengajuan;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seedRolePermissions();

        $department = Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);
        Position::create(['name' => 'IT', 'code' => 'IT', 'status' => true]);
        $location = Location::create(['name' => 'UGD', 'status' => 1]);

        $jenis = JenisPengajuan::where('code', 'IT')->firstOrFail();
        $kategori = KategoriPengajuan::create(['name' => 'Jaringan', 'code' => 'JRG', 'status' => 1]);
        $kategori->syncJenis([$jenis->id]);

        // Admin harus ter-mapping ke kategori agar lolos PengajuanScopeService.
        Role::where('slug', 'admin')->firstOrFail()->kategoris()->sync([$kategori->id]);

        $this->admin = User::create([
            'name' => 'Admin IT', 'email' => 'admin@example.com', 'password' => Hash::make('123'),
            'phone' => '0812', 'position' => 'IT', 'role' => 'admin', 'department' => 'IT', 'status' => 1,
        ]);

        $requester = User::create([
            'name' => 'Regular', 'email' => 'user@example.com', 'password' => Hash::make('123'),
            'phone' => '0811', 'position' => 'IT', 'role' => 'user', 'department' => 'IT', 'status' => 1,
        ]);

        $this->pengajuan = Pengajuan::create([
            'nomor' => 'T-0101-001',
            'kategori_id' => $kategori->id,
            'pemohon_id' => $requester->id,
            'pemohon_nama' => $requester->name,
            'department_id' => $department->id,
            'lokasi_id' => $location->id,
            'deskripsi' => 'Printer tidak bisa print',
            'prioritas' => 'high',
            'status' => 'open',
            'tanggal' => now(),
            'created_by' => $requester->id,
        ]);
    }

    protected function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_respond_menyimpan_lampiran_dari_photos_array(): void
    {
        $resp = $this->withHeaders($this->authHeader($this->admin))
            ->post("/api/admin/tickets/{$this->pengajuan->id}/respond", [
                'notes' => 'Sudah dicek, kabel longgar.',
                'status' => 'in_progress',
                'photos' => [
                    UploadedFile::fake()->image('bukti1.jpg'),
                    UploadedFile::fake()->image('bukti2.jpg'),
                ],
            ], ['Accept' => 'application/json']);

        $resp->assertStatus(200);

        $pesan = PengajuanPesan::where('pengajuan_id', $this->pengajuan->id)->latest('id')->first();
        $this->assertNotNull($pesan, 'Pesan admin harus tersimpan.');

        $lampiran = PengajuanLampiran::where('pesan_id', $pesan->id)->get();
        $this->assertCount(2, $lampiran, 'Dua lampiran harus tersimpan.');
        $this->assertSame('admin_response', $lampiran->first()->kind);

        foreach ($lampiran as $l) {
            Storage::disk('public')->assertExists($l->path);
        }
    }

    public function test_respond_menyimpan_lampiran_dari_photo_tunggal(): void
    {
        $resp = $this->withHeaders($this->authHeader($this->admin))
            ->post("/api/admin/tickets/{$this->pengajuan->id}/respond", [
                'notes' => 'Sudah diperbaiki.',
                'status' => 'ditutup',
                'photo' => UploadedFile::fake()->image('bukti.jpg'),
            ], ['Accept' => 'application/json']);

        $resp->assertStatus(200);

        $this->assertSame(
            1,
            PengajuanLampiran::where('kind', 'admin_response')->count(),
            'Lampiran tunggal harus tersimpan.'
        );
    }

    public function test_respond_tanpa_lampiran_tetap_berhasil(): void
    {
        $resp = $this->withHeaders($this->authHeader($this->admin))
            ->postJson("/api/admin/tickets/{$this->pengajuan->id}/respond", [
                'notes' => 'Hanya catatan.',
                'status' => 'in_progress',
            ]);

        $resp->assertStatus(200);
        $this->assertSame(0, PengajuanLampiran::where('kind', 'admin_response')->count());
    }

    public function test_update_status_menyimpan_lampiran(): void
    {
        $resp = $this->withHeaders($this->authHeader($this->admin))
            ->post("/api/admin/tickets/{$this->pengajuan->id}/status", [
                'status' => 'in_progress',
                '_method' => 'PUT',
                'photos' => [UploadedFile::fake()->image('status.jpg')],
            ], ['Accept' => 'application/json']);

        $resp->assertStatus(200);
        $this->assertSame(
            1,
            PengajuanLampiran::where('kind', 'admin_response')->count(),
            'Lampiran pada update status tidak boleh hilang.'
        );
    }

    public function test_resource_mengirim_lampiran_pesan_ke_mobile(): void
    {
        $this->withHeaders($this->authHeader($this->admin))
            ->post("/api/admin/tickets/{$this->pengajuan->id}/respond", [
                'notes' => 'Lihat lampiran.',
                'status' => 'in_progress',
                'photos' => [UploadedFile::fake()->image('lampiran.jpg')],
            ], ['Accept' => 'application/json'])
            ->assertStatus(200);

        $show = $this->withHeaders($this->authHeader($this->admin))
            ->getJson("/api/admin/tickets/{$this->pengajuan->id}")
            ->assertStatus(200);

        $pesan = $show->json('data.pesans');
        $this->assertNotEmpty($pesan, 'Pesan harus ada di response.');

        $last = collect($pesan)->last();
        $this->assertNotEmpty($last['photo_urls'] ?? [], 'photo_urls harus terisi agar Flutter bisa render gambar.');
        $this->assertNotNull($last['photo_url'] ?? null);
        $this->assertNotEmpty($last['lampirans'] ?? []);
    }
}
