<?php

namespace Tests\Unit\Support;

use App\Models\Building;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\Position;
use App\Models\UnitProses;
use App\Models\User;
use App\Models\Ticket;
use App\Models\OrderPerbaikan;
use App\Support\Notifications\Notify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotifyTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;
    protected Building $building;
    protected Location $location;
    protected Category $category;
    protected UnitProses $unitProses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->department = Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);
        Position::create(['name' => 'IT', 'code' => 'IT', 'status' => true]);
        Position::create(['name' => 'Administrasi', 'code' => 'Administrasi', 'status' => true]);
        Position::create(['name' => 'User', 'code' => 'user', 'status' => true]);
        $this->unitProses = UnitProses::create(['name' => 'SIRS', 'code' => 'SIRS', 'status' => 1]);
        UnitProses::create(['name' => 'IPSRS', 'code' => 'IPSRS', 'status' => 1]);
        $this->building = Building::create(['name' => 'Gedung A', 'code' => 'A', 'status' => 1]);
        $this->location = Location::create(['name' => 'Ruang 1', 'building_id' => $this->building->id, 'status' => 1]);
        $this->category = Category::create(['name' => 'Jaringan', 'unit_proses_id' => $this->unitProses->id, 'status' => 1]);
    }

    private function makeTicket(User $owner): Ticket
    {
        return Ticket::create([
            'user_id' => $owner->id,
            'ticket_number' => 'T-0101-'.rand(100,999),
            'description' => 'Test',
            'category_id' => $this->category->id, 'category' => $this->category->name,
            'department_id' => $this->department->id, 'department' => $this->department->name,
            'building_id' => $this->building->id, 'building' => $this->building->name,
            'location_id' => $this->location->id, 'location' => $this->location->name,
            'priority' => 'high',
            'status' => 'open'
        ]);
    }

    public function test_ticket_to_admins_queues_when_admin_has_token(): void
    {
        Queue::fake();

        $admin = User::create([
            'name' => 'Admin IT', 'email' => 'admin@test.com',
            'password' => bcrypt('123'), 'phone' => '0811',
            'position' => 'IT', 'role' => 'admin', 'department' => 'IT', 'status' => 1,
            'fcm_token' => 'valid_admin_token_1234567890'
        ]);
        $actor = User::create([
            'name' => 'User', 'email' => 'user@test.com',
            'password' => bcrypt('123'), 'phone' => '0812',
            'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1
        ]);

        $ticket = $this->makeTicket($actor);

        Notify::ticketToAdmins($ticket, 'ticket_created', $actor);

        Queue::assertPushed(\App\Jobs\Notifications\SendFirebaseNotificationJob::class);
    }

    public function test_ticket_to_admins_no_queue_when_no_tokens(): void
    {
        Queue::fake();

        User::create([
            'name' => 'Admin IT', 'email' => 'admin@test.com',
            'password' => bcrypt('123'), 'phone' => '0811',
            'position' => 'IT', 'role' => 'admin', 'department' => 'IT', 'status' => 1
        ]);
        $actor = User::create([
            'name' => 'User', 'email' => 'user@test.com',
            'password' => bcrypt('123'), 'phone' => '0812',
            'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1
        ]);
        $ticket = $this->makeTicket($actor);

        Notify::ticketToAdmins($ticket, 'ticket_created', $actor);

        Queue::assertNotPushed(\App\Jobs\Notifications\SendFirebaseNotificationJob::class);
    }

    public function test_ticket_to_user_queues(): void
    {
        Queue::fake();

        $user = User::create([
            'name' => 'User', 'email' => 'user@test.com',
            'password' => bcrypt('123'), 'phone' => '0811',
            'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1,
            'fcm_token' => 'user_token_1234567890'
        ]);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com',
            'password' => bcrypt('123'), 'phone' => '0812',
            'position' => 'IT', 'role' => 'admin', 'department' => 'IT', 'status' => 1
        ]);
        $ticket = $this->makeTicket($user);

        Notify::ticketToUser($ticket, 'ticket_responded', $admin, 'Catatan admin');

        Queue::assertPushed(\App\Jobs\Notifications\SendFirebaseNotificationJob::class, function ($job) {
            return str_contains($job->payload['title'], 'Admin Membalas');
        });
    }

    public function test_order_to_admins_and_to_user(): void
    {
        Queue::fake();

        $adminUmum = User::create([
            'name' => 'Admin Umum', 'email' => 'adm@test.com',
            'password' => bcrypt('123'), 'phone' => '0811',
            'position' => 'Administrasi', 'role' => 'admin', 'department' => 'IT', 'status' => 1,
            'fcm_token' => 'admin_umum_token_1234567890'
        ]);
        $user = User::create([
            'name' => 'User', 'email' => 'user@test.com',
            'password' => bcrypt('123'), 'phone' => '0812',
            'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1,
            'fcm_token' => 'user_token_1234567890'
        ]);

        $order = OrderPerbaikan::create([
            'nomor' => 'OP/RTG/MTC-20250101001', 'tanggal' => now(),
            'unit_proses' => 'IPSRS', 'unit_proses_name' => 'Sarana',
            'unit_penerima' => 'MTC', 'nama_peminta' => $user->name,
            'jenis_barang' => 'Umum', 'kode_inventaris' => 'INV-1',
            'nama_barang' => 'AC', 'lokasi' => $this->location->id, 'keluhan' => 'Rusak',
            'prioritas' => 'RENDAH', 'status' => 'open', 'created_by' => $user->id
        ]);

        Notify::orderToAdmins($order, $user);
        Queue::assertPushed(\App\Jobs\Notifications\SendFirebaseNotificationJob::class, function ($job) {
            return str_contains($job->payload['title'], 'Order Baru');
        });

        Queue::fake();
        Notify::orderToUser($order, 'order_confirmed', $adminUmum);
        Queue::assertPushed(\App\Jobs\Notifications\SendFirebaseNotificationJob::class, function ($job) {
            return $job->payload['data']['type'] === 'order_confirmed';
        });
    }
}
