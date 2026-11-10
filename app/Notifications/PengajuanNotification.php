<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi in-app (database) untuk pengajuan.
 *
 * Payload dibuat sama dengan payload FCM oleh PengajuanNotificationService,
 * sehingga web bell dan push mobile menerima data yang identik:
 *  type, pengajuan_id, nomor, status, prioritas, deep_link (+ title/message).
 */
class PengajuanNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly array $data,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return $this->data;
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
