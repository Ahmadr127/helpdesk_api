<?php

namespace App\Notifications;

use App\Models\OrderPerbaikan;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi untuk Petugas Penanggung Jawab (PJ) yang ditugaskan pada order.
 * Sengaja hanya lewat kanal database (inbox) + FCM dikirim terpisah oleh
 * Notify::orderToUsers — tanpa email, karena email "Order Perbaikan" ditujukan
 * untuk pemohon, bukan petugas.
 */
class OrderPerbaikanAssigned extends Notification
{
    public function __construct(protected OrderPerbaikan $orderPerbaikan) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->orderPerbaikan->id,
            'nomor' => $this->orderPerbaikan->nomor,
            'status' => $this->orderPerbaikan->status,
            'type' => 'order_assigned',
            'deep_link' => "helpdesk://order/{$this->orderPerbaikan->id}",
            'url' => route('user.administrasi-umum.order-perbaikan.show', $this->orderPerbaikan),
            'title' => "Order {$this->orderPerbaikan->nomor} ditugaskan",
            'message' => "Anda ditugaskan menangani order {$this->orderPerbaikan->nomor}",
        ];
    }
}
