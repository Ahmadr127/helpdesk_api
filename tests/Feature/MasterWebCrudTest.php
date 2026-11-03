<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Category;
use App\Models\Department;
use App\Models\KategoriOrder;
use App\Models\Location;
use App\Models\Position;
use App\Models\UnitProses;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

class MasterWebCrudTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolePermissions();
        Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);
        Position::create(['name' => 'IT', 'code' => 'IT', 'status' => true]);
        $up = UnitProses::create(['name' => 'SIRS', 'code' => 'SIRS', 'status' => 1]);
        Category::create(['name' => 'Hardware', 'status' => 1, 'unit_proses_id' => $up->id]);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin', 'password' => Hash::make('123'), 'phone' => '0811', 'position' => 'IT', 'role' => 'admin', 'department' => 'IT', 'status' => 1]);
    }

    public function test_getdata_returns_200_for_all_master_types()
    {
        foreach (['categories', 'departments', 'buildings', 'locations', 'unit-proses', 'kategori-order', 'positions'] as $type) {
            $r = $this->actingAs($this->admin)->getJson("/master/{$type}/data?limit=5");
            $r->assertStatus(200)->assertJsonPath('success', true);
            $this->assertArrayHasKey('html', $r->json());
            $this->assertArrayHasKey('count', $r->json());
        }
    }

    public function test_getdata_supports_filters_and_rejects_invalid_type()
    {
        $this->actingAs($this->admin)->getJson('/master/categories/data?search=hardware')
            ->assertStatus(200)->assertJsonPath('count', 1);

        $this->actingAs($this->admin)->getJson('/master/locations/data?status=0')
            ->assertStatus(200)->assertJsonPath('success', true);

        $this->actingAs($this->admin)->getJson('/master/foobar/data')
            ->assertStatus(422)->assertJsonPath('success', false);

        // varian underscore tetap diterima
        $this->actingAs($this->admin)->getJson('/master/unit_proses/data')
            ->assertStatus(200)->assertJsonPath('success', true);
        $this->actingAs($this->admin)->getJson('/master/kategori_order/data')
            ->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_web_bulk_action_supports_positions()
    {
        $p = Position::create(['name' => 'Dokter', 'code' => 'DR', 'status' => true]);

        $this->actingAs($this->admin)
            ->post('/master/positions/bulk-action', ['action' => 'deactivate', 'selected' => [$p->id]])
            ->assertStatus(302);
        $this->assertDatabaseHas('positions', ['id' => $p->id, 'status' => false]);
    }

    public function test_web_store_update_destroy_all_types()
    {
        $up = UnitProses::where('code', 'SIRS')->first();
        $admin = $this->actingAs($this->admin);

        $admin->post('/master/categories', ['name' => 'Cat1', 'status' => 1, 'unit_proses_id' => $up->id])->assertStatus(302);
        $cat = Category::where('name', 'Cat1')->firstOrFail();
        $admin->put("/master/categories/{$cat->id}", ['name' => 'Cat1U', 'status' => 1, 'unit_proses_id' => $up->id])->assertStatus(302);
        $admin->delete("/master/categories/{$cat->id}")->assertStatus(302);

        $admin->post('/master/unit-proses', ['name' => 'UP1', 'code' => 'UP1', 'status' => 1])->assertStatus(302);
        $u = UnitProses::where('code', 'UP1')->firstOrFail();
        $admin->put("/master/unit-proses/{$u->id}", ['name' => 'UP1U', 'code' => 'UP1', 'status' => 1])->assertStatus(302);
        $admin->delete("/master/unit-proses/{$u->id}")->assertStatus(302);

        $admin->post('/master/kategori-order', ['name' => 'KO1', 'code' => 'KO1', 'status' => 1])->assertStatus(302);
        $k = KategoriOrder::where('code', 'KO1')->firstOrFail();
        $admin->put("/master/kategori-order/{$k->id}", ['name' => 'KO1U', 'code' => 'KO1', 'status' => 1])->assertStatus(302);
        $admin->delete("/master/kategori-order/{$k->id}")->assertStatus(302);

        $admin->post('/master/buildings', ['name' => 'G1', 'code' => 'G1', 'status' => 1])->assertStatus(302);
        $b = Building::where('code', 'G1')->firstOrFail();
        $admin->put("/master/buildings/{$b->id}", ['name' => 'G1U', 'code' => 'G1', 'status' => 1])->assertStatus(302);
        $admin->delete("/master/buildings/{$b->id}")->assertStatus(302);

        $admin->post('/master/locations', ['name' => 'L1', 'status' => 1])->assertStatus(302);
        $l = Location::where('name', 'L1')->firstOrFail();
        $admin->put("/master/locations/{$l->id}", ['name' => 'L1U', 'status' => 1])->assertStatus(302);
        $admin->delete("/master/locations/{$l->id}")->assertStatus(302);

        $admin->post('/master/departments', ['name' => 'D1', 'code' => 'D1', 'status' => 1])->assertStatus(302);
        $d = Department::where('code', 'D1')->firstOrFail();
        $admin->put("/master/departments/{$d->id}", ['name' => 'D1U', 'code' => 'D1', 'status' => 1])->assertStatus(302);
        $admin->delete("/master/departments/{$d->id}")->assertStatus(302);

        $admin->post('/master/positions', ['name' => 'P1', 'code' => 'P1'])->assertStatus(302);
        $p = Position::where('code', 'P1')->firstOrFail();
        $admin->put("/master/positions/{$p->id}", ['name' => 'P1U', 'code' => 'P1'])->assertStatus(302);
        $admin->delete("/master/positions/{$p->id}")->assertStatus(302);
    }

    public function test_removed_resource_routes_do_not_500()
    {
        // create/show/edit tidak di-register (only index,store,update,destroy):
        // jatuh ke fallback redirect login (302), bukan 500 undefined method.
        $this->actingAs($this->admin)->get('/master/buildings/create')->assertStatus(302);
        $this->actingAs($this->admin)->get('/master/buildings/1/edit')->assertStatus(302);
    }

    public function test_master_pages_point_forms_to_correct_urls()
    {
        // URL route master itu /master/... (tanpa prefix admin).
        // Form edit/delete modal harus mengarah ke sana, bukan admin/master/...
        // yang menyebabkan 405 Method Not Allowed saat PUT/DELETE.
        foreach ([
            '/master/categories',
            '/master/departments',
            '/master/buildings',
            '/master/locations',
            '/master/unit-proses',
            '/master/kategori-order',
            '/master/positions',
        ] as $url) {
            $content = $this->actingAs($this->admin)->get($url)->assertStatus(200)->getContent();
            $this->assertStringNotContainsString('admin/master', $content, "wrong form URL on {$url}");
        }
    }
}
