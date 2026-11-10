<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Location;
use App\Models\Pengajuan;
use App\Models\PengajuanHistori;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

/**
 * Regresi: timeline hanya menampilkan pemohon karena `actor_type` selalu
 * ditulis 'user' (hardcode), sehingga admin tidak pernah dikenali.
 */
class TimelineActorTypeTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    protected User $admin;

    protected User $requester;

    protected Pengajuan $pengajuan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolePermissions();

        $department = Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);
        Position::create(['name' => 'IT', 'code' => 'IT', 'status' => true]);
        $location = Location::create(['name' => 'UGD', 'status' => 1]);

        $jenis = JenisPengajuan::where('code', 'IT')->firstOrFail();
        $kategori = KategoriPengajuan::create(['name' => 'Jaringan', 'code' => 'JRG', 'status' => 1]);
        $kategori->syncJenis([$jenis->id]);
        Role::where('slug', 'admin')->firstOrFail()->kategoris()->sync([$kategori->id]);

        $this->admin = User::create([
            'name' => 'Budi Admin', 'email' => 'admin@example.com', 'password' => Hash::make('123'),
            'phone' => '0812', 'position' => 'IT', 'role' => 'admin', 'department' => 'IT', 'status' => 1,
        ]);

        $this->requester = User::create([
            'name' => 'Siti Pemohon', 'email' => 'user@example.com', 'password' => Hash::make('123'),
            'phone' => '0811', 'position' => 'IT', 'role' => 'user', 'department' => 'IT', 'status' => 1,
        ]);

        $this->pengajuan = Pengajuan::create([
            'nomor' => 'T-0101-777',
            'kategori_id' => $kategori->id,
            'pemohon_id' => $this->requester->id,
            'pemohon_nama' => $this->requester->name,
            'department_id' => $department->id,
            'lokasi_id' => $location->id,
            'deskripsi' => 'Printer macet',
            'prioritas' => 'high',
            'status' => 'open',
            'tanggal' => now(),
            'created_by' => $this->requester->id,
        ]);
    }

    protected function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('t')->plainTextToken];
    }

    public function test_actor_type_admin_terdeteksi_untuk_admin_it(): void
    {
        $this->assertSame('admin', PengajuanHistori::actorTypeFor($this->admin));
        $this->assertSame('user', PengajuanHistori::actorTypeFor($this->requester));
        $this->assertSame('system', PengajuanHistori::actorTypeFor(null));
    }

    public function test_respond_admin_mencatat_actor_type_admin(): void
    {
        $this->withHeaders($this->authHeader($this->admin))
            ->postJson("/api/admin/tickets/{$this->pengajuan->id}/respond", [
                'notes' => 'Sudah dicek.',
                'status' => 'in_progress',
            ])
            ->assertStatus(200);

        $histori = PengajuanHistori::where('pengajuan_id', $this->pengajuan->id)
            ->where('actor_id', $this->admin->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($histori);
        $this->assertSame('admin', $histori->actor_type, 'Aksi admin harus tercatat sebagai admin.');
    }

    public function test_timeline_menampilkan_nama_admin_bukan_pemohon(): void
    {
        $this->withHeaders($this->authHeader($this->admin))
            ->postJson("/api/admin/tickets/{$this->pengajuan->id}/respond", [
                'notes' => 'Sedang dikerjakan.',
                'status' => 'in_progress',
            ])
            ->assertStatus(200);

        $resp = $this->withHeaders($this->authHeader($this->admin))
            ->getJson("/api/admin/tickets/{$this->pengajuan->id}")
            ->assertStatus(200);

        $timeline = collect($resp->json('data.timeline'));
        $this->assertNotEmpty($timeline, 'Timeline harus berisi entri.');

        $adminEntry = $timeline->firstWhere('is_admin', true);
        $this->assertNotNull($adminEntry, 'Entri admin harus ada di timeline.');
        $this->assertSame('Budi Admin', $adminEntry['by'], 'Nama admin harus tampil, bukan nama pemohon.');
        $this->assertNotSame('Siti Pemohon', $adminEntry['by']);
    }

    /**
     * Regresi: timeline di mobile hanya menampilkan aksi, tanpa status —
     * padahal web menampilkan "Dibuat / Dalam Proses / Ditutup / Dikonfirmasi".
     * Setiap entri WAJIB membawa `status` agar Flutter bisa merender chip.
     */
    public function test_timeline_mengirim_status_tiap_entri(): void
    {
        $this->withHeaders($this->authHeader($this->admin))
            ->postJson("/api/admin/tickets/{$this->pengajuan->id}/respond", [
                'notes' => 'Sedang dikerjakan.',
                'status' => 'in_progress',
            ])
            ->assertStatus(200);

        $resp = $this->withHeaders($this->authHeader($this->admin))
            ->getJson("/api/admin/tickets/{$this->pengajuan->id}")
            ->assertStatus(200);

        $timeline = collect($resp->json('data.timeline'));
        $this->assertNotEmpty($timeline);

        foreach ($timeline as $entry) {
            $this->assertArrayHasKey('status', $entry, 'Tiap entri timeline harus punya status.');
            $this->assertNotEmpty($entry['status'], 'Status tidak boleh kosong.');
        }

        $statuses = $timeline->pluck('status')->all();
        $this->assertContains('in_progress', $statuses, 'Status hasil aksi admin harus ikut terkirim.');
    }
}
