<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\OrderPerbaikan;

class OrderPerbaikanStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $orderPerbaikan;

    public function __construct(OrderPerbaikan $orderPerbaikan)
    {
        $this->orderPerbaikan = $orderPerbaikan;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $statusMessages = [
            'pending' => 'menunggu persetujuan',
            'in_progress' => 'sedang dalam proses',
            'completed' => 'telah selesai',
            'rejected' => 'ditolak'
        ];

        $message = "Order perbaikan Anda dengan nomor {$this->orderPerbaikan->nomor} " . 
                  $statusMessages[$this->orderPerbaikan->status];

        return (new MailMessage)
            ->subject('Update Status Order Perbaikan')
            ->line($message)
            ->action('Lihat Detail Order', route('user.administrasi-umum.order-perbaikan.show', $this->orderPerbaikan))
            ->line('Terima kasih telah menggunakan layanan kami.');
    }

    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->orderPerbaikan->id,
            'nomor' => $this->orderPerbaikan->nomor,
            'status' => $this->orderPerbaikan->status,
            'message' => "Order perbaikan {$this->orderPerbaikan->nomor} telah diupdate ke status " . 
                        strtoupper($this->orderPerbaikan->status)
        ];
    }
} 