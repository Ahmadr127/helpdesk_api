<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // Audit log otomatis untuk semua model yang terdaftar di config/audit.php
        // (satu lokasi — menambah model baru cukup menambah entri di config).
        foreach (config('audit.models') ?? [] as $model) {
            $model::observe(\App\Observers\AuditObserver::class);
        }

        // Paksa https hanya jika FORCE_HTTPS=true di .env — bisa dimatikan untuk local/dev
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        // Popup pengingat konfirmasi: tiket `closed` milik user +
        // order `tutup` buatan user. Dibagikan ke shell user & admin
        // agar popup selalu muncul selama masih ada yang menunggu.
        View::composer(
            ['user.layouts.app', 'admin.layouts.app'],
            function ($view) {
                $pending = ['tickets' => [], 'orders' => [], 'total' => 0];
                $user = auth()->user();
                if ($user) {
                    $tickets = \App\Models\Pengajuan::sirs()
                        ->where('pemohon_id', $user->id)
                        ->where('status', 'ditutup')
                        ->latest()
                        ->limit(5)
                        ->get(['id', 'nomor', 'deskripsi', 'status']);
                    $orders = \App\Models\Pengajuan::umum()
                        ->where('pemohon_id', $user->id)
                        ->where('status', 'ditutup')
                        ->latest()
                        ->limit(5)
                        ->get(['id', 'nomor', 'deskripsi', 'status']);
                    $pending = [
                        'tickets' => $tickets->map(fn ($t) => [
                            'label' => $t->nomor ?: 'Tiket #'.$t->id,
                            'title' => \Illuminate\Support\Str::limit($t->deskripsi ?? '-', 60),
                            'url' => route('user.pengajuan.show', $t->id),
                        ])->toArray(),
                        'orders' => $orders->map(fn ($o) => [
                            'label' => $o->nomor ?: 'Order #'.$o->id,
                            'title' => \Illuminate\Support\Str::limit($o->deskripsi ?? '-', 60),
                            'url' => route('user.pengajuan.show', $o->id),
                        ])->toArray(),
                        'total' => $tickets->count() + $orders->count(),
                    ];
                }
                $view->with('pendingConfirmations', $pending);
            }
        );
    }
}
