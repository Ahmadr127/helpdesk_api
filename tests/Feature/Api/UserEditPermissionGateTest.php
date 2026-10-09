<?php

namespace Tests\Feature\Api;

use App\Models\Building;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\OrderPerbaikan;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Ticket;
use App\Models\UnitProses;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

/**
 * Regresi: endpoint user tiket/order wajib gate permission
 * (tickets → myticket|ticket, order-perbaikan → myorder|order).
 * Cabut permission → 403 walau owner + status open.
 */
class UserEditPermissionGateTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    protected User $user;

    protected Category $category;

    protected Department $department;

    protected Building $building;

    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolePermissions();
        $this->building = Building::create(['name' => 'Gedung A', 'code' => 'A', 'status' => 1]);
        $this->department = Department::create(['name' => 'IT Department', 'code' => 'IT', 'status' => 1, 'building_id' => $this->building->id]);
        Position::create(['name' => 'UserPos', 'code' => 'user', 'status' => true]);
        $upSirs = UnitProses::create(['name' => 'SIRS', 'code' => 'SIRS', 'status' => 1]);
        $this->category = Category::create(['name' => 'Hardware', 'status' => 1, 'unit_proses_id' => $upSirs->id]);
        $this->location = Location::create(['name' => 'UGD', 'status' => 1]);

        $this->user = User::create([
            'name' => 'Regular User', 'email' => 'user@example.com', 'password' => Hash::make('123'),
            'phone' => '0811', 'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1,
        ]);
    }

    protected function authHeader(): array
    {
        return ['Authorization' => 'Bearer '.$this->user->createToken('test')->plainTextToken];
    }

    /** Cabut slug permission dari role user + direct user. */
    protected function revoke(array $slugs): void
    {
        $ids = Permission::whereIn('slug', $slugs)->pluck('id');
        DB::table('role_permissions')->where('role', 'user')->whereIn('permission_id', $ids)->delete();
        $this->user->permissions()->detach();
    }

    protected function makeTicket(string $status = 'open'): Ticket
    {
        return Ticket::create([
            'user_id' => $this->user->id, 'ticket_number' => 'T-0101-0'.random_int(10, 99),
            'category_id' => $this->category->id, 'category' => $this->category->name,
            'department_id' => $this->department->id, 'department' => 'IT',
            'building_id' => $this->building->id, 'building' => 'Gedung A',
            'location_id' => $this->location->id, 'location' => 'UGD',
            'description' => 'Old', 'priority' => 'low', 'status' => $status,
        ]);
    }

    protected function makeOrder(string $status = 'open'): OrderPerbaikan
    {
        return OrderPerbaikan::create([
            'nomor' => 'OP/RTG/MTC-20250101'.random_int(100, 999), 'tanggal' => now(),
            'unit_proses' => 'SRNS', 'unit_proses_name' => 'Sarana', 'unit_penerima' => 'MTC',
            'nama_peminta' => $this->user->name, 'kode_inventaris' => 'INV-1',
            'nama_barang' => 'Old', 'lokasi' => $this->location->id, 'keluhan' => 'Old',
            'prioritas' => 'RENDAH', 'status' => $status, 'created_by' => $this->user->id,
        ]);
    }

    protected function ticketPayload(): array
    {
        return [
            'description' => 'Updated', 'category_id' => $this->category->id,
            'department_id' => $this->department->id, 'location_id' => $this->location->id,
            'priority' => 'high',
        ];
    }

    protected function orderPayload(): array
    {
        return [
            'kode_inventaris' => 'INV-1', 'nama_barang' => 'Updated',
            'lokasi' => $this->location->id, 'keluhan' => 'Updated', 'prioritas' => 'RENDAH',
        ];
    }

    public function test_ticket_update_ditolak_tanpa_permission()
    {
        $ticket = $this->makeTicket();
        $this->revoke(['myticket', 'ticket']);

        $resp = $this->withHeaders($this->authHeader())->putJson("/api/tickets/{$ticket->id}", $this->ticketPayload());

        $resp->assertStatus(403)->assertJsonPath('success', false);
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id, 'description' => 'Updated']);
    }

    public function test_ticket_update_lolos_dengan_myticket_saja()
    {
        $ticket = $this->makeTicket();
        $this->revoke(['myticket', 'ticket']);
        $this->grantPermission('myticket', 'user');

        $resp = $this->withHeaders($this->authHeader())->putJson("/api/tickets/{$ticket->id}", $this->ticketPayload());

        $resp->assertStatus(200)->assertJsonPath('data.description', 'Updated');
    }

    public function test_ticket_show_dan_delete_ditolak_tanpa_permission()
    {
        $ticket = $this->makeTicket();
        $this->revoke(['myticket', 'ticket']);

        $this->withHeaders($this->authHeader())->getJson("/api/tickets/{$ticket->id}")->assertStatus(403);
        $this->withHeaders($this->authHeader())->deleteJson("/api/tickets/{$ticket->id}")->assertStatus(403);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
    }

    public function test_order_update_ditolak_tanpa_permission()
    {
        $order = $this->makeOrder();
        $this->revoke(['myorder', 'order']);

        $resp = $this->withHeaders($this->authHeader())->putJson("/api/order-perbaikan/{$order->id}", $this->orderPayload());

        $resp->assertStatus(403)->assertJsonPath('success', false);
        $this->assertDatabaseMissing('order_perbaikan', ['id' => $order->id, 'nama_barang' => 'Updated']);
    }

    public function test_order_update_lolos_dengan_myorder_saja()
    {
        $order = $this->makeOrder();
        $this->revoke(['myorder', 'order']);
        $this->grantPermission('myorder', 'user');

        $resp = $this->withHeaders($this->authHeader())->putJson("/api/order-perbaikan/{$order->id}", $this->orderPayload());

        $resp->assertStatus(200)->assertJsonPath('data.nama_barang', 'Updated');
    }

    public function test_order_show_dan_delete_ditolak_tanpa_permission()
    {
        $order = $this->makeOrder();
        $this->revoke(['myorder', 'order']);

        $this->withHeaders($this->authHeader())->getJson("/api/order-perbaikan/{$order->id}")->assertStatus(403);
        $this->withHeaders($this->authHeader())->deleteJson("/api/order-perbaikan/{$order->id}")->assertStatus(403);
        $this->assertDatabaseHas('order_perbaikan', ['id' => $order->id]);
    }
}
