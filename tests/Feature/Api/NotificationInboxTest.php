<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Department::create(['name' => 'IT', 'code' => 'IT', 'status' => 1]);
        Position::create(['name' => 'User', 'code' => 'user', 'status' => true]);
        $this->user = User::create([
            'name' => 'Test', 'email' => 'test@example.com',
            'password' => Hash::make('123'), 'phone' => '0811',
            'position' => 'user', 'role' => 'user', 'department' => 'IT', 'status' => 1
        ]);
    }

    private function authHeader(): array
    {
        return ['Authorization' => 'Bearer '.$this->user->createToken('test')->plainTextToken, 'Accept' => 'application/json'];
    }

    private function createNotification(): string
    {
        $id = (string) Str::uuid();
        $this->user->notifications()->create([
            'id' => $id,
            'type' => 'App\Notifications\TicketRespondedNotification',
            'data' => ['title' => 'Test', 'message' => 'Test message', 'ticket_id' => '1'],
        ]);
        return $id;
    }

    public function test_inbox_returns_notifications(): void
    {
        $this->createNotification();

        $resp = $this->withHeaders($this->authHeader())->getJson('/api/notifications');
        $resp->assertStatus(200)->assertJsonPath('success', true);
        $this->assertGreaterThanOrEqual(1, count($resp->json('data')));
    }

    public function test_unread_count(): void
    {
        $resp = $this->withHeaders($this->authHeader())->getJson('/api/notifications/unread-count');
        $resp->assertStatus(200)->assertJsonPath('success', true);
        $this->assertArrayHasKey('unread_count', $resp->json());
    }

    public function test_mark_read(): void
    {
        $id = $this->createNotification();
        $resp = $this->withHeaders($this->authHeader())->postJson("/api/notifications/{$id}/read");
        $resp->assertStatus(200);
        $this->assertNotNull($this->user->notifications()->where('id', $id)->first()->read_at);
    }

    public function test_mark_all_read(): void
    {
        $this->createNotification();
        $resp = $this->withHeaders($this->authHeader())->postJson('/api/notifications/read-all');
        $resp->assertStatus(200);
        $this->assertEquals(0, $this->user->fresh()->unreadNotifications()->count());
    }

    public function test_destroy(): void
    {
        $id = $this->createNotification();
        $resp = $this->withHeaders($this->authHeader())->deleteJson("/api/notifications/{$id}");
        $resp->assertStatus(200);
        $this->assertDatabaseMissing('notifications', ['id' => $id]);
    }
}
