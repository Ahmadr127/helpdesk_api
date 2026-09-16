<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Location;
use App\Models\OrderPerbaikan;
use App\Models\Role;
use App\Models\UnitProses;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

class AdminIpsrsNavigationTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    private function makeUser(string $role, string $email): User
    {
        Role::firstOrCreate(['slug' => $role], ['slug' => $role, 'name' => $role]);

        return User::create([
            'name' => 'Test '.$role,
            'email' => $email,
            'password' => Hash::make('secret123'),
            'phone' => '0811',
            'role' => $role,
            'position' => null,
            'department' => 'IT',
            'status' => 1,
        ]);
    }

    public function test_ipsrs_pages_render_inside_admin_shell(): void
    {
        $this->seedRolePermissions();
        $building = Building::create(['name' => 'Gedung A', 'code' => 'A', 'status' => 1]);
        $location = Location::create(['name' => 'UGD', 'building_id' => $building->id, 'status' => 1]);
        UnitProses::create(['name' => 'Sarana (IPSRS)', 'code' => 'SRNS', 'status' => 1]);
        $user = $this->makeUser('user', 'user@example.com');
        $ipsrs = $this->makeUser('ipsrs', 'ipsrs@example.com');
        $order = OrderPerbaikan::create([
            'nomor' => 'OP/RTG/MTC-20250101001', 'tanggal' => now(),
            'unit_proses' => 'SRNS', 'unit_proses_name' => 'Sarana',
            'unit_penerima' => 'MTC', 'nama_peminta' => $user->name,
            'jenis_barang' => 'Umum', 'kode_inventaris' => 'INV-1',
            'nama_barang' => 'AC', 'lokasi' => $location->id,
            'keluhan' => 'Rusak', 'prioritas' => 'RENDAH',
            'status' => 'open', 'created_by' => $user->id,
        ]);

        foreach ([
            route('admin.ipsrs.dashboard'),
            route('admin.order-perbaikan.index'),
            route('admin.order-perbaikan.in-progress'),
            route('admin.order-perbaikan.confirmed'),
            route('admin.order-perbaikan.rejected'),
            route('admin.order-perbaikan.total'),
            route('admin.order-perbaikan.show', $order),
        ] as $url) {
            $this->actingAs($ipsrs)->get($url)->assertStatus(200);
        }

        // User biasa tidak boleh masuk area admin.
        $this->actingAs($user)->get(route('admin.order-perbaikan.index'))
            ->assertRedirect(route('user.dashboard'));
    }

    public function test_legacy_administrasi_umum_urls_redirect_to_admin(): void
    {
        $this->seedRolePermissions();
        $ipsrs = $this->makeUser('ipsrs', 'ipsrs@example.com');

        $this->actingAs($ipsrs)->get(route('administrasi-umum.dashboard'))
            ->assertRedirect(route('admin.ipsrs.dashboard'));
        $this->actingAs($ipsrs)->get(route('administrasi-umum.order-perbaikan.index'))
            ->assertRedirect(route('admin.order-perbaikan.index'));
    }

    public function test_ipsrs_dashboard_redirects_by_permission(): void
    {
        $this->seedRolePermissions();

        $this->post('/login', ['login' => $this->makeUser('ipsrs', 'i@example.com')->email, 'password' => 'secret123'])
            ->assertRedirect(route('admin.ipsrs.dashboard'));
    }
}
