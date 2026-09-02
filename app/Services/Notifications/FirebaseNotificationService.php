<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\FirebaseNotificationInterface;
use App\DTO\Notifications\FirebaseNotificationData;

class FirebaseNotificationService implements FirebaseNotificationInterface
{
    public function __construct(
        private readonly FirebaseNotificationInterface $repository
    ) {
    }

    public function sendToToken(string $token, FirebaseNotificationData $notification): array
    {
        return $this->repository->sendToToken($token, $notification);
    }

    public function sendToTokens(array $tokens, FirebaseNotificationData $notification): array
    {
        return $this->repository->sendToTokens($tokens, $notification);
    }

    public function sendToTopic(string $topic, FirebaseNotificationData $notification): array
    {
        return $this->repository->sendToTopic($topic, $notification);
    }

    public function subscribeToTopic(string $topic, array $tokens): array
    {
        return $this->repository->subscribeToTopic($topic, $tokens);
    }

    public function unsubscribeFromTopic(string $topic, array $tokens): array
    {
        return $this->repository->unsubscribeFromTopic($topic, $tokens);
    }

    public function queue(string $token, FirebaseNotificationData $notification, ?string $queue = null): void
    {
        $this->repository->queue($token, $notification, $queue);
    }

    public function queueToTokens(array $tokens, FirebaseNotificationData $notification, ?string $queue = null): void
    {
        $this->repository->queueToTokens($tokens, $notification, $queue);
    }

    public function queueToTopic(string $topic, FirebaseNotificationData $notification, ?string $queue = null): void
    {
        $this->repository->queueToTopic($topic, $notification, $queue);
    }
}
