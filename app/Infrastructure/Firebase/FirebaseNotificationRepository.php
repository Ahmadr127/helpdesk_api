<?php

namespace App\Infrastructure\Firebase;

use App\Contracts\Notifications\FirebaseNotificationInterface;
use App\DTO\Notifications\FirebaseNotificationData;
use App\Exceptions\FirebaseNotificationException;
use App\Jobs\Notifications\SendFirebaseNotificationJob;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Throwable;

class FirebaseNotificationRepository implements FirebaseNotificationInterface
{
    public function __construct(
        private readonly FirebaseClient $client
    ) {
    }

    public function sendToToken(string $token, FirebaseNotificationData $notification): array
    {
        $this->validateToken($token);

        $message = $this->buildMessage($notification)->withToken($token);

        try {
            $result = $this->client->messaging()->send($message);
            // $result contains messageId string in newer SDK, or array
            $messageId = is_string($result) ? $result : ($result['name'] ?? null);

            Log::channel($this->logChannel())->info('firebase notification sent', [
                'token'      => $this->maskToken($token),
                'title'      => $notification->title,
                'message_id' => $messageId,
            ]);

            return ['success' => true, 'message_id' => $messageId];
        } catch (Throwable $e) {
            $this->handleSendException($e, $token, $notification);
        }
    }

    public function sendToTokens(array $tokens, FirebaseNotificationData $notification): array
    {
        if (empty($tokens)) {
            throw FirebaseNotificationException::invalidPayload('Tokens array cannot be empty');
        }

        foreach ($tokens as $t) {
            $this->validateToken($t);
        }

        $messages = [];
        foreach ($tokens as $token) {
            $messages[] = $this->buildMessage($notification)->withToken($token);
        }

        try {
            $report = $this->client->messaging()->sendAll($messages);

            $successCount = 0;
            $failureCount = 0;
            $results = [];
            $invalidTokens = [];

            $items = is_object($report) && method_exists($report, 'getItems') ? $report->getItems() : (is_iterable($report) ? $report : []);
            foreach ($items as $idx => $item) {
                // $report is array of SendReport objects in SDK 8.x
                if (is_object($item) && method_exists($item, 'isSuccess')) {
                    $isSuccess = $item->isSuccess();
                    $token = $tokens[$idx] ?? 'unknown';
                    if ($isSuccess) {
                        $successCount++;
                        $results[] = [
                            'token'      => $this->maskToken($token),
                            'success'    => true,
                            'message_id' => method_exists($item, 'result') ? (is_string($item->result()) ? $item->result() : null) : null,
                        ];
                    } else {
                        $failureCount++;
                        $error = $item->error();
                        $msg = $error ? $error->getMessage() : 'Unknown error';
                        $isInvalid = $error ? FirebaseNotificationException::isInvalidTokenError($error) : false;
                        if ($isInvalid) {
                            $invalidTokens[] = $token;
                        }
                        $results[] = [
                            'token'   => $this->maskToken($token),
                            'success' => false,
                            'error'   => $msg,
                        ];
                        Log::channel($this->logChannel())->warning('firebase notification failed for token', [
                            'token' => $this->maskToken($token),
                            'error' => $msg,
                            'invalid_token' => $isInvalid,
                        ]);
                    }
                } else {
                    // Fallback for array structure
                    $results[] = $item;
                }
            }

            Log::channel($this->logChannel())->info('firebase multicast sent', [
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'invalid_tokens' => array_map(fn($t) => $this->maskToken($t), $invalidTokens),
            ]);

            return [
                'success_count'  => $successCount,
                'failure_count'  => $failureCount,
                'results'        => $results,
                'invalid_tokens' => $invalidTokens,
            ];
        } catch (Throwable $e) {
            Log::channel($this->logChannel())->error('firebase multicast exception', [
                'error' => $e->getMessage(),
            ]);
            throw FirebaseNotificationException::apiError($e->getMessage(), $e);
        }
    }

