<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Location;
use App\Models\Pengajuan;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

/**
 * Kontrak `/api/dashboard/summary`:
 * - seluruh angka (termasuk `active`/`pending`/`done`) dihitung di backend;
 * - scope `own` hanya milik user yang login;
 * - scope `admin` hanya diisi bila user berhak (dashboard.it + ticket,
 *   atau dashboard.ipsrs + order) dan angkanya = jumlah baris di list admin.
 */
class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    protected User $userA;

    protected User $userB;

    protected KategoriPengajuan $kategoriTiket;

    protected KategoriPengajuan $kategoriOrder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolePermissions();

        $department = Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);
        Position::create(['name' => 'User', 'code' => 'user', 'status' => true]);
        $location = Location::create(['name' => 'UGD', 'status' => 1]);

        $jenisIt = JenisPengajuan::where('code', 'IT')->firstOrFail();
        $jenisIpsrs = JenisPengajuan::where('code', 'IPSRS')->firstOrFail()
            ?? JenisPengajuan::create(['code' => 'IPSRS', 'name' => 'IPSRS', 'status' => 1]);

        $this->kategoriTiket = KategoriPengajuan::create(['name' => 'Jaringan', 'code' => 'JRG', 'status' => 1]);
        $this->kategoriTiket->syncJenis([$jenisIt->id]);

        $this->kategoriOrder = KategoriPengajuan::create(['name' => 'AC', 'code' => 'AC', 'status' => 1]);
        $this->kategoriOrder->syncJenis([$jenisIpsrs->id]);

        // Admin IT punya kategori tiket saja (scope role_kategori).
        Role::where('slug', 'admin')->firstOrFail()->kategoris()->sync([$this->kategoriTiket->id]);
        Role::where('slug', 'ipsrs')->firstOrFail()->kategoris()->sync([$this->kategoriOrder->id]);

        $this->userA = User::create([
            'name' => 'A', 'email' => 'a@x.com', 'password' => Hash::make('123'),
            'phone' => '0811', 'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1,
        ]);
        $this->userB = User::create([
            'name' => 'B', 'email' => 'b@x.com', 'password' => Hash::make('123'),
            'phone' => '0812', 'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1,
        ]);
    }

    protected function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('t')->plainTextToken];
    }

    protected function makePengajuan(User $pemohon, KategoriPengajuan $kategori, string $status, string $nomor): Pengajuan
    {
        return Pengajuan::create([
            'nomor' => $nomor,
            'kategori_id' => $kategori->id,
            'pemohon_id' => $pemohon->id,
            'pemohon_nama' => $pemohon->name,
            'deskripsi' => 'Keluhan '.$nomor,
            'prioritas' => 'medium',
            'status' => $status,
            'tanggal' => now(),
            'created_by' => $pemohon->id,
        ]);
    }

    public function test_summary_menghitung_own_sendiri_saja(): void
    {
        // A: 1 tiket open + 1 order in_progress
        $this->makePengajuan($this->userA, $this->kategoriTiket, 'open', 'T-A1');
        $this->makePengajuan($this->userA, $this->kategoriOrder, 'in_progress', 'O-A1');
        // B: 1 tiket open
        $this->makePengajuan($this->userB, $this->kategoriTiket, 'open', 'T-B1');

        $res = $this->withHeaders($this->authHeader($this->userA))
            ->getJson('/api/dashboard/summary')
            ->assertStatus(200);

        $res->assertJsonPath('data.own.tickets.total', 1);
        $res->assertJsonPath('data.own.tickets.open', 1);
        $res->assertJsonPath('data.own.tickets.active', 1);
        $res->assertJsonPath('data.own.orders.total', 1);
        $res->assertJsonPath('data.own.orders.in_progress', 1);
        $res->assertJsonPath('data.own.orders.active', 1);
    }

    public function test_summary_menghitung_turunan_status(): void
    {
        $this->makePengajuan($this->userA, $this->kategoriTiket, 'open', 'T-1');
        $this->makePengajuan($this->userA, $this->kategoriTiket, 'in_progress', 'T-2');
        $this->makePengajuan($this->userA, $this->kategoriTiket, 'ditutup', 'T-3');
        $this->makePengajuan($this->userA, $this->kategoriTiket, 'confirmed', 'T-4');
        $this->makePengajuan($this->userA, $this->kategoriTiket, 'rejected', 'T-5');

        $res = $this->withHeaders($this->authHeader($this->userA))
            ->getJson('/api/dashboard/summary')
            ->assertStatus(200);

        $res->assertJsonPath('data.own.tickets.total', 5);
        $res->assertJsonPath('data.own.tickets.open', 1);
        $res->assertJsonPath('data.own.tickets.in_progress', 1);
        $res->assertJsonPath('data.own.tickets.ditutup', 1);
        $res->assertJsonPath('data.own.tickets.confirmed', 1);
        $res->assertJsonPath('data.own.tickets.rejected', 1);
        // active = open + in_progress
        $res->assertJsonPath('data.own.tickets.active', 2);
        // pending = ditutup (menunggu konfirmasi user)
        $res->assertJsonPath('data.own.tickets.pending', 1);
        // done = confirmed + rejected
        $res->assertJsonPath('data.own.tickets.done', 2);
    }

    public function test_user_biasa_tidak_mendapat_section_admin(): void
    {
        $this->makePengajuan($this->userB, $this->kategoriTiket, 'open', 'T-B1');

        $res = $this->withHeaders($this->authHeader($this->userA))
            ->getJson('/api/dashboard/summary')
            ->assertStatus(200);

        $this->assertNull($res->json('data.admin.tickets'), 'User biasa tidak boleh dapat stat admin tiket.');
        $this->assertNull($res->json('data.admin.orders'), 'User biasa tidak boleh dapat stat admin order.');
    }

    public function test_admin_it_mendapat_stat_ticket_sesuai_scope_role_kategori(): void
    {
        $adminIt = User::create([
            'name' => 'Admin IT', 'email' => 'it@x.com', 'password' => Hash::make('123'),
            'phone' => '0813', 'position' => 'IT', 'role' => 'admin', 'department' => 'IT', 'status' => 1,
        ]);

        // Tiket kategori milik role admin IT (visible), 1 open + 1 in_progress.
        $this->makePengajuan($this->userB, $this->kategoriTiket, 'open', 'T-V1');
        $this->makePengajuan($this->userB, $this->kategoriTiket, 'in_progress', 'T-V2');
        // Order di luar scope admin IT (kategori IPSRS) — tidak boleh ikut.
        $this->makePengajuan($this->userB, $this->kategoriOrder, 'open', 'O-V1');

        $res = $this->withHeaders($this->authHeader($adminIt))
            ->getJson('/api/dashboard/summary')
            ->assertStatus(200);

        $res->assertJsonPath('data.admin.tickets.total', 2);
        $res->assertJsonPath('data.admin.tickets.active', 2);
        // Admin IT tidak punya scope order IPSRS.
        $this->assertNull($res->json('data.admin.orders'));
    }

    public function test_admin_ipsrs_mendapat_stat_order_sesuai_scope_role_kategori(): void
    {
        $adminIpsrs = User::create([
            'name' => 'Admin IPSRS', 'email' => 'ipsrs@x.com', 'password' => Hash::make('123'),
            'phone' => '0814', 'position' => 'Administrasi', 'role' => 'ipsrs', 'department' => 'IT', 'status' => 1,
        ]);

        $this->makePengajuan($this->userB, $this->kategoriOrder, 'open', 'O-V1');
        $this->makePengajuan($this->userB, $this->kategoriOrder, 'ditutup', 'O-V2');
        $this->makePengajuan($this->userB, $this->kategoriTiket, 'open', 'T-V1');

        $res = $this->withHeaders($this->authHeader($adminIpsrs))
            ->getJson('/api/dashboard/summary')
            ->assertStatus(200);

        $res->assertJsonPath('data.admin.orders.total', 2);
        $res->assertJsonPath('data.admin.orders.open', 1);
        $res->assertJsonPath('data.admin.orders.ditutup', 1);
        $res->assertJsonPath('data.admin.orders.pending', 1);
        // Admin IPSRS tidak punya scope tiket.
        $this->assertNull($res->json('data.admin.tickets'));
    }

    public function test_angka_admin_sama_dengan_jumlah_baris_list(): void
    {
        $adminIt = User::create([
            'name' => 'Admin IT', 'email' => 'it@x.com', 'password' => Hash::make('123'),
            'phone' => '0813', 'position' => 'IT', 'role' => 'admin', 'department' => 'IT', 'status' => 1,
        ]);

        $this->makePengajuan($this->userB, $this->kategoriTiket, 'open', 'T-V1');
        $this->makePengajuan($this->userB, $this->kategoriTiket, 'in_progress', 'T-V2');
        $this->makePengajuan($this->userB, $this->kategoriTiket, 'ditutup', 'T-V3');

        $summary = $this->withHeaders($this->authHeader($adminIt))
            ->getJson('/api/dashboard/summary')
            ->assertStatus(200);

        $list = $this->withHeaders($this->authHeader($adminIt))
            ->getJson('/api/admin/tickets')
            ->assertStatus(200);

        $this->assertSame(
            count($list->json('data')),
            $summary->json('data.admin.tickets.total'),
            'Stat admin tiket harus sama dengan jumlah baris di list admin tiket.'
        );
    }
}
