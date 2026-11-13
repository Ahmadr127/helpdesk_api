<?php

namespace Tests\Feature\Api;

use App\Models\Building;
use App\Models\Department;
use App\Models\Location;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

class MasterDataAndUserApiTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    protected User $admin;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolePermissions();
        Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);
        Position::create(['name' => 'IT', 'code' => 'IT', 'status' => true]);
        Position::create(['name' => 'User', 'code' => 'user', 'status' => true]);
        $this->admin = User::create(['name' => 'Admin IT', 'email' => 'admin', 'password' => Hash::make('123'), 'phone' => '0811', 'position' => 'IT', 'role' => 'admin', 'department' => 'IT', 'status' => 1]);
        $this->user = User::create(['name' => 'Regular', 'email' => 'user@example.com', 'password' => Hash::make('123'), 'phone' => '0812', 'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1]);
    }

    protected function authHeader(User $u)
    {
        return ['Authorization' => 'Bearer '.$u->createToken('test')->plainTextToken];
    }

    public function test_admin_can_crud_departments()
    {
        $headers = $this->authHeader($this->admin);
        $create = $this->withHeaders($headers)->postJson('/api/admin/master/departments', [
            'name' => 'Keuangan', 'code' => 'KEU', 'status' => true,
        ]);
        $create->assertStatus(201);
        $id = $create->json('data.id');
        $upd = $this->withHeaders($headers)->putJson("/api/admin/master/departments/{$id}", [
            'name' => 'Keuangan Updated', 'code' => 'KEU', 'status' => false,
        ]);
        $upd->assertStatus(200);
        $this->assertDatabaseHas('departments', ['id' => $id, 'status' => false]);

        $del = $this->withHeaders($headers)->deleteJson("/api/admin/master/departments/{$id}");
        $del->assertStatus(200);
        $this->assertDatabaseMissing('departments', ['id' => $id]);
    }

    public function test_admin_can_crud_buildings_and_locations()
    {
        $h = $this->authHeader($this->admin);
        $b = $this->withHeaders($h)->postJson('/api/admin/master/buildings', ['name' => 'Gedung B', 'code' => 'B', 'status' => true]);
        $b->assertStatus(201);
        $bid = $b->json('data.id');

        $l = $this->withHeaders($h)->postJson('/api/admin/master/locations', ['name' => 'Ruang OK', 'status' => true]);
        $l->assertStatus(201);
        $lid = $l->json('data.id');

        $bulk = $this->withHeaders($h)->postJson('/api/admin/master/locations/bulk-action', ['action' => 'deactivate', 'selected' => [$lid]]);
        $bulk->assertStatus(200);
        $this->assertDatabaseHas('locations', ['id' => $lid, 'status' => 0]);

        // lokasi nonaktif tetap bisa di-list admin via filter status
        $inactive = $this->withHeaders($h)->getJson('/api/admin/master/locations?status=0');
        $inactive->assertStatus(200)->assertJsonFragment(['id' => $lid]);
        $active = $this->withHeaders($h)->getJson('/api/admin/master/locations?status=1');
        $active->assertStatus(200)->assertJsonMissing(['id' => $lid]);
    }

    public function test_admin_can_crud_positions()
    {
        $h = $this->authHeader($this->admin);
        $pos = $this->withHeaders($h)->postJson('/api/admin/master/positions', ['name' => 'Dokter', 'code' => 'DR', 'status' => true]);
        $pos->assertStatus(201)->assertJsonPath('success', true);
        $id = $pos->json('data.id');

        $list = $this->withHeaders($h)->getJson('/api/admin/master/positions');
        $list->assertStatus(200)->assertJsonPath('success', true);

        $show = $this->withHeaders($h)->getJson("/api/admin/master/positions/{$id}");
        $show->assertStatus(200)->assertJsonPath('data.code', 'DR');

        $upd = $this->withHeaders($h)->putJson("/api/admin/master/positions/{$id}", ['name' => 'Dokter Umum', 'code' => 'DR', 'status' => true]);
        $upd->assertStatus(200)->assertJsonPath('data.name', 'Dokter Umum');

        $del = $this->withHeaders($h)->deleteJson("/api/admin/master/positions/{$id}");
        $del->assertStatus(200);
        $this->assertDatabaseMissing('positions', ['id' => $id]);
    }

    public function test_removed_master_types_are_rejected()
    {
        $h = $this->authHeader($this->admin);
        foreach (['categories', 'unit-proses', 'kategori-order'] as $type) {
            $this->withHeaders($h)->getJson("/api/admin/master/{$type}")
                ->assertStatus(404);
        }
    }

    public function test_user_cannot_access_admin_master()
    {
        $h = $this->authHeader($this->user);
        $resp = $this->withHeaders($h)->getJson('/api/admin/master/departments');
        $resp->assertStatus(403);
    }

    public function test_lookup_accessible_for_all_authenticated()
    {
        $hUser = $this->authHeader($this->user);
        $hAdmin = $this->authHeader($this->admin);
        Building::create(['name' => 'Gedung A', 'code' => 'A', 'status' => 1]);
        Location::create(['name' => 'UGD', 'status' => 1]);

        foreach (['/api/lookup/buildings', '/api/lookup/locations', '/api/lookup/departments', '/api/lookup/positions'] as $url) {
            $r = $this->getJson($url, $hUser);
            $r->assertStatus(200)->assertJsonPath('success', true);
        }
        $r2 = $this->getJson('/api/lookup/departments', $hAdmin);
        $r2->assertStatus(200);
    }

    public function test_admin_can_crud_users()
    {
        $h = $this->authHeader($this->admin);
        // list
        $list = $this->withHeaders($h)->getJson('/api/admin/users');
        $list->assertStatus(200);

        // create
        $create = $this->withHeaders($h)->postJson('/api/admin/users', [
            'name' => 'New User', 'email' => 'new@example.com', 'password' => '123', 'password_confirmation' => '123',
            'role' => 'user', 'department' => 'IT', 'status' => true, 'phone' => '0813', 'position' => 'user',
        ]);
        $create->assertStatus(201)->assertJsonPath('data.email', 'new@example.com');
        $id = $create->json('data.id');

        // show
        $show = $this->withHeaders($h)->getJson("/api/admin/users/{$id}");
        $show->assertStatus(200);

        // update
        $upd = $this->withHeaders($h)->putJson("/api/admin/users/{$id}", [
            'name' => 'Updated', 'email' => 'new@example.com', 'role' => 'user', 'department' => 'IT', 'status' => false, 'phone' => '0813', 'position' => 'user',
        ]);
        $upd->assertStatus(200)->assertJsonPath('data.status', false);

        // delete
        $del = $this->withHeaders($h)->deleteJson("/api/admin/users/{$id}");
        $del->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $id]);
    }

    public function test_user_cannot_manage_users()
    {
        $h = $this->authHeader($this->user);
        $resp = $this->withHeaders($h)->getJson('/api/admin/users');
        $resp->assertStatus(403);
    }

    public function test_dashboard_endpoints()
    {
        $uDash = $this->actingAs($this->user, 'sanctum')->getJson('/api/dashboard/user');
        $uDash->assertStatus(200)->assertJsonPath('success', true);

        $aDash = $this->actingAs($this->admin, 'sanctum')->getJson('/api/dashboard/admin');
        $aDash->assertStatus(200);

        $userForbiddenAdminDash = $this->actingAs($this->user, 'sanctum')->getJson('/api/dashboard/admin');
        $userForbiddenAdminDash->assertStatus(403);

        // administrasi dashboard
        $adminAdm = User::create(['name' => 'Adm Umum', 'email' => 'admumum', 'password' => Hash::make('123'), 'phone' => '0814', 'position' => 'Administrasi', 'role' => 'ipsrs', 'department' => 'IT', 'status' => 1]);
        $admDash = $this->actingAs($adminAdm, 'sanctum')->getJson('/api/dashboard/administrasi');
        $admDash->assertStatus(200);

        $health = $this->getJson('/api/health');
        $health->assertStatus(200)->assertJsonPath('success',true);
    }
}
