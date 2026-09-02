<?php

namespace App\Contracts\Notifications;

use App\DTO\Notifications\FirebaseNotificationData;

interface FirebaseNotificationInterface
{
    /**
     * Send to single FCM token (synchronous).
     *
     * @return array{success: bool, message_id: ?string}
     */
    public function sendToToken(string $token, FirebaseNotificationData $notification): array;

    /**
     * Send to multiple FCM tokens.
     *
     * @param string[] $tokens
     * @return array{success_count: int, failure_count: int, results: array, invalid_tokens: string[]}
     */
    public function sendToTokens(array $tokens, FirebaseNotificationData $notification): array;

    /**
     * Send to topic.
     *
     * @return array{success: bool, message_id: ?string}
     */
    public function sendToTopic(string $topic, FirebaseNotificationData $notification): array;

    /**
     * Subscribe tokens to topic.
     *
     * @param string[] $tokens
     * @return array
     */
    public function subscribeToTopic(string $topic, array $tokens): array;

    /**
     * Unsubscribe tokens from topic.
     *
     * @param string[] $tokens
     * @return array
     */
    public function unsubscribeFromTopic(string $topic, array $tokens): array;

    /**
     * Queue notification for async sending via Job.
     */
    public function queue(string $token, FirebaseNotificationData $notification, ?string $queue = null): void;

    /**
     * Queue notification to multiple tokens.
     *
     * @param string[] $tokens
     */
    public function queueToTokens(array $tokens, FirebaseNotificationData $notification, ?string $queue = null): void;

    /**
     * Queue notification to topic.
     */
    public function queueToTopic(string $topic, FirebaseNotificationData $notification, ?string $queue = null): void;
}
