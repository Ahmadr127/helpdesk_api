<?php

namespace Tests\Feature\Api;

use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\PengajuanPenugasan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

/**
 * Penugasan & scope admin harus mengikuti role_kategori (sama seperti web),
 * bukan sekadar whereHas('kategori.jenis').
 */
class AdminPenugasanRoleKategoriTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    protected User $adminIt;

    protected User $adminLain;

    protected User $userBiasa;

    protected KategoriPengajuan $kategoriItA;

    protected KategoriPengajuan $kategoriItB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolePermissions();

        $jenisIt = JenisPengajuan::where('code', 'IT')->firstOrFail();

        $this->kategoriItA = KategoriPengajuan::create(['name' => 'Hardware', 'status' => 1]);
        $this->kategoriItA->syncJenis([$jenisIt->id]);

        $this->kategoriItB = KategoriPengajuan::create(['name' => 'Jaringan', 'status' => 1]);
        $this->kategoriItB->syncJenis([$jenisIt->id]);

        // Role admin hanya dipetakan ke kategori A.
        $adminRole = Role::where('slug', 'admin')->firstOrFail();
        $adminRole->kategoris()->sync([$this->kategoriItA->id]);

        $this->adminIt = $this->makeUser('Admin IT A', 'admin');
        $this->adminLain = $this->makeUser('Admin IT B', 'admin');
        $this->userBiasa = $this->makeUser('User Biasa', 'user');
    }

    protected function makeUser(string $name, string $role): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'password' => Hash::make('123'),
            'phone' => '0811',
            'position' => null,
            'role' => $role,
            'department' => null,
            'status' => 1,
        ]);
    }

    protected function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    protected function makePengajuan(KategoriPengajuan $kategori, User $pemohon, string $nomor): Pengajuan
    {
        return Pengajuan::create([
            'nomor' => $nomor,
            'kategori_id' => $kategori->id,
            'pemohon_id' => $pemohon->id,
            'pemohon_nama' => $pemohon->name,
            'deskripsi' => 'Kerusakan '.$nomor,
            'prioritas' => 'medium',
            'status' => 'open',
            'created_by' => $pemohon->id,
        ]);
    }

    public function test_admin_only_sees_pengajuan_of_its_role_categories(): void
    {
        $this->makePengajuan($this->kategoriItA, $this->userBiasa, 'T-1109-001');
        $this->makePengajuan($this->kategoriItB, $this->userBiasa, 'T-1109-002');

        $resp = $this->withHeaders($this->authHeader($this->adminIt))->getJson('/api/admin/tickets');

        $resp->assertStatus(200);
        $this->assertSame(1, $resp->json('meta.total'));
        $this->assertSame('T-1109-001', $resp->json('data.0.nomor'));
        // stats juga harus dari scope yang sama, bukan query global
        $this->assertSame(1, $resp->json('stats.total'));
    }

    public function test_admin_cannot_open_pengajuan_outside_role_category(): void
    {
        $diLuar = $this->makePengajuan($this->kategoriItB, $this->userBiasa, 'T-1109-003');

        $this->withHeaders($this->authHeader($this->adminIt))
            ->getJson("/api/admin/tickets/{$diLuar->id}")
            ->assertStatus(403);
    }

    public function test_penugasan_candidates_follow_role_kategori(): void
    {
        $pengajuan = $this->makePengajuan($this->kategoriItA, $this->userBiasa, 'T-1109-004');

        $resp = $this->withHeaders($this->authHeader($this->adminIt))
            ->getJson("/api/admin/pengajuan/{$pengajuan->id}/penugasan");

        $resp->assertStatus(200);
        $ids = collect($resp->json('data.candidates'))->pluck('id')->all();
        $this->assertContains($this->adminIt->id, $ids);
        $this->assertContains($this->adminLain->id, $ids);
        // user biasa tidak punya role yang ter-mapping ke kategori
        $this->assertNotContains($this->userBiasa->id, $ids);
    }

    public function test_sync_penugasan_rejects_non_candidate(): void
    {
        $pengajuan = $this->makePengajuan($this->kategoriItA, $this->userBiasa, 'T-1109-005');

        $resp = $this->withHeaders($this->authHeader($this->adminIt))
            ->putJson("/api/admin/pengajuan/{$pengajuan->id}/penugasan", [
                'user_ids' => [$this->userBiasa->id],
            ]);

        $resp->assertStatus(422);
        $this->assertArrayHasKey('user_ids', $resp->json('errors'));
        $this->assertDatabaseMissing('pengajuan_penugasan', [
            'pengajuan_id' => $pengajuan->id,
            'user_id' => $this->userBiasa->id,
        ]);
    }

    public function test_sync_penugasan_assigns_candidate_and_release_clears_it(): void
    {
        $pengajuan = $this->makePengajuan($this->kategoriItA, $this->userBiasa, 'T-1109-006');
        $headers = $this->authHeader($this->adminIt);

        $assign = $this->withHeaders($headers)->putJson("/api/admin/pengajuan/{$pengajuan->id}/penugasan", [
            'user_ids' => [$this->adminLain->id],
        ]);
        $assign->assertStatus(200);
        $this->assertSame([$this->adminLain->id], collect($assign->json('data.active'))->pluck('user_id')->all());

        // Resource pengajuan hanya menampilkan PJ aktif
        $show = $this->withHeaders($headers)->getJson("/api/admin/pengajuan/{$pengajuan->id}");
        $show->assertStatus(200);
        $this->assertSame([$this->adminLain->id], $show->json('data.penanggung_jawab_ids'));
        $this->assertSame('Admin IT B', $show->json('data.nama_penanggung_jawab'));

        // Lepas semua PJ -> resource tidak lagi menampilkan PJ lama
        $release = $this->withHeaders($headers)->putJson("/api/admin/pengajuan/{$pengajuan->id}/penugasan", [
            'user_ids' => [],
        ]);
        $release->assertStatus(200);
        $this->assertSame([], $release->json('data.active'));

        $show2 = $this->withHeaders($headers)->getJson("/api/admin/pengajuan/{$pengajuan->id}");
        $this->assertSame([], $show2->json('data.penanggung_jawab_ids'));
        $this->assertNull($show2->json('data.nama_penanggung_jawab'));

        // Baris penugasan lama tetap ada (soft release), bukan dihapus
        $this->assertDatabaseHas('pengajuan_penugasan', [
            'pengajuan_id' => $pengajuan->id,
            'user_id' => $this->adminLain->id,
        ]);
        $this->assertSame(1, PengajuanPenugasan::where('pengajuan_id', $pengajuan->id)->whereNotNull('released_at')->count());
    }

    public function test_pj_aktif_tetap_tunduk_pada_role_kategori(): void
    {
        $pengajuan = $this->makePengajuan($this->kategoriItB, $this->userBiasa, 'T-1109-007');

        // adminLain ditugaskan sebagai PJ pada kategori B yang di luar role-nya.
        PengajuanPenugasan::create([
            'pengajuan_id' => $pengajuan->id,
            'user_id' => $this->adminLain->id,
            'role_id' => Role::where('slug', 'admin')->value('id'),
            'assigned_at' => now(),
        ]);

        // Scope tetap murni role_kategori: penugasan aktual TIDAK menambah hak lihat.
        $this->withHeaders($this->authHeader($this->adminLain))
            ->getJson("/api/admin/tickets/{$pengajuan->id}")
            ->assertStatus(403);

        $list = $this->withHeaders($this->authHeader($this->adminLain))->getJson('/api/admin/tickets');
        $this->assertSame(0, $list->json('meta.total'));
    }

    public function test_admin_tanpa_role_kategori_tidak_melihat_pengajuan_apa_pun(): void
    {
        // Role tanpa mapping role_kategori = tanpa scope (fail-closed).
        // Admin tidak boleh "jatuh" ke semua kategori sejenis.
        Role::where('slug', 'admin')->firstOrFail()->kategoris()->sync([]);
        DB::table('role_kategori')->delete();

        $this->makePengajuan($this->kategoriItA, $this->userBiasa, 'T-1109-008');
        $this->makePengajuan($this->kategoriItB, $this->userBiasa, 'T-1109-009');

        $resp = $this->withHeaders($this->authHeader($this->adminIt))->getJson('/api/admin/tickets');

        $resp->assertStatus(200);
        $this->assertSame(0, $resp->json('meta.total'));
        $this->assertSame(0, $resp->json('stats.total'));
        $this->assertSame([], $resp->json('data'));
    }

    public function test_kategori_tanpa_mapping_tidak_punya_kandidat_pj(): void
    {
        // Kategori B tidak terhubung ke role mana pun -> kandidat PJ kosong.
        DB::table('role_kategori')->where('kategori_id', $this->kategoriItB->id)->delete();

        $pengajuan = $this->makePengajuan($this->kategoriItB, $this->userBiasa, 'T-1109-010');

        // adminIt tidak punya scope ke kategori B, jadi tidak bisa membuka penugasan.
        $this->withHeaders($this->authHeader($this->adminIt))
            ->getJson("/api/admin/pengajuan/{$pengajuan->id}/penugasan")
            ->assertStatus(403);

        // Kandidat dihitung langsung dari service: harus kosong, bukan fallback role.
        $this->assertCount(
            0,
            app(\App\Services\Pengajuan\PengajuanPenugasanService::class)->candidates($pengajuan)
        );
    }
}
