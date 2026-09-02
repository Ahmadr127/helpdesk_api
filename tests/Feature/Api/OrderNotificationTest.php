<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Location;
use App\Models\Building;
use App\Models\Position;
use App\Models\UnitProses;
use App\Models\User;
use App\Models\OrderPerbaikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $adminUmum;
    protected Location $location;
    protected UnitProses $unitProses;

    protected function setUp(): void
    {
        parent::setUp();
        Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);
        Position::create(['name' => 'User', 'code' => 'user', 'status' => true]);
        Position::create(['name' => 'Administrasi', 'code' => 'Administrasi', 'status' => true]);
        Position::create(['name' => 'IT', 'code' => 'IT', 'status' => true]);
        $this->unitProses = UnitProses::create(['name' => 'IPSRS', 'code' => 'IPSRS', 'status' => 1]);
        UnitProses::create(['name' => 'SIRS', 'code' => 'SIRS', 'status' => 1]);
        $building = Building::create(['name' => 'Gedung A', 'code' => 'A', 'status' => 1]);
        $this->location = Location::create(['name' => 'UGD', 'building_id' => $building->id, 'status' => 1]);

        $this->user = User::create([
            'name' => 'Regular', 'email' => 'user@example.com', 'password' => Hash::make('123'),
            'phone' => '0811', 'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1,
            'fcm_token' => 'user_token_1234567890'
        ]);
        $this->adminUmum = User::create([
            'name' => 'Admin Umum', 'email' => 'adm@example.com', 'password' => Hash::make('123'),
            'phone' => '0812', 'position' => 'Administrasi', 'role' => 'admin', 'department' => 'IT', 'status' => 1,
            'fcm_token' => 'admin_umum_token_1234567890'
        ]);
    }

    private function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken, 'Accept' => 'application/json'];
    }

    public function test_create_order_queues_to_admin_umum(): void
    {
        Queue::fake();

        $resp = $this->withHeaders($this->authHeader($this->user))->postJson('/api/order-perbaikan', [
            'unit_proses_code' => $this->unitProses->code,
            'jenis_barang' => 'Umum',
            'kode_inventaris' => 'INV-001',
            'nama_barang' => 'AC',
            'lokasi' => $this->location->id,
            'keluhan' => 'Rusak',
            'prioritas' => 'RENDAH',
            'tanggal' => now()->format('Y-m-d H:i:s'),
        ]);

        $resp->assertStatus(201);
        Queue::assertPushed(\App\Jobs\Notifications\SendFirebaseNotificationJob::class, function ($job) {
            return $job->payload['data']['type'] === 'order_created';
        });
    }

    public function test_admin_update_status_queues_to_user(): void
    {
        Queue::fake();

        $order = OrderPerbaikan::create([
            'nomor' => 'OP/RTG/MTC-20250101001', 'tanggal' => now(),
            'unit_proses' => 'IPSRS', 'unit_proses_name' => 'IPSRS',
            'unit_penerima' => 'MTC', 'nama_peminta' => $this->user->name,
            'jenis_barang' => 'Umum', 'kode_inventaris' => 'INV-1',
            'nama_barang' => 'AC', 'lokasi' => $this->location->id,
            'keluhan' => 'Rusak', 'prioritas' => 'RENDAH',
            'status' => 'open', 'created_by' => $this->user->id
        ]);

        $resp = $this->withHeaders($this->authHeader($this->adminUmum))->putJson("/api/administrasi-umum/order-perbaikan/{$order->id}/status", [
            'status' => 'in_progress',
            'follow_up' => 'Dikerjakan',
            'nama_penanggung_jawab' => 'Teknisi'
        ]);

        $resp->assertStatus(200);
        Queue::assertPushed(\App\Jobs\Notifications\SendFirebaseNotificationJob::class, function ($job) {
            return $job->payload['data']['type'] === 'order_status_updated';
        });
    }

    public function test_admin_confirm_queues_to_user(): void
    {
        Queue::fake();

        $order = OrderPerbaikan::create([
            'nomor' => 'OP/RTG/MTC-20250101002', 'tanggal' => now(),
            'unit_proses' => 'IPSRS', 'unit_proses_name' => 'IPSRS',
            'unit_penerima' => 'MTC', 'nama_peminta' => $this->user->name,
            'jenis_barang' => 'Umum', 'kode_inventaris' => 'INV-1',
            'nama_barang' => 'AC', 'lokasi' => $this->location->id,
            'keluhan' => 'Rusak', 'prioritas' => 'RENDAH',
            'status' => 'in_progress', 'created_by' => $this->user->id
        ]);

        $resp = $this->withHeaders($this->authHeader($this->adminUmum))->postJson("/api/administrasi-umum/order-perbaikan/{$order->id}/confirm");
        $resp->assertStatus(200);
        Queue::assertPushed(\App\Jobs\Notifications\SendFirebaseNotificationJob::class, function ($job) {
            return $job->payload['data']['type'] === 'order_confirmed';
        });
    }
}
