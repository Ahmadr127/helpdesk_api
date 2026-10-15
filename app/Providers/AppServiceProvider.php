<?php

namespace App\Providers;

use App\Models\OrderPerbaikan;
use App\Models\Ticket;
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
                    $tickets = Ticket::where('user_id', $user->id)
                        ->where('status', 'closed')
                        ->latest()
                        ->limit(5)
                        ->get(['id', 'ticket_number', 'description', 'status']);
                    $orders = OrderPerbaikan::where('created_by', $user->id)
                        ->where('status', 'tutup')
                        ->latest()
                        ->limit(5)
                        ->get(['id', 'nomor', 'nama_barang', 'status']);
                    $pending = [
                        'tickets' => $tickets->map(fn ($t) => [
                            'label' => $t->ticket_number ?: 'Tiket #'.$t->id,
                            'title' => \Illuminate\Support\Str::limit($t->description ?? '-', 60),
                            'url' => route('user.ticket.show', $t->id),
                        ])->toArray(),
                        'orders' => $orders->map(fn ($o) => [
                            'label' => $o->nomor ?: 'Order #'.$o->id,
                            'title' => $o->nama_barang ?? '-',
                            'url' => route('user.administrasi-umum.order-perbaikan.show', $o->id),
                        ])->toArray(),
                        'total' => $tickets->count() + $orders->count(),
                    ];
                }
                $view->with('pendingConfirmations', $pending);
            }
        );
    }
}
