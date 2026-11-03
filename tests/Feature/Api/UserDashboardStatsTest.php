<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Location;
use App\Models\Position;
use App\Models\UnitProses;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

class UserDashboardStatsTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    public function test_stat_myticket_dan_myorder_hanya_milik_sendiri()
    {
        $this->seedRolePermissions();
        Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);
        Position::create(['name' => 'User', 'code' => 'user', 'status' => true]);
        $sirs = UnitProses::create(['name' => 'SIRS', 'code' => 'SIRS', 'status' => 1]);
        $up = UnitProses::create(['name' => 'Sarana (IPSRS)', 'code' => 'SRNS', 'status' => 1]);
        $loc = Location::create(['name' => 'UGD', 'status' => 1]);
        $cat = \App\Models\Category::create(['name' => 'Hardware', 'status' => 1, 'unit_proses_id' => $sirs->id]);

        $a = User::create(['name' => 'A', 'email' => 'a@x.com', 'password' => Hash::make('123'), 'phone' => '0811', 'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1]);
        $b = User::create(['name' => 'B', 'email' => 'b@x.com', 'password' => Hash::make('123'), 'phone' => '0812', 'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1]);

        $payload = fn (string $brg) => [
            'unit_proses_code' => $up->code, 'jenis_barang' => 'Inventaris',
            'kode_inventaris' => 'INV-'.$brg, 'nama_barang' => $brg, 'lokasi' => $loc->id,
            'keluhan' => 'rusak', 'prioritas' => 'RENDAH', 'tanggal' => now()->format('Y-m-d H:i:s'),
        ];

        // A bikin 1 tiket + 1 order, B bikin 2 tiket + 2 order
        $this->actingAs($a, 'sanctum')->postJson('/api/tickets', [
            'category_id' => $cat->id, 'location_id' => $loc->id,
            'description' => 'Tiket A', 'priority' => 'high',
        ])->assertStatus(201);
        $this->actingAs($a, 'sanctum')->postJson('/api/order-perbaikan', $payload('AC-A'))->assertStatus(201);
        foreach (['Tiket B1', 'Tiket B2'] as $desc) {
            $this->actingAs($b, 'sanctum')->postJson('/api/tickets', [
                'category_id' => $cat->id, 'location_id' => $loc->id,
                'description' => $desc, 'priority' => 'low',
            ])->assertStatus(201);
        }
        $this->actingAs($b, 'sanctum')->postJson('/api/order-perbaikan', $payload('AC-B1'))->assertStatus(201);
        $this->actingAs($b, 'sanctum')->postJson('/api/order-perbaikan', $payload('AC-B2'))->assertStatus(201);

        $resA = $this->actingAs($a, 'sanctum')->getJson('/api/dashboard/user');
        $resA->assertJsonPath('data.orders.total', 1);
        $resA->assertJsonPath('data.tickets.total', 1);

        $resB = $this->actingAs($b, 'sanctum')->getJson('/api/dashboard/user');
        $resB->assertJsonPath('data.orders.total', 2);
        $resB->assertJsonPath('data.tickets.total', 2);
    }
}
