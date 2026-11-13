<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAccessControl;
use Tests\TestCase;

/**
 * Halaman "Tiket Saya" / "Order Saya" di shell admin — hanya menampilkan
 * pengajuan milik user yang login, dengan penentuan jenis flavour lewat
 * kategori (SIRS = jenis IT, UMUM = jenis IPSRS).
 */
class AdminMyticketTest extends TestCase
{
    use RefreshDatabase, SeedsAccessControl;

    protected Department $departemenIt;
    protected KategoriPengajuan $kategoriPrinter;
    protected KategoriPengajuan $kategoriPerbaikan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolePermissions();

        Position::firstOrCreate(['name' => 'User', 'code' => 'user'], ['name' => 'User', 'code' => 'user', 'status' => true]);
        $this->departemenIt = Department::firstOrCreate(
            ['name' => 'IT', 'code' => 'IT'],
            ['name' => 'IT', 'code' => 'IT', 'status' => 1]
        );

        $jenisIt = JenisPengajuan::where('code', 'IT')->firstOrFail();
        $jenisIpsrs = JenisPengajuan::where('code', 'IPSRS')->firstOrFail();

        $this->kategoriPrinter = KategoriPengajuan::create(['name' => 'Printer', 'code' => 'PRN', 'status' => 1]);
        $this->kategoriPrinter->syncJenis([$jenisIt->id]);

