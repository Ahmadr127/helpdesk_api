<?php

namespace Tests\Feature\Api;

use App\Contracts\Notifications\FirebaseNotificationInterface;
use App\DTO\Notifications\FirebaseNotificationData;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Mockery;

class FirebaseNotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Department::create(['name' => 'IT Department', 'code' => 'IT', 'status' => 1]);
        Position::create(['name' => 'IT', 'code' => 'IT', 'status' => true]);
        Position::create(['name' => 'User', 'code' => 'user', 'status' => true]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('123'),
            'phone' => '08123456789',
            'position' => 'user',
            'role' => 'user',
            'department' => 'IT',
            'status' => 1,
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Mock Firebase binding to avoid real SDK calls
        $this->mockFirebase();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockFirebase(): void
    {
        $mock = Mockery::mock(FirebaseNotificationInterface::class);
        $mock->shouldReceive('sendToToken')->andReturn(['success' => true, 'message_id' => 'test_msg_id'])->byDefault();
        $mock->shouldReceive('sendToTokens')->andReturn(['success_count' => 2, 'failure_count' => 0, 'results' => [], 'invalid_tokens' => []])->byDefault();
        $mock->shouldReceive('sendToTopic')->andReturn(['success' => true, 'message_id' => 'topic_msg_id'])->byDefault();
        $mock->shouldReceive('subscribeToTopic')->andReturn(['success' => true])->byDefault();
        $mock->shouldReceive('unsubscribeFromTopic')->andReturn(['success' => true])->byDefault();
        // For queue tests, we will override with Queue::fake expectations
        $mock->shouldReceive('queue')->andReturnNull()->byDefault();
        $mock->shouldReceive('queueToTokens')->andReturnNull()->byDefault();
        $mock->shouldReceive('queueToTopic')->andReturnNull()->byDefault();

        $this->app->instance(FirebaseNotificationInterface::class, $mock);
    }

    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ];
    }

    public function test_send_notification_success(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/firebase/notification/send', [
            'token' => 'valid_fcm_token_1234567890',
            'notification' => ['title' => 'Test Notification', 'body' => 'Hello from Laravel'],
            'data' => ['type' => 'test', 'screen' => 'home'],
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true)->assertJsonPath('data.success', true);
    }

    public function test_send_notification_queued(): void
    {
        // Override mock to ensure queue is called and test via fake
        Queue::fake();
        // Need to rebind mock that actually dispatches? Instead we test endpoint returns queued true
        // The repository queue method dispatches job, but our mock just returns null. So we test that controller returns queued true when queue=true
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/firebase/notification/send', [
            'token' => 'valid_fcm_token_1234567890',
            'notification' => ['title' => 'Test Queue', 'body' => 'Notification via queue'],
            'data' => ['type' => 'test'],
            'queue' => true,
        ]);

        $response->assertStatus(200)->assertJsonPath('queued', true)->assertJsonPath('success', true);
    }

    public function test_send_notification_validation_fails_without_token(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/firebase/notification/send', [
            'notification' => ['title' => 'Hi', 'body' => 'Hello'],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['token']);
    }

    public function test_send_notification_validation_fails_without_title(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/firebase/notification/send', [
            'token' => 'valid_fcm_token_1234567890',
            'notification' => ['body' => 'Hello'],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['notification.title']);
    }

    public function test_send_notification_validation_fails_without_body(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/firebase/notification/send', [
            'token' => 'valid_fcm_token_1234567890',
            'notification' => ['title' => 'Hi'],
        ]);

        $response->assertStatus(422);
    }

    public function test_send_many_success(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/firebase/notification/send-many', [
            'tokens' => ['valid_token_1234567890_a', 'valid_token_1234567890_b'],
            'notification' => ['title' => 'Multi', 'body' => 'Hello multiple'],
            'data' => ['type' => 'broadcast'],
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_send_many_validation_fails_on_empty_tokens(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/firebase/notification/send-many', [
            'tokens' => [],
            'notification' => ['title' => 'Hi', 'body' => 'Hello'],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['tokens']);
    }

    public function test_send_many_queued(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/firebase/notification/send-many', [
            'tokens' => ['valid_token_1234567890_a', 'valid_token_1234567890_b'],
            'notification' => ['title' => 'Multi Queue', 'body' => 'Queued'],
            'queue' => true,
        ]);

        $response->assertStatus(200)->assertJsonPath('queued', true);
    }

    public function test_send_topic_success(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/firebase/notification/topic', [
            'topic' => 'news',
            'notification' => ['title' => 'Topic', 'body' => 'Hello topic'],
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_send_topic_validation_fails_without_topic(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/firebase/notification/topic', [
            'notification' => ['title' => 'Hi', 'body' => 'Hello'],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['topic']);
    }

    public function test_send_topic_queued(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/firebase/notification/topic', [
            'topic' => 'news',
            'notification' => ['title' => 'Topic Queue', 'body' => 'Queued topic'],
            'queue' => true,
        ]);

        $response->assertStatus(200)->assertJsonPath('queued', true);
    }

    public function test_subscribe_topic_success(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/firebase/topic/subscribe', [
            'topic' => 'news',
            'tokens' => ['valid_token_1234567890_a'],
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_subscribe_topic_validation_fails_without_topic(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/firebase/topic/subscribe', [
            'tokens' => ['valid_token_1234567890_a'],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['topic']);
    }

    public function test_unsubscribe_topic_success(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/firebase/topic/unsubscribe', [
            'topic' => 'news',
            'tokens' => ['valid_token_1234567890_a'],
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_unauthenticated_fails(): void
    {
        $response = $this->postJson('/api/firebase/notification/send', [
            'token' => 'valid_fcm_token_1234567890',
            'notification' => ['title' => 'Hi', 'body' => 'Hello'],
        ]);

        $response->assertStatus(401);
    }

    public function test_queue_dispatch_actually_queues_job(): void
    {
        // Test real queue dispatch without mocking queue method - verify Job class can be dispatched
        // Use Queue::fake to capture
        Queue::fake();

        // Manually dispatch job to ensure queue works
        $dto = FirebaseNotificationData::make(title: 'Hi', body: 'Hello');
        \App\Jobs\Notifications\SendFirebaseNotificationJob::dispatch('valid_token_1234567890', $dto->toQueuePayload())->onQueue('notifications');

        Queue::assertPushed(\App\Jobs\Notifications\SendFirebaseNotificationJob::class, function ($job) {
            return $job->queue === 'notifications';
        });
    }
}
