<?php

namespace Tests\Unit\Jobs;

use App\DTO\Notifications\FirebaseNotificationData;
use App\Jobs\Notifications\SendFirebaseNotificationJob;
use App\Services\Notifications\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class SendFirebaseNotificationJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_job_has_correct_tries_and_timeout_and_backoff(): void
    {
        $job = new SendFirebaseNotificationJob('token1234567890', ['title' => 'Hi', 'body' => 'Hello', 'data' => []]);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(120, $job->timeout);
        $this->assertEquals([10, 30, 60], $job->backoff());
    }

    public function test_job_is_serializable_without_sdk_objects(): void
    {
        $payload = FirebaseNotificationData::make(title: 'Hi', body: 'Hello', data: ['type' => 'test'])->toQueuePayload();
        $job = new SendFirebaseNotificationJob('token1234567890', $payload);

        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        $this->assertEquals('token1234567890', $unserialized->token);
        $this->assertEquals('Hi', $unserialized->payload['title']);
        $this->assertIsString($unserialized->token);
        $this->assertIsArray($unserialized->payload);
    }

    public function test_job_handle_calls_service_send_to_token(): void
    {
        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')->andReturnNull()->byDefault();
        Log::shouldReceive('warning')->andReturnNull()->byDefault();
        Log::shouldReceive('error')->andReturnNull()->byDefault();

        $payload = FirebaseNotificationData::make(title: 'Hi', body: 'Hello')->toQueuePayload();
        $job = new SendFirebaseNotificationJob('valid_token_1234567890', $payload);

        $service = Mockery::mock(FirebaseNotificationService::class);
        $service->shouldReceive('sendToToken')->once()->with('valid_token_1234567890', Mockery::on(function ($dto) {
            return $dto instanceof FirebaseNotificationData && $dto->title === 'Hi';
        }))->andReturn(['success' => true]);

        $job->handle($service);
        $this->assertTrue(true);
    }

    public function test_job_handle_topic_dispatch(): void
    {
        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')->andReturnNull()->byDefault();
        Log::shouldReceive('error')->andReturnNull()->byDefault();
        Log::shouldReceive('warning')->andReturnNull()->byDefault();

        $payload = FirebaseNotificationData::make(title: 'Hi', body: 'Hello')->toQueuePayload();
        $job = new SendFirebaseNotificationJob('topic://news', $payload);

        $service = Mockery::mock(FirebaseNotificationService::class);
        $service->shouldReceive('sendToTopic')->once()->with('news', Mockery::any())->andReturn(['success' => true]);

        $job->handle($service);
        $this->assertTrue(true);
    }

    public function test_job_handle_topic_detection(): void
    {
        $payload = FirebaseNotificationData::make(title: 'Hi', body: 'Hello')->toQueuePayload();
        $job = new SendFirebaseNotificationJob('topic://news', $payload);

        $this->assertTrue(str_starts_with($job->token, 'topic://'));
        $this->assertEquals('news', substr($job->token, strlen('topic://')));
    }

    public function test_job_payload_from_dto_is_correct(): void
    {
        $dto = FirebaseNotificationData::make(title: 'Ticket Baru', body: 'Ada ticket', data: ['ticket_id' => '123']);
        $payload = $dto->toQueuePayload();

        $this->assertEquals('Ticket Baru', $payload['title']);
        $this->assertEquals('Ada ticket', $payload['body']);
        $this->assertEquals('123', $payload['data']['ticket_id']);

        $restored = FirebaseNotificationData::fromArray($payload);
        $this->assertEquals($dto->title, $restored->title);
        $this->assertEquals($dto->data, $restored->data);
    }

    public function test_queue_dispatch_on_notifications_queue(): void
    {
        $payload = FirebaseNotificationData::make(title: 'Hi', body: 'Hello')->toQueuePayload();
        $job = new SendFirebaseNotificationJob('token1234567890', $payload);
        $job->onQueue('notifications');

        $this->assertEquals('notifications', $job->queue);
    }

    public function test_job_failed_logs_error(): void
    {
        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('error')->once()->andReturnNull();

        $payload = FirebaseNotificationData::make(title: 'Hi', body: 'Hello')->toQueuePayload();
        $job = new SendFirebaseNotificationJob('token1234567890', $payload);

        $exception = new \Exception('test failure');
        $job->failed($exception);
        $this->assertTrue(true);
    }
}
