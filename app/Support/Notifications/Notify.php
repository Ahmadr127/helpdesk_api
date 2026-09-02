<?php

namespace App\Support\Notifications;

use App\DTO\Notifications\FirebaseNotificationData;
use App\Facades\FirebaseNotification;
use App\Models\OrderPerbaikan;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Simple 1-line notification helper.
 *
 * Usage di Service setelah transaction commit:
 *   Notify::ticketToAdmins($ticket, 'created', $actor);
 *   Notify::ticketToUser($ticket, 'responded', $admin, $notes);
 *   Notify::orderToAdmins($order, $actor);
 *   Notify::orderToUser($order, 'status_updated', $admin);
 */
class Notify
{
    // ========== TICKET ==========

    /**
     * Notify Admin IT when user creates/replies/confirms/rejects ticket.
     *
     * @param Ticket $ticket
     * @param string $event ticket_created|ticket_replied|ticket_confirmed|ticket_rejected
     * @param User $actor  User yang melakukan aksi
     */
    public static function ticketToAdmins(Ticket $ticket, string $event, User $actor): void
    {
        try {
            $admins = User::adminIT()->get();
            $tokens = [];
            foreach ($admins as $admin) {
                $tokens = array_merge($tokens, $admin->getAllFcmTokens());
                // Also collect from device_tokens relation if not via getAllFcmTokens? getAllFcmTokens already does
            }
            $tokens = array_values(array_unique(array_filter($tokens)));

            if (empty($tokens)) {
                Log::channel(self::logChannel())->info('notify ticketToAdmins no tokens', ['event' => $event, 'ticket_id' => $ticket->id]);
                return;
            }

            [$title, $body, $data] = self::buildTicketAdminPayload($ticket, $event, $actor);

            $dto = FirebaseNotificationData::make(title: $title, body: $body, data: $data);

            // Use batch queueToTokens for efficiency
            FirebaseNotification::queueToTokens($tokens, $dto);

            Log::channel(self::logChannel())->info('notify ticketToAdmins queued', [
                'event' => $event,
                'ticket_id' => $ticket->id,
                'tokens_count' => count($tokens),
            ]);
        } catch (\Throwable $e) {
            Log::channel(self::logChannel())->error('notify ticketToAdmins failed', ['error' => $e->getMessage(), 'ticket_id' => $ticket->id ?? null]);
        }
    }

    /**
     * Notify ticket owner (pengaju) when admin responds/updates.
     *
     * @param string $event ticket_responded|ticket_updated|ticket_replied
     */
    public static function ticketToUser(Ticket $ticket, string $event, User $actor, ?string $notes = null): void
    {
        try {
            $user = $ticket->user;
            if (!$user) {
                $user = User::find($ticket->user_id);
            }
            if (!$user) {
                return;
            }

            $tokens = $user->getAllFcmTokens();
            if (empty($tokens)) {
                Log::channel(self::logChannel())->info('notify ticketToUser no tokens', ['event' => $event, 'ticket_id' => $ticket->id, 'user_id' => $user->id]);
                return;
            }

            [$title, $body, $data] = self::buildTicketUserPayload($ticket, $event, $actor, $notes);

            $dto = FirebaseNotificationData::make(title: $title, body: $body, data: $data, image: self::ticketImageUrl($ticket));

            foreach ($tokens as $token) {
                FirebaseNotification::queue($token, $dto);
            }

            Log::channel(self::logChannel())->info('notify ticketToUser queued', ['event' => $event, 'ticket_id' => $ticket->id, 'user_id' => $user->id]);
        } catch (\Throwable $e) {
            Log::channel(self::logChannel())->error('notify ticketToUser failed', ['error' => $e->getMessage(), 'ticket_id' => $ticket->id ?? null]);
        }
    }

    // ========== ORDER ==========

    public static function orderToAdmins(OrderPerbaikan $order, User $actor): void
    {
        try {
            $admins = User::adminUmum()->get();
            $tokens = [];
            foreach ($admins as $admin) {
                $tokens = array_merge($tokens, $admin->getAllFcmTokens());
            }
            $tokens = array_values(array_unique(array_filter($tokens)));

            if (empty($tokens)) {
                Log::channel(self::logChannel())->info('notify orderToAdmins no tokens', ['order_id' => $order->id]);
                return;
            }

            $title = "Order Baru {$order->nomor}";
            $body = "{$actor->name}: {$order->nama_barang} - {$order->keluhan}";
            $body = mb_strimwidth($body, 0, 100, '...');

            $data = [
                'type' => 'order_created',
                'order_id' => (string) $order->id,
                'nomor' => (string) $order->nomor,
                'status' => (string) $order->status,
                'prioritas' => (string) $order->prioritas,
                'deep_link' => "helpdesk://order/{$order->id}",
            ];

            $dto = FirebaseNotificationData::make(title: $title, body: $body, data: $data);

            FirebaseNotification::queueToTokens($tokens, $dto);

            Log::channel(self::logChannel())->info('notify orderToAdmins queued', ['order_id' => $order->id, 'tokens_count' => count($tokens)]);
        } catch (\Throwable $e) {
            Log::channel(self::logChannel())->error('notify orderToAdmins failed', ['error' => $e->getMessage(), 'order_id' => $order->id ?? null]);
        }
    }