    public function sendToTopic(string $topic, FirebaseNotificationData $notification): array
    {
        $this->validateTopic($topic);

        $message = $this->buildMessage($notification)->withTopic($topic);

        try {
            $result = $this->client->messaging()->send($message);
            $messageId = is_string($result) ? $result : ($result['name'] ?? null);

            Log::channel($this->logChannel())->info('firebase topic notification sent', [
                'topic'      => $topic,
                'title'      => $notification->title,
                'message_id' => $messageId,
            ]);

            return ['success' => true, 'message_id' => $messageId];
        } catch (Throwable $e) {
            Log::channel($this->logChannel())->error('firebase topic send failed', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            throw FirebaseNotificationException::apiError($e->getMessage(), $e);
        }
    }

    public function subscribeToTopic(string $topic, array $tokens): array
    {
        $this->validateTopic($topic);
        if (empty($tokens)) {
            throw FirebaseNotificationException::invalidPayload('Tokens array cannot be empty for subscribe');
        }
        foreach ($tokens as $t) {
            $this->validateToken($t);
        }

        try {
            $result = $this->client->messaging()->subscribeToTopic($topic, $tokens);

            Log::channel($this->logChannel())->info('firebase topic subscribe', [
                'topic' => $topic,
                'tokens_count' => count($tokens),
                'result' => $result,
            ]);

            return is_array($result) ? $result : ['result' => $result];
        } catch (Throwable $e) {
            Log::channel($this->logChannel())->error('firebase topic subscribe failed', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            throw FirebaseNotificationException::topicError($e->getMessage(), $e);
        }
    }

    public function unsubscribeFromTopic(string $topic, array $tokens): array
    {
        $this->validateTopic($topic);
        if (empty($tokens)) {
            throw FirebaseNotificationException::invalidPayload('Tokens array cannot be empty for unsubscribe');
        }
        foreach ($tokens as $t) {
            $this->validateToken($t);
        }

        try {
            $result = $this->client->messaging()->unsubscribeFromTopic($topic, $tokens);

            Log::channel($this->logChannel())->info('firebase topic unsubscribe', [
                'topic' => $topic,
                'tokens_count' => count($tokens),
                'result' => $result,
            ]);

            return is_array($result) ? $result : ['result' => $result];
        } catch (Throwable $e) {
            Log::channel($this->logChannel())->error('firebase topic unsubscribe failed', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            throw FirebaseNotificationException::topicError($e->getMessage(), $e);
        }
    }

    public function queue(string $token, FirebaseNotificationData $notification, ?string $queue = null): void
    {
        $this->validateToken($token);
        $job = new SendFirebaseNotificationJob($token, $notification->toQueuePayload());
        $job->onQueue($queue ?? 'notifications');
        dispatch($job);
    }

    public function queueToTokens(array $tokens, FirebaseNotificationData $notification, ?string $queue = null): void
    {
        foreach ($tokens as $token) {
            $this->queue($token, $notification, $queue);
        }
    }

    public function queueToTopic(string $topic, FirebaseNotificationData $notification, ?string $queue = null): void
    {
        // Dispatch job with topic prefix - job will detect topic:// prefix
        $this->validateTopic($topic);
        $job = new SendFirebaseNotificationJob('topic://' . $topic, $notification->toQueuePayload());
        $job->onQueue($queue ?? 'notifications');
        dispatch($job);
    }

    /**
     * Build Kreait CloudMessage from DTO.
     */
    private function buildMessage(FirebaseNotificationData $data): CloudMessage
    {
        $notification = Notification::create($data->title, $data->body);

        if ($data->image !== null) {
            $notification = Notification::create($data->title, $data->body, $data->image);
        }

        $message = CloudMessage::new()->withNotification($notification);

        if (!empty($data->data)) {
            $message = $message->withData($data->data);
        }

        // Android config
        $androidConfig = [];
        if ($data->channelId !== null) {
            $androidConfig['notification'] = ['channel_id' => $data->channelId];
        }
        if ($data->sound !== null) {
            $androidConfig['notification']['sound'] = $data->sound;
        }
        if (!empty($androidConfig)) {
            $message = $message->withAndroidConfig($androidConfig);
        }

        // APNS config for sound
        if ($data->sound !== null) {
            $message = $message->withApnsConfig([
                'payload' => [
                    'aps' => [
                        'sound' => $data->sound,
                    ],
                ],
            ]);
        }

        return $message;
    }

    private function validateToken(string $token): void
    {
        if (trim($token) === '') {
            throw FirebaseNotificationException::invalidPayload('FCM token cannot be empty');
        }
        if (strlen($token) < 10) {
            throw FirebaseNotificationException::invalidPayload('FCM token too short, possibly invalid');
        }
    }

    private function validateTopic(string $topic): void
    {
        if (trim($topic) === '') {
            throw FirebaseNotificationException::invalidPayload('Topic cannot be empty');
        }
        if (!preg_match('/^[a-zA-Z0-9\-_\.~%]+$/', $topic)) {
            throw FirebaseNotificationException::invalidPayload('Topic contains invalid characters. Must match [a-zA-Z0-9-_.~%]+');
        }
    }

    /**
     * @throws FirebaseNotificationException
     */
    private function handleSendException(Throwable $e, string $token, FirebaseNotificationData $notification): never
    {
        $isInvalid = FirebaseNotificationException::isInvalidTokenError($e);

        Log::channel($this->logChannel())->error('firebase notification failed', [
            'token'      => $this->maskToken($token),
            'title'      => $notification->title,
            'error'      => $e->getMessage(),
            'is_invalid_token' => $isInvalid,
        ]);

        if ($isInvalid) {
            // Check if project has device_tokens table - document integration point, do not auto-delete unless validated
            // We log and throw specific exception so caller can decide to cleanup token
            throw FirebaseNotificationException::invalidToken($this->maskToken($token), $e);
        }

        if ($e instanceof MessagingException || $e instanceof FirebaseException) {
            throw FirebaseNotificationException::apiError($e->getMessage(), $e);
        }

        // Check for network errors via message
        $msg = strtolower($e->getMessage());
        if (str_contains($msg, 'cURL') || str_contains($msg, 'network') || str_contains($msg, 'timeout') || str_contains($msg, 'connection')) {
            throw FirebaseNotificationException::networkError($e->getMessage(), $e);
        }

        throw FirebaseNotificationException::apiError($e->getMessage(), $e);
    }

    private function maskToken(string $token): string
    {
        $len = strlen($token);
        if ($len <= 12) {
            return str_repeat('*', $len);
        }
        return substr($token, 0, 6) . str_repeat('*', $len - 10) . substr($token, -4);
    }

    private function logChannel(): string
    {
        try {
            $channels = config('logging.channels', []);
            if (isset($channels['firebase'])) {
                return 'firebase';
            }
            return config('logging.default', 'stack');
        } catch (\Throwable $e) {
            return 'stack';
        }
    }
}
