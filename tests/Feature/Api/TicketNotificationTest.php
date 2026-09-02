<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Location;
use App\Models\Building;
use App\Models\Position;
use App\Models\UnitProses;
use App\Models\Category;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TicketNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Location $location;
    protected Category $category;
    protected Department $department;
    protected Building $building;

    protected function setUp(): void
    {
        parent::setUp();
        $this->department = Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);
        Position::create(['name' => 'User', 'code' => 'user', 'status' => true]);
        Position::create(['name' => 'IT', 'code' => 'IT', 'status' => true]);
        Position::create(['name' => 'Administrasi', 'code' => 'Administrasi', 'status' => true]);
        $up = UnitProses::create(['name' => 'SIRS', 'code' => 'SIRS', 'status' => 1]);
        UnitProses::create(['name' => 'IPSRS', 'code' => 'IPSRS', 'status' => 1]);
        $this->building = Building::create(['name' => 'Gedung A', 'code' => 'A', 'status' => 1]);
        $this->location = Location::create(['name' => 'UGD', 'building_id' => $this->building->id, 'status' => 1]);
        $this->category = Category::create(['name' => 'Jaringan', 'unit_proses_id' => $up->id, 'status' => 1]);

        $this->user = User::create([
            'name' => 'Regular', 'email' => 'user@example.com', 'password' => Hash::make('123'),
            'phone' => '0811', 'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1,
            'fcm_token' => 'user_token_1234567890'
        ]);
        $this->admin = User::create([
            'name' => 'Admin IT', 'email' => 'admin@example.com', 'password' => Hash::make('123'),
            'phone' => '0812', 'position' => 'IT', 'role' => 'admin', 'department' => 'IT', 'status' => 1,
            'fcm_token' => 'admin_token_1234567890_abcdef'
        ]);
    }

    private function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken, 'Accept' => 'application/json'];
    }

    public function test_create_ticket_queues_notification_to_admin(): void
    {
        Queue::fake();

        $resp = $this->withHeaders($this->authHeader($this->user))->postJson('/api/tickets', [
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'description' => 'Komputer tidak menyala',
            'priority' => 'high'
        ]);

        $resp->assertStatus(201);
        Queue::assertPushed(\App\Jobs\Notifications\SendFirebaseNotificationJob::class, function ($job) {
            return $job->payload['data']['type'] === 'ticket_created';
        });
    }

    public function test_admin_respond_queues_notification_to_user(): void
    {
        Queue::fake();

        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'ticket_number' => 'T-0101-001',
            'description' => 'Test',
            'category_id' => $this->category->id, 'category' => $this->category->name,
            'department_id' => $this->department->id, 'department' => $this->department->name,
            'building_id' => $this->building->id, 'building' => $this->building->name,
            'location_id' => $this->location->id, 'location' => $this->location->name,
            'priority' => 'high', 'status' => 'open'
        ]);

        $resp = $this->withHeaders($this->authHeader($this->admin))->postJson("/api/admin/tickets/{$ticket->id}/respond", [
            'notes' => 'Kami proses',
            'status' => 'in_progress'
        ]);

        $resp->assertStatus(200);
        Queue::assertPushed(\App\Jobs\Notifications\SendFirebaseNotificationJob::class, function ($job) {
            return $job->payload['data']['type'] === 'ticket_responded';
        });
    }

    public function test_user_reply_queues_to_admin(): void
    {
        Queue::fake();

        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'ticket_number' => 'T-0101-002',
            'description' => 'Test',
            'category_id' => $this->category->id, 'category' => $this->category->name,
            'department_id' => $this->department->id, 'department' => $this->department->name,
            'building_id' => $this->building->id, 'building' => $this->building->name,
            'location_id' => $this->location->id, 'location' => $this->location->name,
            'priority' => 'high', 'status' => 'open'
        ]);

        $resp = $this->withHeaders($this->authHeader($this->user))->postJson("/api/tickets/{$ticket->id}/reply", [
            'message' => 'Mohon segera'
        ]);

        $resp->assertStatus(200);
        Queue::assertPushed(\App\Jobs\Notifications\SendFirebaseNotificationJob::class, function ($job) {
            return $job->payload['data']['type'] === 'ticket_replied';
        });
    }
}
