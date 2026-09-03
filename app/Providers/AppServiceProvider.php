<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Paksa https hanya jika FORCE_HTTPS=true di .env — bisa dimatikan untuk local/dev
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }
    }
}