<?php

namespace Tests\Feature\Api;

use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

/**
 * Regresi: list order (`/order-perbaikan` & admin) WAJIB memakai scope `umum()`
 * — semua jenis selain SIRS, termasuk RT — bukan kode `IPSRS` persis.
 *
 * Sebelumnya route menitipkan `jenis=IPSRS`, sehingga order berkategori RT
 * (yang bisa dipilih di form karena form memakai `umum()`) tersimpan tapi
 * TIDAK PERNAH muncul di list. Test ini mengunci agar hal itu tidak terulang.
 */
class OrderScopeUmumTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    protected User $pemohon;

    protected User $adminIpsrs;

    protected KategoriPengajuan $kategoriIpsrs;

    protected KategoriPengajuan $kategoriRt;

    protected KategoriPengajuan $kategoriIt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolePermissions();

        $jenisIt = JenisPengajuan::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Tiket IT / SIRS', 'prefix' => 'TIK', 'is_active' => true]
        );
        $jenisIpsrs = JenisPengajuan::firstOrCreate(
            ['code' => 'IPSRS'],
            ['name' => 'Order Perbaikan / IPSRS', 'prefix' => 'OPR', 'is_active' => true]
        );
        $jenisRt = JenisPengajuan::firstOrCreate(
            ['code' => 'RT'],
            ['name' => 'Rumah Tangga', 'prefix' => 'RTG', 'is_active' => true]
        );

        $this->kategoriIt = KategoriPengajuan::create(['name' => 'Hardware', 'status' => 1]);
        $this->kategoriIt->syncJenis([$jenisIt->id]);

        $this->kategoriIpsrs = KategoriPengajuan::create(['name' => 'Listrik', 'status' => 1]);
        $this->kategoriIpsrs->syncJenis([$jenisIpsrs->id]);

        $this->kategoriRt = KategoriPengajuan::create(['name' => 'Rumah Tangga Uji', 'status' => 1]);
        $this->kategoriRt->syncJenis([$jenisRt->id]);

        // Admin IPSRS melihat semua kategori umum (termasuk RT).
        $ipsrsRole = Role::where('slug', 'ipsrs')->firstOrFail();
        $ipsrsRole->kategoris()->sync([$this->kategoriIpsrs->id, $this->kategoriRt->id]);

        $this->pemohon = $this->makeUser('Pemohon', 'user');
        $this->adminIpsrs = $this->makeUser('Admin Ipsrs', 'ipsrs');
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

    protected function makePengajuan(KategoriPengajuan $kategori, string $nomor, string $status = 'open'): Pengajuan
    {
        return Pengajuan::create([
            'nomor' => $nomor,
            'kategori_id' => $kategori->id,
            'pemohon_id' => $this->pemohon->id,
            'pemohon_nama' => $this->pemohon->name,
            'deskripsi' => 'Kerusakan '.$nomor,
            'prioritas' => 'medium',
            'status' => $status,
            'created_by' => $this->pemohon->id,
        ]);
    }

    public function test_order_rt_muncul_di_list_order_user(): void
    {
        $ipsrs = $this->makePengajuan($this->kategoriIpsrs, 'O-001');
        $rt = $this->makePengajuan($this->kategoriRt, 'O-002');

        $resp = $this->withHeaders($this->authHeader($this->pemohon))
            ->getJson('/api/order-perbaikan');

        $resp->assertStatus(200);
        // Keduanya flavour umum -> harus tampil.
        $this->assertSame(2, $resp->json('meta.total'));
        $nomors = collect($resp->json('data'))->pluck('nomor')->all();
        $this->assertContains($ipsrs->nomor, $nomors);
        $this->assertContains($rt->nomor, $nomors);
    }

    public function test_list_order_tidak_menampilkan_tiket_sirs(): void
    {
        $this->makePengajuan($this->kategoriIpsrs, 'O-001');
        $this->makePengajuan($this->kategoriIt, 'T-001');

        $resp = $this->withHeaders($this->authHeader($this->pemohon))
            ->getJson('/api/order-perbaikan');

        $resp->assertStatus(200);
        // SIRS bukan flavour umum -> tidak boleh ikut.
        $this->assertSame(1, $resp->json('meta.total'));
        $this->assertSame('O-001', $resp->json('data.0.nomor'));
    }

    public function test_detail_order_rt_bisa_dibuka(): void
    {
        $rt = $this->makePengajuan($this->kategoriRt, 'O-002');

        $this->withHeaders($this->authHeader($this->pemohon))
            ->getJson('/api/order-perbaikan/'.$rt->id)
            ->assertStatus(200)
            ->assertJsonPath('data.nomor', 'O-002');
    }

    public function test_detail_tiket_sirs_ditolak_di_route_order(): void
    {
        $it = $this->makePengajuan($this->kategoriIt, 'T-001');

        $this->withHeaders($this->authHeader($this->pemohon))
            ->getJson('/api/order-perbaikan/'.$it->id)
            ->assertStatus(403);
    }

    public function test_detail_order_rt_ditolak_di_route_tiket(): void
    {
        $rt = $this->makePengajuan($this->kategoriRt, 'O-002');

        $this->withHeaders($this->authHeader($this->pemohon))
            ->getJson('/api/tickets/'.$rt->id)
            ->assertStatus(403);
    }

    public function test_admin_order_list_memakai_scope_umum(): void
    {
        $this->makePengajuan($this->kategoriIpsrs, 'O-001');
        $this->makePengajuan($this->kategoriRt, 'O-002');

        $resp = $this->withHeaders($this->authHeader($this->adminIpsrs))
            ->getJson('/api/administrasi-umum/order-perbaikan');

        $resp->assertStatus(200);
        // Admin melihat kategori IPSRS + RT (keduanya umum).
        $this->assertSame(2, $resp->json('meta.total'));
    }

    public function test_order_konfirmasi_dan_rejected_memakai_scope_umum(): void
    {
        $this->makePengajuan($this->kategoriRt, 'O-001', 'ditutup');
        $this->makePengajuan($this->kategoriRt, 'O-002', 'rejected');

        $this->withHeaders($this->authHeader($this->pemohon))
            ->getJson('/api/order-perbaikan/konfirmasi')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1);

        $this->withHeaders($this->authHeader($this->pemohon))
            ->getJson('/api/order-perbaikan/rejected')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }
}
