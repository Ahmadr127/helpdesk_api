<?php

namespace App\Jobs\Notifications;

use App\DTO\Notifications\FirebaseNotificationData;
use App\Services\Notifications\FirebaseNotificationService;
use App\Exceptions\FirebaseNotificationException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendFirebaseNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @param string $token  FCM token or "topic://{topic}" for topic dispatch
     * @param array  $payload DTO payload array (from FirebaseNotificationData::toQueuePayload)
     */
    public function __construct(
        public readonly string $token,
        public readonly array $payload,
    ) {
    }

    /**
     * Backoff strategy: 10s, 30s, 60s
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(FirebaseNotificationService $service): void
    {
        $notification = FirebaseNotificationData::fromArray($this->payload);

        // Detect topic dispatch
        if (str_starts_with($this->token, 'topic://')) {
            $topic = substr($this->token, strlen('topic://'));
            try {
                $service->sendToTopic($topic, $notification);
                Log::channel($this->logChannel())->info('firebase job topic sent', [
                    'topic' => $topic,
                    'title' => $notification->title,
                    'attempt' => $this->attempts(),
                ]);
            } catch (Throwable $e) {
                Log::channel($this->logChannel())->error('firebase job topic failed', [
                    'topic' => $topic,
                    'error' => $e->getMessage(),
                    'attempt' => $this->attempts(),
                ]);
                throw $e;
            }
            return;
        }

        try {
            $service->sendToToken($this->token, $notification);
            Log::channel($this->logChannel())->info('firebase job sent', [
                'token'   => $this->maskToken($this->token),
                'title'   => $notification->title,
                'attempt' => $this->attempts(),
            ]);
        } catch (FirebaseNotificationException $e) {
            // Do not retry on invalid token - fail fast
            if (in_array($e->getCode(), [FirebaseNotificationException::CODE_INVALID_TOKEN, FirebaseNotificationException::CODE_EXPIRED_TOKEN], true)) {
                Log::channel($this->logChannel())->warning('firebase job invalid token, will not retry', [
                    'token' => $this->maskToken($this->token),
                    'error' => $e->getMessage(),
                ]);
                $this->fail($e);
                return;
            }

            Log::channel($this->logChannel())->error('firebase job failed, will retry', [
                'token'   => $this->maskToken($this->token),
                'error'   => $e->getMessage(),
                'attempt' => $this->attempts(),
                'tries'   => $this->tries,
            ]);
            throw $e;
        } catch (Throwable $e) {
            Log::channel($this->logChannel())->error('firebase job unexpected failed', [
                'token'   => $this->maskToken($this->token),
                'error'   => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::channel($this->logChannel())->error('firebase job permanently failed', [
            'token'     => $this->maskToken($this->token),
            'payload'   => $this->payload,
            'error'     => $exception->getMessage(),
            'exception' => get_class($exception),
            'attempts'  => $this->attempts(),
        ]);

        // Integration point: if you have device_tokens table, mark token invalid here
        // Example (uncomment if table exists):
        // if (FirebaseNotificationException::isInvalidTokenError($exception)) {
        //     \App\Models\DeviceToken::where('token', $this->token)->update(['is_valid' => false]);
        // }
    }

    private function maskToken(string $token): string
    {
        if (str_starts_with($token, 'topic://')) {
            return $token;
        }
        $len = strlen($token);
        if ($len <= 12) {
            return str_repeat('*', $len);
        }
        return substr($token, 0, 6) . str_repeat('*', $len - 10) . substr($token, -4);
    }

    private function logChannel(): string
    {
        $channels = config('logging.channels', []);
        return isset($channels['firebase']) ? 'firebase' : config('logging.default', 'stack');
    }
}
