<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\Position;
use App\Models\UnitProses;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

class ConfirmationPopupTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    private function makeUser(string $email): User
    {
        $this->seedRolePermissions();
        Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);
        Position::create(['name' => 'User', 'code' => 'user', 'status' => true]);
        UnitProses::create(['name' => 'SIRS', 'code' => 'SIRS', 'status' => 1]);

        return User::create(['name' => 'U', 'email' => $email, 'password' => Hash::make('123'), 'phone' => '0811', 'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1]);
    }

    public function test_popup_muncul_saat_ada_tutup()
    {
        $u = $this->makeUser('a@x.com');
        $up = UnitProses::create(['name' => 'Sarana', 'code' => 'SRNS', 'status' => 1]);
        $sirs = UnitProses::where('code', 'SIRS')->first();
        $cat = Category::create(['name' => 'HW', 'status' => 1, 'unit_proses_id' => $sirs->id]);
        $loc = Location::create(['name' => 'UGD', 'status' => 1]);

        $this->actingAs($u, 'sanctum')->postJson('/api/tickets', [
            'category_id' => $cat->id, 'location_id' => $loc->id,
            'description' => 'Tiket A', 'priority' => 'high',
        ])->assertStatus(201);
        $this->actingAs($u, 'sanctum')->postJson('/api/order-perbaikan', [
            'unit_proses_code' => $up->code, 'jenis_barang' => 'Inventaris',
            'kode_inventaris' => 'INV-1', 'nama_barang' => 'AC', 'lokasi' => $loc->id,
            'keluhan' => 'rusak', 'prioritas' => 'RENDAH', 'tanggal' => now()->format('Y-m-d H:i:s'),
        ])->assertStatus(201);

        \App\Models\Ticket::where('user_id', $u->id)->update(['status' => 'closed']);
        \App\Models\OrderPerbaikan::where('created_by', $u->id)->update(['status' => 'tutup']);

        $r = $this->actingAs($u)->get('/user/dashboard');
        $r->assertStatus(200);
        $r->assertSee('Perlu Konfirmasi (2)', false);
    }

    public function test_popup_tidak_muncul_saat_tidak_ada_tutup()
    {
        $u = $this->makeUser('b@x.com');
        $r = $this->actingAs($u)->get('/user/dashboard');
        $r->assertStatus(200);
        $r->assertDontSee('Perlu Konfirmasi', false);
    }
}
