<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Building;
use App\Models\Category;
use App\Models\Location;
use App\Models\Position;
use App\Models\UnitProses;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataAndUserApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Department::create(['name'=>'IT','code'=>'IT','status'=>1]);
        Position::create(['name'=>'IT','code'=>'IT','status'=>true]);
        Position::create(['name'=>'User','code'=>'user','status'=>true]);
        UnitProses::create(['name'=>'SIRS','code'=>'SIRS','status'=>1]);
        $this->admin = User::create(['name'=>'Admin IT','email'=>'admin','password'=>Hash::make('123'),'phone'=>'0811','position'=>'IT','role'=>'admin','department'=>'IT','status'=>1]);
        $this->user = User::create(['name'=>'Regular','email'=>'user@example.com','password'=>Hash::make('123'),'phone'=>'0812','position'=>'user','role'=>'user','department'=>'IT','status'=>1]);
    }

    protected function authHeader(User $u){ return ['Authorization'=>'Bearer '.$u->createToken('test')->plainTextToken]; }

    public function test_admin_can_crud_categories()
    {
        $headers = $this->authHeader($this->admin);
        $up = UnitProses::where('code','SIRS')->first();

        // create
        $create = $this->withHeaders($headers)->postJson('/api/admin/master/categories', [
            'name'=>'Software','status'=>true,'unit_proses_id'=>$up->id
        ]);
        $create->assertStatus(201)->assertJsonPath('success',true);
        $id = $create->json('data.id');

        // list
        $list = $this->withHeaders($headers)->getJson('/api/admin/master/categories');
        $list->assertStatus(200)->assertJsonCount(1, 'data');

        // show
        $show = $this->withHeaders($headers)->getJson("/api/admin/master/categories/{$id}");
        $show->assertStatus(200);

        // update
        $upd = $this->withHeaders($headers)->putJson("/api/admin/master/categories/{$id}", [
            'name'=>'Software Updated','status'=>true,'unit_proses_id'=>$up->id
        ]);
        $upd->assertStatus(200)->assertJsonPath('data.name','Software Updated');

        // delete
        $del = $this->withHeaders($headers)->deleteJson("/api/admin/master/categories/{$id}");
        $del->assertStatus(200);
        $this->assertDatabaseMissing('categories',['id'=>$id]);
    }

    public function test_admin_can_crud_departments()
    {
        $headers = $this->authHeader($this->admin);
        $create = $this->withHeaders($headers)->postJson('/api/admin/master/departments', [
            'name'=>'Keuangan','code'=>'KEU','status'=>true
        ]);
        $create->assertStatus(201);
        $id = $create->json('data.id');
        $upd = $this->withHeaders($headers)->putJson("/api/admin/master/departments/{$id}", [
            'name'=>'Keuangan Updated','code'=>'KEU','status'=>false
        ]);
        $upd->assertStatus(200);
        $this->assertDatabaseHas('departments',['id'=>$id,'status'=>false]);
    }

    public function test_admin_can_crud_buildings_and_locations()
    {
        $h = $this->authHeader($this->admin);
        $b = $this->withHeaders($h)->postJson('/api/admin/master/buildings', ['name'=>'Gedung B','code'=>'B','status'=>true]);
        $b->assertStatus(201);
        $bid = $b->json('data.id');

        $l = $this->withHeaders($h)->postJson('/api/admin/master/locations', ['name'=>'Ruang OK','building_id'=>$bid,'status'=>true]);
        $l->assertStatus(201);
        $lid = $l->json('data.id');

        $bulk = $this->withHeaders($h)->postJson('/api/admin/master/locations/bulk-action', ['action'=>'deactivate','selected'=>[$lid]]);
        $bulk->assertStatus(200);
        $this->assertDatabaseHas('locations',['id'=>$lid,'status'=>0]);
    }

    public function test_admin_can_crud_positions_and_unit_proses()
    {
        $h = $this->authHeader($this->admin);
        $pos = $this->withHeaders($h)->postJson('/api/admin/master/positions', ['name'=>'Dokter','code'=>'DR','status'=>true]);
        $pos->assertStatus(201);
        $up = $this->withHeaders($h)->postJson('/api/admin/master/unit-proses', ['name'=>'IPSRS Baru','code'=>'IPSRS','status'=>true]);
        $up->assertStatus(201);
    }

    public function test_user_cannot_access_admin_master()
    {
        $h = $this->authHeader($this->user);
        $resp = $this->withHeaders($h)->getJson('/api/admin/master/categories');
        $resp->assertStatus(403);
    }

    public function test_lookup_accessible_for_all_authenticated()
    {
        $hUser = $this->authHeader($this->user);
        $hAdmin = $this->authHeader($this->admin);
        // create some data
        $up = UnitProses::where('code','SIRS')->first();
        Category::create(['name'=>'Hardware','status'=>1,'unit_proses_id'=>$up->id]);
        $b = Building::create(['name'=>'Gedung A','code'=>'A','status'=>1]);
        Location::create(['name'=>'UGD','building_id'=>$b->id,'status'=>1]);

        foreach (['/api/lookup/categories','/api/lookup/buildings','/api/lookup/locations','/api/lookup/departments','/api/lookup/unit-proses','/api/lookup/positions'] as $url){
            $r = $this->getJson($url, $hUser);
            $r->assertStatus(200)->assertJsonPath('success',true);
        }
        $r2 = $this->getJson('/api/lookup/categories', $hAdmin);
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
            'name'=>'New User','email'=>'new@example.com','password'=>'123','password_confirmation'=>'123',
            'role'=>'user','department'=>'IT','status'=>true,'phone'=>'0813','position'=>'user'
        ]);
        $create->assertStatus(201)->assertJsonPath('data.email','new@example.com');
        $id = $create->json('data.id');

        // show
        $show = $this->withHeaders($h)->getJson("/api/admin/users/{$id}");
        $show->assertStatus(200);

        // update
        $upd = $this->withHeaders($h)->putJson("/api/admin/users/{$id}", [
            'name'=>'Updated','email'=>'new@example.com','role'=>'user','department'=>'IT','status'=>false,'phone'=>'0813','position'=>'user'
        ]);
        $upd->assertStatus(200)->assertJsonPath('data.status',false);

        // delete
        $del = $this->withHeaders($h)->deleteJson("/api/admin/users/{$id}");
        $del->assertStatus(200);
        $this->assertDatabaseMissing('users',['id'=>$id]);
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
        $uDash->assertStatus(200)->assertJsonPath('success',true);

        $aDash = $this->actingAs($this->admin, 'sanctum')->getJson('/api/dashboard/admin');
        $aDash->assertStatus(200);

        $userForbiddenAdminDash = $this->actingAs($this->user, 'sanctum')->getJson('/api/dashboard/admin');
        $userForbiddenAdminDash->assertStatus(403);

        // administrasi dashboard
        $adminAdm = User::create(['name'=>'Adm Umum','email'=>'admumum','password'=>Hash::make('123'),'phone'=>'0814','position'=>'Administrasi','role'=>'admin','department'=>'IT','status'=>1]);
        $admDash = $this->actingAs($adminAdm, 'sanctum')->getJson('/api/dashboard/administrasi');
        $admDash->assertStatus(200);

        $health = $this->getJson('/api/health');
        $health->assertStatus(200)->assertJsonPath('success',true);
    }
}
