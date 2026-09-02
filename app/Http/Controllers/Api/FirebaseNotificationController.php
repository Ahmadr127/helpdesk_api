<?php

namespace App\Http\Controllers\Api;

use App\DTO\Notifications\FirebaseNotificationData;
use App\Exceptions\FirebaseNotificationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Firebase\FirebaseTopicSubscriptionRequest;
use App\Http\Requests\Firebase\SendFirebaseNotificationManyRequest;
use App\Http\Requests\Firebase\SendFirebaseNotificationRequest;
use App\Http\Requests\Firebase\SendFirebaseTopicNotificationRequest;
use App\Contracts\Notifications\FirebaseNotificationInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationController extends Controller
{
    public function __construct(
        private readonly FirebaseNotificationInterface $firebase
    ) {
    }

    /**
     * POST /api/firebase/notification/send
     */
    public function send(SendFirebaseNotificationRequest $request): JsonResponse
    {
        $data = $this->buildDto($request);

        $token = $request->input('token');
        $useQueue = $request->boolean('queue', false);

        try {
            if ($useQueue) {
                $this->firebase->queue($token, $data);
                return response()->json([
                    'success' => true,
                    'message' => 'Notification queued',
                    'queued' => true,
                    'token' => $this->maskToken($token),
                ]);
            }

            $result = $this->firebase->sendToToken($token, $data);

            return response()->json([
                'success' => true,
                'message' => 'Notification sent',
                'queued' => false,
                'data' => $result,
            ]);
        } catch (FirebaseNotificationException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/firebase/notification/send-many
     */
    public function sendMany(SendFirebaseNotificationManyRequest $request): JsonResponse
    {
        $data = $this->buildDto($request);
        $tokens = $request->input('tokens');
        $useQueue = $request->boolean('queue', false);

        try {
            if ($useQueue) {
                $this->firebase->queueToTokens($tokens, $data);
                return response()->json([
                    'success' => true,
                    'message' => 'Notifications queued',
                    'queued' => true,
                    'count' => count($tokens),
                ]);
            }

            $result = $this->firebase->sendToTokens($tokens, $data);

            return response()->json([
                'success' => true,
                'message' => 'Notifications sent',
                'queued' => false,
                'data' => $result,
            ]);
        } catch (FirebaseNotificationException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/firebase/notification/topic
     */
    public function sendToTopic(SendFirebaseTopicNotificationRequest $request): JsonResponse
    {
        $data = $this->buildDto($request);
        $topic = $request->input('topic');
        $useQueue = $request->boolean('queue', false);

        try {
            if ($useQueue) {
                $this->firebase->queueToTopic($topic, $data);
                return response()->json([
                    'success' => true,
                    'message' => 'Topic notification queued',
                    'queued' => true,
                    'topic' => $topic,
                ]);
            }

            $result = $this->firebase->sendToTopic($topic, $data);

            return response()->json([
                'success' => true,
                'message' => 'Topic notification sent',
                'queued' => false,
                'data' => $result,
            ]);
        } catch (FirebaseNotificationException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/firebase/topic/subscribe
     */
    public function subscribe(FirebaseTopicSubscriptionRequest $request): JsonResponse
    {
        $topic = $request->input('topic');
        $tokens = $request->getTokens();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'Tokens required',
            ], 422);
        }

        try {
            $result = $this->firebase->subscribeToTopic($topic, $tokens);
            return response()->json([
                'success' => true,
                'message' => 'Subscribed to topic',
                'topic' => $topic,
                'data' => $result,
            ]);
        } catch (FirebaseNotificationException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/firebase/topic/unsubscribe
     */
    public function unsubscribe(FirebaseTopicSubscriptionRequest $request): JsonResponse
    {
        $topic = $request->input('topic');
        $tokens = $request->getTokens();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'Tokens required',
            ], 422);
        }

        try {
            $result = $this->firebase->unsubscribeFromTopic($topic, $tokens);
            return response()->json([
                'success' => true,
                'message' => 'Unsubscribed from topic',
                'topic' => $topic,
                'data' => $result,
            ]);
        } catch (FirebaseNotificationException $e) {
            return $this->handleException($e);
        }
    }

    private function buildDto($request): FirebaseNotificationData
    {
        $notif = $request->input('notification', []);
        $data = $request->input('data', []);

        return FirebaseNotificationData::make(
            title: $notif['title'],
            body: $notif['body'],
            data: $data,
            image: $notif['image'] ?? null,
            sound: $notif['sound'] ?? null,
            channelId: $notif['channel_id'] ?? $notif['channelId'] ?? null,
        );
    }

    private function handleException(FirebaseNotificationException $e): JsonResponse
    {
        $status = match ($e->getCode()) {
            FirebaseNotificationException::CODE_INVALID_TOKEN,
            FirebaseNotificationException::CODE_EXPIRED_TOKEN => 422,
            FirebaseNotificationException::CODE_INVALID_PAYLOAD => 422,
            FirebaseNotificationException::CODE_CREDENTIAL_MISSING,
            FirebaseNotificationException::CODE_CREDENTIAL_INVALID => 500,
            default => 500,
        };

        // Never expose credential details
        $safeMessage = $e->getMessage();
        if (in_array($e->getCode(), [FirebaseNotificationException::CODE_CREDENTIAL_MISSING, FirebaseNotificationException::CODE_CREDENTIAL_INVALID], true)) {
            $safeMessage = 'Firebase credential error. Check server logs.';
        }

        Log::channel($this->logChannel())->error('firebase api error', [
            'code' => $e->getCode(),
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $safeMessage,
            'code' => $e->getCode(),
        ], $status);
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
        $channels = config('logging.channels', []);
        return isset($channels['firebase']) ? 'firebase' : config('logging.default', 'stack');
    }
}
