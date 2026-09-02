<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Notifications\FirebaseNotificationInterface;
use App\Infrastructure\Firebase\FirebaseClient;
use App\Infrastructure\Firebase\FirebaseNotificationRepository;
use App\Services\Notifications\FirebaseNotificationService;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FirebaseClient::class, function () {
            return new FirebaseClient();
        });

        $this->app->singleton(FirebaseNotificationRepository::class, function ($app) {
            return new FirebaseNotificationRepository(
                $app->make(FirebaseClient::class)
            );
        });

        // Bind interface to service (which wraps repository)
        $this->app->singleton(FirebaseNotificationInterface::class, function ($app) {
            return new FirebaseNotificationService(
                $app->make(FirebaseNotificationRepository::class)
            );
        });

        // Alias for convenient DI
        $this->app->alias(FirebaseNotificationInterface::class, 'firebase.notification');
    }

    public function boot(): void
    {
        // Global alias for convenient one-line usage: FirebaseNotification::sendToToken(...)
        if (!class_exists('FirebaseNotification')) {
            class_alias(\App\Facades\FirebaseNotification::class, 'FirebaseNotification');
        }
    }
}
