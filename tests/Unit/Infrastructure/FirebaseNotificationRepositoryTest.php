<?php

namespace Tests\Unit\Infrastructure;

use App\DTO\Notifications\FirebaseNotificationData;
use App\Exceptions\FirebaseNotificationException;
use App\Infrastructure\Firebase\FirebaseClient;
use App\Infrastructure\Firebase\FirebaseNotificationRepository;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Fake messaging to avoid mocking final class Kreait\Firebase\Messaging.
 */
class FakeMessaging
{
    public $sendReturn = 'projects/notification-azra/messages/msg123';
    public $sendException = null;
    public $sendAllReturn = [];
    public $sendAllException = null;
    public $subscribeReturn = ['successCount' => 1];
    public $subscribeException = null;
    public $unsubscribeReturn = ['successCount' => 1];
    public $unsubscribeException = null;
    public $lastSendMessage = null;
    public $lastSendAllMessages = null;

    public function send($message)
    {
        $this->lastSendMessage = $message;
        if ($this->sendException) {
            throw $this->sendException;
        }
        return $this->sendReturn;
    }

    public function sendAll($messages)
    {
        $this->lastSendAllMessages = $messages;
        if ($this->sendAllException) {
            throw $this->sendAllException;
        }
        return $this->sendAllReturn;
    }

    public function subscribeToTopic($topic, $tokens)
    {
        if ($this->subscribeException) {
            throw $this->subscribeException;
        }
        return $this->subscribeReturn;
    }

    public function unsubscribeFromTopic($topic, $tokens)
    {
        if ($this->unsubscribeException) {
            throw $this->unsubscribeException;
        }
        return $this->unsubscribeReturn;
    }
}

class FakeReport
{
    public function __construct(private bool $success, private $result = null, private $error = null) {}
    public function isSuccess(): bool { return $this->success; }
    public function result() { return $this->result; }
    public function error() { return $this->error; }
}

class FirebaseNotificationRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')->andReturnNull()->byDefault();
        Log::shouldReceive('warning')->andReturnNull()->byDefault();
        Log::shouldReceive('error')->andReturnNull()->byDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeRepositoryWithFakeMessaging(FakeMessaging $messaging): FirebaseNotificationRepository
    {
        $client = Mockery::mock(FirebaseClient::class);
        $client->shouldReceive('messaging')->andReturn($messaging);
        return new FirebaseNotificationRepository($client);
    }

    public function test_send_to_token_success(): void
    {
        $messaging = new FakeMessaging();
        $messaging->sendReturn = 'projects/notification-azra/messages/msg123';

        $repo = $this->makeRepositoryWithFakeMessaging($messaging);
        $dto = FirebaseNotificationData::make(title: 'Hi', body: 'Hello', data: ['type' => 'test']);

        $result = $repo->sendToToken('valid_token_1234567890', $dto);

        $this->assertTrue($result['success']);
        $this->assertEquals('projects/notification-azra/messages/msg123', $result['message_id']);
    }

    public function test_send_to_token_invalid_token_throws_exception(): void
    {
        $messaging = new FakeMessaging();
        $messaging->sendException = new \Exception('Requested entity was not found.');

        $repo = $this->makeRepositoryWithFakeMessaging($messaging);
        $dto = FirebaseNotificationData::make(title: 'Hi', body: 'Hello');

        $this->expectException(FirebaseNotificationException::class);
        $repo->sendToToken('invalid_token_1234567890', $dto);
    }

    public function test_send_to_token_empty_token_validation(): void
    {
        $messaging = new FakeMessaging();
        $repo = $this->makeRepositoryWithFakeMessaging($messaging);
        $dto = FirebaseNotificationData::make(title: 'Hi', body: 'Hello');

        $this->expectException(FirebaseNotificationException::class);
        $repo->sendToToken('', $dto);
    }

    public function test_send_to_topic_success(): void
    {
        $messaging = new FakeMessaging();
        $messaging->sendReturn = 'msg_topic_123';

        $repo = $this->makeRepositoryWithFakeMessaging($messaging);
        $dto = FirebaseNotificationData::make(title: 'Hi', body: 'Hello');

        $result = $repo->sendToTopic('news', $dto);
        $this->assertTrue($result['success']);
    }

    public function test_send_to_topic_invalid_topic(): void
    {
        $messaging = new FakeMessaging();
        $repo = $this->makeRepositoryWithFakeMessaging($messaging);
        $dto = FirebaseNotificationData::make(title: 'Hi', body: 'Hello');

        $this->expectException(FirebaseNotificationException::class);
        $repo->sendToTopic('invalid topic spaces', $dto);
    }

    public function test_subscribe_to_topic(): void
    {
        $messaging = new FakeMessaging();
        $messaging->subscribeReturn = ['successCount' => 1];

        $repo = $this->makeRepositoryWithFakeMessaging($messaging);
        $result = $repo->subscribeToTopic('news', ['token1234567890']);
        $this->assertIsArray($result);
    }

    public function test_unsubscribe_from_topic(): void
    {
        $messaging = new FakeMessaging();
        $messaging->unsubscribeReturn = ['successCount' => 1];

        $repo = $this->makeRepositoryWithFakeMessaging($messaging);
        $result = $repo->unsubscribeFromTopic('news', ['token1234567890']);
        $this->assertIsArray($result);
    }

    public function test_send_to_tokens_handles_multicast(): void
    {
        $successReport = new FakeReport(true, 'msg1', null);
        $failError = new \Exception('NotRegistered');
        $failReport = new FakeReport(false, null, $failError);

        $messaging = new FakeMessaging();
        $messaging->sendAllReturn = [$successReport, $failReport];

        $repo = $this->makeRepositoryWithFakeMessaging($messaging);
        $dto = FirebaseNotificationData::make(title: 'Hi', body: 'Hello');

        $result = $repo->sendToTokens(['valid_token_1234567890', 'invalid_token_1234567890'], $dto);

        $this->assertEquals(1, $result['success_count']);
        $this->assertEquals(1, $result['failure_count']);
    }

    public function test_firebase_exception_is_wrapped(): void
    {
        $messaging = new FakeMessaging();
        $messaging->sendException = new \Exception('Internal server error');

        $repo = $this->makeRepositoryWithFakeMessaging($messaging);
        $dto = FirebaseNotificationData::make(title: 'Hi', body: 'Hello');

        $this->expectException(FirebaseNotificationException::class);
        $repo->sendToToken('valid_token_1234567890', $dto);
    }

    public function test_send_to_tokens_empty_throws(): void
    {
        $messaging = new FakeMessaging();
        $repo = $this->makeRepositoryWithFakeMessaging($messaging);
        $dto = FirebaseNotificationData::make(title: 'Hi', body: 'Hello');

        $this->expectException(FirebaseNotificationException::class);
        $repo->sendToTokens([], $dto);
    }
}
