<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\OrderPerbaikan;

class OrderPerbaikanStatusUpdated extends Notification
{

    protected $orderPerbaikan;

    public function __construct(OrderPerbaikan $orderPerbaikan)
    {
        $this->orderPerbaikan = $orderPerbaikan;
    }

    public function via($notifiable)
    {
        // Jika email tidak valid (mis. "administrasi" tanpa domain), jangan kirim mail agar tidak error 500 saat sync
        if (!filter_var($notifiable->email ?? '', FILTER_VALIDATE_EMAIL)) {
            return ['database'];
        }
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $statusMessages = [
            'pending' => 'menunggu persetujuan',
            'open' => 'telah dibuat',
            'in_progress' => 'sedang dalam proses',
            'completed' => 'telah selesai',
            'confirmed' => 'telah dikonfirmasi',
            'rejected' => 'ditolak'
        ];

        $message = "Order perbaikan Anda dengan nomor {$this->orderPerbaikan->nomor} " .
                  ($statusMessages[$this->orderPerbaikan->status] ?? 'telah diperbarui ke status '.$this->orderPerbaikan->status);

        return (new MailMessage)
            ->subject('Update Status Order Perbaikan')
            ->line($message)
            ->action('Lihat Detail Order', route('user.administrasi-umum.order-perbaikan.show', $this->orderPerbaikan))
            ->line('Terima kasih telah menggunakan layanan kami.');
    }

    public function toArray($notifiable)
    {
        $typeMap = [
            'open' => 'order_created',
            'in_progress' => 'order_status_updated',
            'confirmed' => 'order_confirmed',
            'rejected' => 'order_rejected',
        ];
        $type = $typeMap[$this->orderPerbaikan->status] ?? 'order_status_updated';
        return [
            'order_id' => $this->orderPerbaikan->id,
            'nomor' => $this->orderPerbaikan->nomor,
            'status' => $this->orderPerbaikan->status,
            'type' => $type,
            'deep_link' => "helpdesk://order/{$this->orderPerbaikan->id}",
            'url' => route('user.administrasi-umum.order-perbaikan.show', $this->orderPerbaikan),
            'title' => "Order {$this->orderPerbaikan->nomor} - ".ucfirst($this->orderPerbaikan->status),
            'message' => "Order perbaikan {$this->orderPerbaikan->nomor} telah diupdate ke status " .
                        strtoupper($this->orderPerbaikan->status)
        ];
    }
} 