        $this->kategoriPerbaikan = KategoriPengajuan::create(['name' => 'Perbaikan', 'code' => 'PRB', 'status' => 1]);
        $this->kategoriPerbaikan->syncJenis([$jenisIpsrs->id]);
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'name' => 'Test '.$role,
            'email' => $email,
            'password' => Hash::make('secret123'),
            'phone' => '0811',
            'role' => $role,
            'position' => null,
            'department' => 'IT',
            'department_id' => $this->departemenIt->id,
            'status' => 1,
        ]);
    }

    private function makePengajuan(
        string $nomor,
        string $status,
        string $deskripsi,
        KategoriPengajuan $kategori,
        User $pemohon,
        ?string $prioritas = 'low',
    ): Pengajuan {
        return Pengajuan::create([
            'nomor' => $nomor,
            'kategori_id' => $kategori->id,
            'department_id' => $this->departemenIt->id,
            'pemohon_id' => $pemohon->id,
            'pemohon_nama' => $pemohon->name,
            'deskripsi' => $deskripsi,
            'prioritas' => $prioritas,
            'status' => $status,
            'tanggal' => now(),
            'created_by' => $pemohon->id,
            'updated_by' => $pemohon->id,
        ]);
    }

    public function test_admin_sees_only_own_tickets_on_myticket(): void
    {
        $admin = $this->makeUser('admin', 'admin@example.com');
        $user = $this->makeUser('user', 'user@example.com');

        $this->makePengajuan('T-0101-001', 'open', 'Tiket milik admin', $this->kategoriPrinter, $admin);
        $this->makePengajuan('T-0101-002', 'open', 'Tiket milik user', $this->kategoriPrinter, $user);

        $resp = $this->actingAs($admin)->get(route('admin.tickets.myticket'));
        $resp->assertStatus(200);
        $resp->assertSee('T-0101-001');
        $resp->assertDontSee('T-0101-002');

        // User biasa tidak boleh masuk shell admin.
        $this->actingAs($user)->get(route('admin.tickets.myticket'))->assertForbidden();
    }

    public function test_myticket_detail_needs_no_manage_permission(): void
    {
        $admin = $this->makeUser('admin', 'admin@example.com');
        $user = $this->makeUser('user', 'user@example.com');

        $own = $this->makePengajuan('T-0101-010', 'open', 'Own', $this->kategoriPrinter, $admin);
        $other = $this->makePengajuan('T-0101-011', 'open', 'Other', $this->kategoriPrinter, $user);

        // Cabut permission ticket: detail milik sendiri tetap 200 via myticket.show.
        $ticketPerm = Permission::where('slug', 'ticket')->firstOrFail();
        DB::table('role_permissions')
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
        $admin = $this->makeUser('admin', 'admin@example.com');
        $user = $this->makeUser('user', 'user@example.com');

        $own = $this->makePengajuan('OP-OWN-001', 'open', 'Own', $this->kategoriPerbaikan, $admin, 'low');
        $other = $this->makePengajuan('OP-OTHER-001', 'open', 'Other', $this->kategoriPerbaikan, $user, 'low');

        // Cabut permission order: detail milik sendiri tetap 200 via myorder.show.
        $orderPerm = Permission::where('slug', 'order')->firstOrFail();
        DB::table('role_permissions')
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
        $admin = $this->makeUser('admin', 'admin@example.com');
        $user = $this->makeUser('user', 'user@example.com');

        $this->makePengajuan('OP-ADMIN-001', 'open', 'Order milik admin', $this->kategoriPerbaikan, $admin, 'low');
        $this->makePengajuan('OP-USER-001', 'open', 'Order milik user', $this->kategoriPerbaikan, $user, 'low');

        $resp = $this->actingAs($admin)->get(route('admin.order-perbaikan.myorder'));
        $resp->assertStatus(200);
        $resp->assertSee('OP-ADMIN-001');
        $resp->assertDontSee('OP-USER-001');

        $this->actingAs($user)->get(route('admin.order-perbaikan.myorder'))->assertForbidden();
    }

    public function test_admin_can_crud_own_ticket_via_myticket(): void
    {
        $admin = $this->makeUser('admin', 'admin@example.com');
        $user = $this->makeUser('user', 'user@example.com');

        // Create
        $this->actingAs($admin)->get(route('admin.tickets.myticket.create'))->assertStatus(200);
        $this->actingAs($admin)->post(route('admin.tickets.myticket.store'), [
            'kategori_id' => $this->kategoriPrinter->id,
            'description' => 'Tiket admin baru',
            'priority' => 'high',
        ])->assertRedirect(route('admin.tickets.myticket'));
        $ticket = Pengajuan::where('deskripsi', 'Tiket admin baru')->firstOrFail();
        $this->assertEquals($admin->id, $ticket->pemohon_id);

        // Edit own open ticket
        $this->actingAs($admin)->get(route('admin.tickets.myticket.edit', $ticket))->assertStatus(200);
        $this->actingAs($admin)->put(route('admin.tickets.myticket.update', $ticket), [
            'kategori_id' => $this->kategoriPrinter->id,
            'description' => 'Tiket admin diubah',
            'priority' => 'low',
        ])->assertRedirect(route('admin.tickets.myticket'));
        $this->assertEquals('Tiket admin diubah', $ticket->fresh()->deskripsi);

        // Cannot edit other's ticket
        $other = $this->makePengajuan('T-0101-099', 'open', 'Other', $this->kategoriPrinter, $user);
        $this->actingAs($admin)->get(route('admin.tickets.myticket.edit', $other))->assertForbidden();

        // Delete own open ticket
        $this->actingAs($admin)->delete(route('admin.tickets.myticket.destroy', $ticket))
            ->assertRedirect(route('admin.tickets.myticket'));
        $this->assertSoftDeleted('pengajuan', ['id' => $ticket->id]);
    }

    public function test_admin_can_crud_own_order_via_myticket(): void
    {
        $ipsrs = $this->makeUser('ipsrs', 'ipsrs@example.com');
        $user = $this->makeUser('user', 'user@example.com');
        $admin = $this->makeUser('admin', 'admin@example.com');

        // Admin IT memegang permission order (seed silang) → bisa buat order sendiri
        $this->actingAs($admin)->get(route('admin.order-perbaikan.myorder.create'))->assertStatus(200);
        $this->actingAs($admin)->post(route('admin.order-perbaikan.myorder.store'), [
            'kategori_id' => $this->kategoriPerbaikan->id,
            'keluhan' => 'AC admin rusak',
            'prioritas' => 'low',
            'tanggal' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('admin.order-perbaikan.myorder'));
        $adminOrder = Pengajuan::where('deskripsi', 'AC admin rusak')->firstOrFail();
        $this->assertEquals($admin->id, $adminOrder->pemohon_id);

        $this->actingAs($ipsrs)->get(route('admin.order-perbaikan.myorder.create'))->assertStatus(200);
        $this->actingAs($ipsrs)->post(route('admin.order-perbaikan.myorder.store'), [
            'kategori_id' => $this->kategoriPerbaikan->id,
            'keluhan' => 'AC rusak',
            'prioritas' => 'low',
            'tanggal' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('admin.order-perbaikan.myorder'));
        $order = Pengajuan::where('deskripsi', 'AC rusak')->firstOrFail();
        $this->assertEquals($ipsrs->id, $order->pemohon_id);

        // Edit own open order
        $this->actingAs($ipsrs)->get(route('admin.order-perbaikan.myorder.edit', $order))->assertStatus(200);
        $this->actingAs($ipsrs)->put(route('admin.order-perbaikan.myorder.update', $order), [
            'kategori_id' => $this->kategoriPerbaikan->id,
            'keluhan' => 'AC rusak parah',
            'prioritas' => 'low',
        ])->assertRedirect(route('admin.order-perbaikan.myorder.show', $order));
        $this->assertEquals('AC rusak parah', $order->fresh()->deskripsi);

        // Cannot edit other's order
        $other = $this->makePengajuan('OP-USER-099', 'open', 'Other', $this->kategoriPerbaikan, $user, 'low');
        $this->actingAs($ipsrs)->get(route('admin.order-perbaikan.myorder.edit', $other))->assertForbidden();

        // Delete own open order
        $this->actingAs($ipsrs)->delete(route('admin.order-perbaikan.myorder.destroy', $order))
            ->assertRedirect(route('admin.order-perbaikan.myorder'));
        $this->assertSoftDeleted('pengajuan', ['id' => $order->id]);
    }
}
