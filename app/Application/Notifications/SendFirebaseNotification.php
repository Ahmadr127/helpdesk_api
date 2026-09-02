<?php

namespace App\Application\Notifications;

use App\Contracts\Notifications\FirebaseNotificationInterface;
use App\DTO\Notifications\FirebaseNotificationData;

class SendFirebaseNotification
{
    public function __construct(
        private readonly FirebaseNotificationInterface $notifier
    ) {
    }

    /**
     * Synchronous send to token - Use Case layer.
     */
    public function toToken(string $token, FirebaseNotificationData $data): array
    {
        return $this->notifier->sendToToken($token, $data);
    }

    /**
     * Asynchronous queue to token.
     */
    public function queueToToken(string $token, FirebaseNotificationData $data): void
    {
        $this->notifier->queue($token, $data);
    }

    public function toTokens(array $tokens, FirebaseNotificationData $data): array
    {
        return $this->notifier->sendToTokens($tokens, $data);
    }

    public function toTopic(string $topic, FirebaseNotificationData $data): array
    {
        return $this->notifier->sendToTopic($topic, $data);
    }

    public function subscribe(string $topic, array $tokens): array
    {
        return $this->notifier->subscribeToTopic($topic, $tokens);
    }

    public function unsubscribe(string $topic, array $tokens): array
    {
        return $this->notifier->unsubscribeFromTopic($topic, $tokens);
    }
}
