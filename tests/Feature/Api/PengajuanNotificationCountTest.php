<?php

namespace Tests\Feature\Api;

use App\Jobs\Notifications\SendFirebaseNotificationJob;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\PengajuanPenugasan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

/**
 * Mengukur jumlah notifikasi yang benar-benar di-queue untuk tiap aksi,
 * agar double-notify (service + controller) terdeteksi.
 */
class PengajuanNotificationCountTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    protected User $admin;

    protected User $pemohon;

    protected User $pj;

    protected KategoriPengajuan $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolePermissions();

        $jenis = JenisPengajuan::where('code', 'IT')->firstOrFail();
        $this->kategori = KategoriPengajuan::create(['name' => 'Hardware', 'status' => 1]);
        $this->kategori->syncJenis([$jenis->id]);

        $adminRole = Role::where('slug', 'admin')->firstOrFail();
        $adminRole->kategoris()->sync([$this->kategori->id]);

        $this->admin = $this->makeUser('Admin IT', 'admin', 'admin@example.com', 'admin_token_1234567890');
        $this->pj = $this->makeUser('PJ Satu', 'admin', 'pj@example.com', 'pj_token_1234567890ab');
        $this->pemohon = $this->makeUser('Pemohon', 'user', 'pemohon@example.com', 'user_token_1234567890');
    }

    protected function makeUser(string $name, string $role, string $email, string $token): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('123'),
            'phone' => '0811',
            'position' => null,
            'role' => $role,
            'department' => null,
            'status' => 1,
            'fcm_token' => $token,
        ]);
    }

    protected function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('t')->plainTextToken, 'Accept' => 'application/json'];
    }

    protected function makePengajuan(string $status = 'open'): Pengajuan
    {
        return Pengajuan::create([
            'nomor' => 'T-0109-'.uniqid(),
            'kategori_id' => $this->kategori->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_nama' => $this->pemohon->name,
            'deskripsi' => 'Rusak',
            'prioritas' => 'medium',
            'status' => $status,
            'created_by' => $this->pemohon->id,
        ]);
    }

    /**
     * Tipe notifikasi status yang dikirim (unik).
     * Satu tipe bisa menghasilkan >1 job karena dispatch per token FCM,
     * jadi yang diuji adalah variasi tipenya, bukan jumlah job.
     */
    protected function statusTypes(): array
    {
        $statusTypes = ['started', 'closed', 'status_updated', 'responded', 'admin_confirmed', 'admin_rejected'];

        return Queue::pushed(SendFirebaseNotificationJob::class)
            ->map(fn ($job) => $job->payload['data']['type'])
            ->filter(fn ($t) => in_array($t, $statusTypes, true))
            ->unique()
            ->values()
            ->all();
    }

    protected function allTypes(): array
    {
        return Queue::pushed(SendFirebaseNotificationJob::class)
            ->map(fn ($job) => $job->payload['data']['type'])
            ->unique()
            ->values()
            ->all();
    }

    public function test_status_change_via_respond_hanya_satu_notifikasi(): void
    {
        Queue::fake();
        $pengajuan = $this->makePengajuan('open');

        $this->withHeaders($this->authHeader($this->admin))
            ->postJson("/api/admin/tickets/{$pengajuan->id}/respond", [
                'notes' => 'Kami proses',
                'status' => 'in_progress',
            ])
            ->assertStatus(200);

        // start() sudah mengirim 'started'; controller tidak boleh mengirim lagi.
        $this->assertSame(['started'], $this->statusTypes(), 'Tipe notifikasi: '.implode(',', $this->allTypes()));
    }

    public function test_close_via_update_status_tidak_dobel_notifikasi(): void
    {
        Queue::fake();
        $pengajuan = $this->makePengajuan('in_progress');

        $this->withHeaders($this->authHeader($this->admin))
            ->putJson("/api/admin/tickets/{$pengajuan->id}/status", [
                'status' => 'ditutup',
                'notes' => 'Selesai',
            ])
            ->assertStatus(200);

        // close() sudah mengirim 'closed'; controller tidak boleh mengirim lagi.
        $this->assertSame(['closed'], $this->statusTypes(), 'Tipe notifikasi: '.implode(',', $this->allTypes()));
    }

    public function test_notifikasi_penugasan_hanya_ke_pj_baru(): void
    {
        Queue::fake();
        $pengajuan = $this->makePengajuan('open');

        $this->withHeaders($this->authHeader($this->admin))
            ->putJson("/api/admin/pengajuan/{$pengajuan->id}/penugasan", [
                'user_ids' => [$this->pj->id],
            ])
            ->assertStatus(200);

        $this->assertSame(['assigned'], $this->allTypes());
    }

    public function test_admin_di_luar_scope_tidak_menerima_notifikasi(): void
    {
        Queue::fake();

        // Role 'ipsrs' tidak dipetakan ke kategori ini -> tidak boleh jadi penerima.
        $ipsrs = $this->makeUser('Admin IPSRS', 'ipsrs', 'ipsrs@example.com', 'ipsrs_token_1234567890');
        $pengajuan = $this->makePengajuan('open');

        // Pemohon membuat pengajuan -> notifikasi 'ticket_created'.
        $this->withHeaders($this->authHeader($this->pemohon))
            ->postJson('/api/tickets', [
                'kategori_id' => $this->kategori->id,
                'description' => 'Komputer rusak',
                'priority' => 'high',
            ])
            ->assertStatus(201);

        $tokens = Queue::pushed(SendFirebaseNotificationJob::class)
            ->map(fn ($job) => $job->token)
            ->filter()
            ->values()->all();

        // Admin dalam scope (role_kategori) menerima; admin IPSRS di luar scope tidak.
        $this->assertContains($this->admin->fcm_token, $tokens);
        $this->assertNotContains($ipsrs->fcm_token, $tokens);
        $this->assertNotContains($this->pemohon->fcm_token, $tokens, 'Actor tidak boleh menerima notifikasi buatannya sendiri');
    }

    public function test_pj_aktif_di_luar_scope_tidak_menerima_notifikasi(): void
    {
        Queue::fake();

        // PJ dari role 'ipsrs' yang tidak ter-mapping ke kategori ini.
        $pjLuarScope = $this->makeUser('PJ Luar', 'ipsrs', 'pjluar@example.com', 'pjluar_token_1234567890');

        $pengajuan = $this->makePengajuan('open');
        PengajuanPenugasan::create([
            'pengajuan_id' => $pengajuan->id,
            'user_id' => $pjLuarScope->id,
            'role_id' => Role::where('slug', 'ipsrs')->value('id'),
            'assigned_at' => now(),
        ]);

        // Admin mengubah status -> notifikasi status.
        $this->withHeaders($this->authHeader($this->admin))
            ->putJson("/api/admin/tickets/{$pengajuan->id}/status", [
                'status' => 'in_progress',
            ])
            ->assertStatus(200);

        $tokens = Queue::pushed(SendFirebaseNotificationJob::class)
            ->map(fn ($job) => $job->token)
            ->filter()
            ->values()->all();

        // Scope notifikasi murni role_kategori: PJ di luar scope tidak ikut.
        $this->assertNotContains($pjLuarScope->fcm_token, $tokens);
        $this->assertNotContains($this->admin->fcm_token, $tokens, 'Actor tidak menerima notifikasi aksinya sendiri');
        // Pemohon tetap harus tahu status pengajuannya berubah.
        $this->assertContains($this->pemohon->fcm_token, $tokens);
    }
}
