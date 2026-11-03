<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\OrderPerbaikan;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\UnitProses;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

class AdminMyticketTest extends TestCase
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

    public function test_admin_sees_only_own_tickets_on_myticket(): void
    {
        $this->seedRolePermissions();
        $admin = $this->makeUser('admin', 'admin@example.com');
        $user = $this->makeUser('user', 'user@example.com');
        $upSirs = UnitProses::create(['name' => 'SIRS', 'code' => 'SIRS', 'status' => 1]);
        $category = Category::create(['name' => 'Hardware', 'status' => 1, 'unit_proses_id' => $upSirs->id]);
        $department = Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);

        Ticket::create([
            'user_id' => $admin->id, 'ticket_number' => 'T-0101-001',
            'category_id' => $category->id, 'category' => $category->name,
            'department_id' => $department->id, 'department' => 'IT',
            'description' => 'Tiket milik admin', 'priority' => 'low', 'status' => 'open',
        ]);
        Ticket::create([
            'user_id' => $user->id, 'ticket_number' => 'T-0101-002',
            'category_id' => $category->id, 'category' => $category->name,
            'department_id' => $department->id, 'department' => 'IT',
            'description' => 'Tiket milik user', 'priority' => 'low', 'status' => 'open',
        ]);

        $resp = $this->actingAs($admin)->get(route('admin.tickets.myticket'));
        $resp->assertStatus(200);
        $resp->assertSee('T-0101-001');
        $resp->assertDontSee('T-0101-002');

        // User biasa tidak boleh masuk shell admin.
        $this->actingAs($user)->get(route('admin.tickets.myticket'))->assertForbidden();
    }

    public function test_myticket_detail_needs_no_manage_permission(): void
    {
        $this->seedRolePermissions();
        $admin = $this->makeUser('admin', 'admin@example.com');
        $user = $this->makeUser('user', 'user@example.com');
        $upSirs = UnitProses::create(['name' => 'SIRS', 'code' => 'SIRS', 'status' => 1]);
        $category = Category::create(['name' => 'Hardware', 'status' => 1, 'unit_proses_id' => $upSirs->id]);
        $department = Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);

        $own = Ticket::create([
            'user_id' => $admin->id, 'ticket_number' => 'T-0101-010',
            'category_id' => $category->id, 'category' => $category->name,
            'department_id' => $department->id, 'department' => 'IT',
            'description' => 'Own', 'priority' => 'low', 'status' => 'open',
        ]);
        $other = Ticket::create([
            'user_id' => $user->id, 'ticket_number' => 'T-0101-011',
            'category_id' => $category->id, 'category' => $category->name,
            'department_id' => $department->id, 'department' => 'IT',
            'description' => 'Other', 'priority' => 'low', 'status' => 'open',
        ]);

        // Cabut permission ticket: detail milik sendiri tetap 200 via myticket.show.
        $ticketPerm = \App\Models\Permission::where('slug', 'ticket')->firstOrFail();
        \Illuminate\Support\Facades\DB::table('role_permissions')
            ->where('role', 'admin')->where('permission_id', $ticketPerm->id)->delete();

        $this->actingAs($admin)->get(route('admin.tickets.myticket.show', $own))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.tickets.myticket.show', $other))->assertForbidden();
        // Tanpa permission ticket, halaman manage tetap tertutup.
        $this->actingAs($admin)->get(route('admin.tickets.show', $own))->assertForbidden();

        // Modal detail via JSON.
        $this->actingAs($admin)->getJson(route('admin.tickets.myticket.show', $own))
            ->assertStatus(200)->assertJsonPath('ticket_number', 'T-0101-010');
        $this->actingAs($admin)->getJson(route('admin.tickets.myticket.show', $other))->assertForbidden();

        // Modal detail via HTML partial (?modal=1) — lengkap seperti detail user.
        $modal = $this->actingAs($admin)->get(route('admin.tickets.myticket.show', $own).'?modal=1');
        $modal->assertStatus(200);
        $modal->assertSee('T-0101-010');
        $modal->assertSee('Riwayat Percakapan');
        $this->actingAs($admin)->get(route('admin.tickets.myticket.show', $other).'?modal=1')->assertForbidden();
    }

    public function test_myorder_detail_needs_no_manage_permission(): void
    {
        $this->seedRolePermissions();
        Location::create(['name' => 'UGD', 'status' => 1]);
        UnitProses::create(['name' => 'Sarana (IPSRS)', 'code' => 'SRNS', 'status' => 1]);
        $admin = $this->makeUser('admin', 'admin@example.com');
        $user = $this->makeUser('user', 'user@example.com');

        $base = [
            'tanggal' => now(), 'unit_proses' => 'SRNS', 'unit_penerima' => 'MTC',
            'jenis_barang' => 'Umum', 'kode_inventaris' => 'INV-1', 'nama_barang' => 'AC',
            'keluhan' => 'Rusak', 'prioritas' => 'RENDAH', 'status' => 'open',
        ];
        $own = OrderPerbaikan::create($base + [
            'nomor' => 'OP-OWN-001', 'nama_peminta' => $admin->name, 'created_by' => $admin->id,
        ]);
        $other = OrderPerbaikan::create($base + [
            'nomor' => 'OP-OTHER-001', 'nama_peminta' => $user->name, 'created_by' => $user->id,
        ]);

        // Cabut permission order: detail milik sendiri tetap 200 via myorder.show.
        $orderPerm = \App\Models\Permission::where('slug', 'order')->firstOrFail();
        \Illuminate\Support\Facades\DB::table('role_permissions')
            ->where('role', 'admin')->where('permission_id', $orderPerm->id)->delete();

        $this->actingAs($admin)->get(route('admin.order-perbaikan.myorder.show', $own))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.order-perbaikan.myorder.show', $other))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.order-perbaikan.show', $own))->assertForbidden();

        // Modal detail via JSON.
        $this->actingAs($admin)->getJson(route('admin.order-perbaikan.myorder.show', $own))
            ->assertStatus(200)->assertJsonPath('nomor', 'OP-OWN-001');
        $this->actingAs($admin)->getJson(route('admin.order-perbaikan.myorder.show', $other))->assertForbidden();

        // Modal detail via HTML partial (?modal=1) — lengkap seperti detail user.
        $modal = $this->actingAs($admin)->get(route('admin.order-perbaikan.myorder.show', $own).'?modal=1');
        $modal->assertStatus(200);
        $modal->assertSee('OP-OWN-001');
        $modal->assertSee('Riwayat Timeline');
        $this->actingAs($admin)->get(route('admin.order-perbaikan.myorder.show', $other).'?modal=1')->assertForbidden();
    }

    public function test_admin_sees_only_own_orders_on_myticket(): void
    {
        $this->seedRolePermissions();
        $building = Building::create(['name' => 'Gedung A', 'code' => 'A', 'status' => 1]);
        $location = Location::create(['name' => 'UGD', 'status' => 1]);
        UnitProses::create(['name' => 'Sarana (IPSRS)', 'code' => 'SRNS', 'status' => 1]);
        $admin = $this->makeUser('admin', 'admin@example.com');
        $user = $this->makeUser('user', 'user@example.com');

        $base = [
            'tanggal' => now(), 'unit_proses' => 'SRNS', 'unit_proses_name' => 'Sarana',
            'unit_penerima' => 'MTC', 'jenis_barang' => 'Umum', 'kode_inventaris' => 'INV-1',
            'nama_barang' => 'AC', 'lokasi' => $location->id, 'keluhan' => 'Rusak',
            'prioritas' => 'RENDAH', 'status' => 'open',
        ];
        OrderPerbaikan::create($base + [
            'nomor' => 'OP-ADMIN-001', 'nama_peminta' => $admin->name, 'created_by' => $admin->id,
        ]);
        OrderPerbaikan::create($base + [
            'nomor' => 'OP-USER-001', 'nama_peminta' => $user->name, 'created_by' => $user->id,
        ]);

        $resp = $this->actingAs($admin)->get(route('admin.order-perbaikan.myorder'));
        $resp->assertStatus(200);
        $resp->assertSee('OP-ADMIN-001');
        $resp->assertDontSee('OP-USER-001');

        $this->actingAs($user)->get(route('admin.order-perbaikan.myorder'))->assertForbidden();
    }

    public function test_admin_can_crud_own_ticket_via_myticket(): void
    {
        $this->seedRolePermissions();
        $admin = $this->makeUser('admin', 'admin@example.com');
        $user = $this->makeUser('user', 'user@example.com');
        $upSirs = UnitProses::create(['name' => 'SIRS', 'code' => 'SIRS', 'status' => 1]);
        $category = Category::create(['name' => 'Hardware', 'status' => 1, 'unit_proses_id' => $upSirs->id]);
        $department = Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);

        // Create
        $this->actingAs($admin)->get(route('admin.tickets.myticket.create'))->assertStatus(200);
        $this->actingAs($admin)->post(route('admin.tickets.myticket.store'), [
            'category_id' => $category->id,
            'department_id' => $department->id,
            'description' => 'Tiket admin baru',
            'priority' => 'high',
        ])->assertRedirect(route('admin.tickets.myticket'));
        $ticket = Ticket::where('description', 'Tiket admin baru')->firstOrFail();
        $this->assertEquals($admin->id, $ticket->user_id);

        // Edit own open ticket
        $this->actingAs($admin)->get(route('admin.tickets.myticket.edit', $ticket))->assertStatus(200);
        $this->actingAs($admin)->put(route('admin.tickets.myticket.update', $ticket), [
            'category_id' => $category->id,
            'department_id' => $department->id,
            'description' => 'Tiket admin diubah',
            'priority' => 'low',
        ])->assertRedirect(route('admin.tickets.myticket.show', $ticket));
        $this->assertEquals('Tiket admin diubah', $ticket->fresh()->description);

        // Cannot edit other's ticket
        $other = Ticket::create([
            'user_id' => $user->id, 'ticket_number' => 'T-0101-099',
            'category_id' => $category->id, 'category' => $category->name,
            'department_id' => $department->id, 'department' => 'IT',
            'description' => 'Other', 'priority' => 'low', 'status' => 'open',
        ]);
        $this->actingAs($admin)->get(route('admin.tickets.myticket.edit', $other))->assertForbidden();

        // Delete own open ticket
        $this->actingAs($admin)->delete(route('admin.tickets.myticket.destroy', $ticket))
            ->assertRedirect(route('admin.tickets.myticket'));
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_admin_can_crud_own_order_via_myticket(): void
    {
        $this->seedRolePermissions();
        Location::create(['name' => 'UGD', 'status' => 1]);
        UnitProses::create(['name' => 'Sarana (IPSRS)', 'code' => 'SRNS', 'status' => 1]);
        $admin = $this->makeUser('admin', 'admin@example.com');
        $user = $this->makeUser('user', 'user@example.com');

        // Admin IT memegang permission order (seed silang) → bisa buat order sendiri
        $this->actingAs($admin)->get(route('admin.order-perbaikan.myorder.create'))->assertStatus(200);
        $this->actingAs($admin)->post(route('admin.order-perbaikan.myorder.store'), [
            'keluhan' => 'AC admin rusak',
            'prioritas' => 'RENDAH',
            'tanggal' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('admin.order-perbaikan.myorder'));
        $adminOrder = OrderPerbaikan::where('keluhan', 'AC admin rusak')->firstOrFail();
        $this->assertEquals($admin->id, $adminOrder->created_by);

        $ipsrs = $this->makeUser('ipsrs', 'ipsrs@example.com');
        $this->actingAs($ipsrs)->get(route('admin.order-perbaikan.myorder.create'))->assertStatus(200);
        $this->actingAs($ipsrs)->post(route('admin.order-perbaikan.myorder.store'), [
            'keluhan' => 'AC rusak',
            'prioritas' => 'RENDAH',
            'tanggal' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('admin.order-perbaikan.myorder'));
        $order = OrderPerbaikan::where('keluhan', 'AC rusak')->firstOrFail();
        $this->assertEquals($ipsrs->id, $order->created_by);

        // Edit own open order
        $this->actingAs($ipsrs)->get(route('admin.order-perbaikan.myorder.edit', $order))->assertStatus(200);
        $this->actingAs($ipsrs)->put(route('admin.order-perbaikan.myorder.update', $order), [
            'keluhan' => 'AC rusak parah',
            'prioritas' => 'RENDAH',
        ])->assertRedirect(route('admin.order-perbaikan.myorder.show', $order));
        $this->assertEquals('AC rusak parah', $order->fresh()->keluhan);

        // Cannot edit other's order
        $other = OrderPerbaikan::create([
            'nomor' => 'OP-USER-099', 'tanggal' => now(),
            'unit_proses' => 'SRNS', 'unit_penerima' => 'MTC', 'nama_peminta' => $user->name,
            'keluhan' => 'Other', 'prioritas' => 'RENDAH', 'status' => 'open',
            'created_by' => $user->id,
        ]);
        $this->actingAs($ipsrs)->get(route('admin.order-perbaikan.myorder.edit', $other))->assertForbidden();

        // Delete own open order
        $this->actingAs($ipsrs)->delete(route('admin.order-perbaikan.myorder.destroy', $order))
            ->assertRedirect(route('admin.order-perbaikan.myorder'));
    }
}
