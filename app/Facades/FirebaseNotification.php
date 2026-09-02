<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\DTO\Notifications\FirebaseNotificationData;

/**
 * @method static array sendToToken(string $token, FirebaseNotificationData $notification)
 * @method static array sendToTokens(array $tokens, FirebaseNotificationData $notification)
 * @method static array sendToTopic(string $topic, FirebaseNotificationData $notification)
 * @method static array subscribeToTopic(string $topic, array $tokens)
 * @method static array unsubscribeFromTopic(string $topic, array $tokens)
 * @method static void queue(string $token, FirebaseNotificationData $notification, string|null $queue = null)
 * @method static void queueToTokens(array $tokens, FirebaseNotificationData $notification, string|null $queue = null)
 * @method static void queueToTopic(string $topic, FirebaseNotificationData $notification, string|null $queue = null)
 *
 * One-line facade usage:
 * FirebaseNotification::sendToToken($token, FirebaseNotificationData::make(title: 'Hi', body: 'Hello'));
 * FirebaseNotification::queue($token, $data);
 */
class FirebaseNotification extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Contracts\Notifications\FirebaseNotificationInterface::class;
    }
}
