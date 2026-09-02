<?php

namespace Tests\Unit\Services;

use App\Contracts\Notifications\FirebaseNotificationInterface;
use App\DTO\Notifications\FirebaseNotificationData;
use App\Services\Notifications\FirebaseNotificationService;
use Mockery;
use PHPUnit\Framework\TestCase;

class FirebaseNotificationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_send_to_token_delegates_to_repository(): void
    {
        $repo = Mockery::mock(FirebaseNotificationInterface::class);
        $dto = FirebaseNotificationData::make(title: 'Hi', body: 'Hello');
        $repo->shouldReceive('sendToToken')->once()->with('token1234567890', $dto)->andReturn(['success' => true, 'message_id' => 'msg_123']);

        $service = new FirebaseNotificationService($repo);
        $result = $service->sendToToken('token1234567890', $dto);

        $this->assertTrue($result['success']);
        $this->assertEquals('msg_123', $result['message_id']);
    }

    public function test_send_to_tokens_delegates(): void
    {
        $repo = Mockery::mock(FirebaseNotificationInterface::class);
        $dto = FirebaseNotificationData::make(title: 'Hi', body: 'Hello');
        $repo->shouldReceive('sendToTokens')->once()->andReturn(['success_count' => 2, 'failure_count' => 0, 'results' => [], 'invalid_tokens' => []]);

        $service = new FirebaseNotificationService($repo);
        $result = $service->sendToTokens(['t1_long_enough_token', 't2_long_enough_token'], $dto);

        $this->assertEquals(2, $result['success_count']);
    }

    public function test_send_to_topic_delegates(): void
    {
        $repo = Mockery::mock(FirebaseNotificationInterface::class);
        $dto = FirebaseNotificationData::make(title: 'Hi', body: 'Hello');
        $repo->shouldReceive('sendToTopic')->once()->with('news', $dto)->andReturn(['success' => true, 'message_id' => 'msg_topic']);

        $service = new FirebaseNotificationService($repo);
        $result = $service->sendToTopic('news', $dto);

        $this->assertTrue($result['success']);
    }

    public function test_subscribe_and_unsubscribe_delegate(): void
    {
        $repo = Mockery::mock(FirebaseNotificationInterface::class);
        $repo->shouldReceive('subscribeToTopic')->once()->with('news', ['token1234567890'])->andReturn(['success' => true]);
        $repo->shouldReceive('unsubscribeFromTopic')->once()->with('news', ['token1234567890'])->andReturn(['success' => true]);

        $service = new FirebaseNotificationService($repo);
        $this->assertTrue($service->subscribeToTopic('news', ['token1234567890'])['success']);
        $this->assertTrue($service->unsubscribeFromTopic('news', ['token1234567890'])['success']);
    }

    public function test_queue_delegates(): void
    {
        $repo = Mockery::mock(FirebaseNotificationInterface::class);
        $dto = FirebaseNotificationData::make(title: 'Hi', body: 'Hello');
        $repo->shouldReceive('queue')->once()->with('token1234567890', $dto, null);
        $repo->shouldReceive('queueToTokens')->once();
        $repo->shouldReceive('queueToTopic')->once();

        $service = new FirebaseNotificationService($repo);
        $service->queue('token1234567890', $dto);
        $service->queueToTokens(['token1234567890'], $dto);
        $service->queueToTopic('news', $dto);
        $this->assertTrue(true);
    }
}