    public static function orderToUser(OrderPerbaikan $order, string $event, User $actor): void
    {
        try {
            $user = $order->creator;
            if (!$user) {
                $user = User::find($order->created_by);
            }
            if (!$user) {
                return;
            }

            $tokens = $user->getAllFcmTokens();
            if (empty($tokens)) {
                Log::channel(self::logChannel())->info('notify orderToUser no tokens', ['event' => $event, 'order_id' => $order->id]);
                return;
            }

            [$title, $body, $data] = self::buildOrderUserPayload($order, $event, $actor);

            $dto = FirebaseNotificationData::make(title: $title, body: $body, data: $data, image: self::orderImageUrl($order));

            foreach ($tokens as $token) {
                FirebaseNotification::queue($token, $dto);
            }

            Log::channel(self::logChannel())->info('notify orderToUser queued', ['event' => $event, 'order_id' => $order->id]);
        } catch (\Throwable $e) {
            Log::channel(self::logChannel())->error('notify orderToUser failed', ['error' => $e->getMessage(), 'order_id' => $order->id ?? null]);
        }
    }

    // ========== PAYLOAD BUILDERS ==========

    private static function buildTicketAdminPayload(Ticket $ticket, string $event, User $actor): array
    {
        $number = $ticket->ticket_number ?? "#{$ticket->id}";
        $data = [
            'type' => $event,
            'ticket_id' => (string) $ticket->id,
            'ticket_number' => (string) $number,
            'status' => (string) $ticket->status,
            'deep_link' => "helpdesk://ticket/{$ticket->id}",
            'actor_id' => (string) $actor->id,
        ];

        return match ($event) {
            'ticket_created' => ["Tiket Baru {$number}", "{$actor->name}: ".mb_strimwidth($ticket->description, 0, 80, '...'), $data],
            'ticket_replied' => ["Balasan Tiket {$number}", "{$actor->name} membalas tiket", $data],
            'ticket_confirmed' => ["Tiket Dikonfirmasi {$number}", "{$actor->name} mengkonfirmasi penyelesaian", $data],
            'ticket_rejected' => ["Tiket Ditolak {$number}", "{$actor->name} menolak penyelesaian, tiket kembali in_progress", $data],
            default => ["Update Tiket {$number}", "{$actor->name} memperbarui tiket", $data],
        };
    }

    private static function buildTicketUserPayload(Ticket $ticket, string $event, User $actor, ?string $notes): array
    {
        $number = $ticket->ticket_number ?? "#{$ticket->id}";
        $data = [
            'type' => $event,
            'ticket_id' => (string) $ticket->id,
            'ticket_number' => (string) $number,
            'status' => (string) $ticket->status,
            'deep_link' => "helpdesk://ticket/{$ticket->id}",
            'actor_id' => (string) $actor->id,
        ];

        $notesSnippet = $notes ? mb_strimwidth($notes, 0, 80, '...') : '';

        return match ($event) {
            'ticket_responded' => ["Admin Membalas {$number}", $notesSnippet ?: "Tiket Anda dibalas oleh {$actor->name} (status: {$ticket->status})", $data],
            'ticket_updated' => ["Tiket Diperbarui {$number}", $notesSnippet ?: "Tiket Anda diperbarui (status: {$ticket->status})", $data],
            'ticket_replied' => ["Balasan Admin {$number}", $notesSnippet ?: "Ada balasan baru", $data],
            default => ["Update Tiket {$number}", $notesSnippet ?: "Status tiket: {$ticket->status}", $data],
        };
    }

    private static function buildOrderUserPayload(OrderPerbaikan $order, string $event, User $actor): array
    {
        $data = [
            'order_id' => (string) $order->id,
            'nomor' => (string) $order->nomor,
            'status' => (string) $order->status,
            'prioritas' => (string) $order->prioritas,
            'deep_link' => "helpdesk://order/{$order->id}",
            'actor_id' => (string) $actor->id,
        ];

        $follow = $order->follow_up ? mb_strimwidth($order->follow_up, 0, 80, '...') : '';

        return match ($event) {
            'order_status_updated' => ["Order {$order->nomor} → {$order->status}", $follow ?: "Status order diperbarui ke {$order->status} oleh {$actor->name}", array_merge($data, ['type' => $event])],
            'order_confirmed' => ["Order Dikonfirmasi {$order->nomor}", "Order Anda telah dikonfirmasi", array_merge($data, ['type' => $event])],
            'order_rejected' => ["Order Ditolak {$order->nomor}", $follow ?: "Order ditolak", array_merge($data, ['type' => $event])],
            'order_started' => ["Order Diproses {$order->nomor}", "Pengerjaan dimulai oleh {$actor->name}", array_merge($data, ['type' => $event])],
            default => ["Update Order {$order->nomor}", $follow ?: "Status: {$order->status}", array_merge($data, ['type' => $event])],
        };
    }

    private static function ticketImageUrl(Ticket $ticket): ?string
    {
        try {
            $photo = $ticket->initialPhoto ?? $ticket->photos()->first();
            if ($photo && !empty($photo->photo_path)) {
                // photo_path like ticket-photos/xxx.jpg stored on public disk
                if (str_starts_with($photo->photo_path, 'http')) {
                    return $photo->photo_path;
                }
                return Storage::disk('public')->url($photo->photo_path);
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return null;
    }

    private static function orderImageUrl(OrderPerbaikan $order): ?string
    {
        try {
            if (!empty($order->foto)) {
                if (str_starts_with($order->foto, 'http')) {
                    return $order->foto;
                }
                return Storage::disk('public')->url($order->foto);
            }
        } catch (\Throwable $e) {
        }
        return null;
    }

    private static function logChannel(): string
    {
        try {
            $channels = config('logging.channels', []);
            return isset($channels['firebase']) ? 'firebase' : config('logging.default', 'stack');
        } catch (\Throwable $e) {
            return 'stack';
        }
    }
}
