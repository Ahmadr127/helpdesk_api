<?php

namespace App\Services;

use App\Models\Category;
use App\Models\KategoriOrder;
use App\Models\OrderPerbaikan;
use App\Models\PengajuanLink;
use App\Models\Ticket;
use App\Models\TicketPhoto;
use App\Models\User;
use App\Notifications\OrderPerbaikanStatusUpdated;
use App\Notifications\TicketRespondedNotification;
use App\Services\Api\OrderPerbaikanService;
use App\Services\Api\TicketService;
use App\Support\Notifications\Notify;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pengajuan Gabungan: Tiket IT (SIRS) + Order Perbaikan, berbasis kategori target.
 *
 * Satu pengajuan yang memakai kategori di TARGET_KATEGORI (dan kategori tsb ada
 * di kedua master aktif) akan otomatis membuat record pasangannya, ditautkan di
 * `pengajuan_links`, lalu statusnya disinkronkan dua arah.
 *
 * Seluruh logic sengaja ditempatkan di SATU file ini (tidak dipisah) sesuai permintaan.
 */
class PengajuanSinkronService
{
    /** Nama kategori yang menjadi target sinkronisasi. Tambah nama di sini. */
    public const TARGET_KATEGORI = ['TV'];

    /** Guard re-entrancy agar pembuatan counterpart tidak saling memicu (ping-pong). */
    protected static bool $creating = false;

    /** Guard anti-loop untuk sinkron status dua arah. */
    protected static bool $syncing = false;

    // =====================================================================
    // Deteksi target
    // =====================================================================

    /**
     * Apakah kategori (id tiket dan/atau nama kategori order) merupakan target
     * pengajuan gabungan: namanya ada di TARGET_KATEGORI DAN ada di kedua master aktif.
     */
    public function isTarget(?int $categoryId = null, ?string $kategoriOrderName = null): bool
    {
        $name = $kategoriOrderName;
        if ($categoryId) {
            $name = Category::find($categoryId)?->name ?? $name;
        }
        if (! $name) {
            return false;
        }

        return $this->isTargetName($name)
            && $this->categoryExists($name)
            && $this->kategoriOrderExists($name);
    }

    public function isTargetName(?string $name): bool
    {
        $name = $this->normalize($name);

        foreach (self::TARGET_KATEGORI as $target) {
            if ($this->normalize($target) === $name && $name !== '') {
                return true;
            }
        }

        return false;
    }

    protected function categoryExists(string $name): bool
    {
        return Category::where('status', 1)
            ->whereRaw('LOWER(name) = ?', [$this->normalize($name)])
            ->whereHas('unitProses', fn ($q) => $q->where('code', 'SIRS'))
            ->exists();
    }

    protected function kategoriOrderExists(string $name): bool
    {
        return KategoriOrder::where('status', 1)
            ->whereRaw('LOWER(name) = ?', [$this->normalize($name)])
            ->exists();
    }

    public function categoryIdByName(string $name): ?int
    {
        return Category::where('status', 1)
            ->whereRaw('LOWER(name) = ?', [$this->normalize($name)])
            ->whereHas('unitProses', fn ($q) => $q->where('code', 'SIRS'))
            ->value('id');
    }

    public function kategoriOrderNameForCategory(int $categoryId): ?string
    {
        return Category::find($categoryId)?->name;
    }

    public function categoryIdForKategoriOrder(string $name): ?int
    {
        return $this->categoryIdByName($name);
    }

    // =====================================================================
    // Penautan
    // =====================================================================

    public function link(Ticket $ticket, OrderPerbaikan $order, string $kategoriName, ?User $actor = null): PengajuanLink
    {
        $link = PengajuanLink::where('ticket_id', $ticket->id)
            ->orWhere('order_perbaikan_id', $order->id)
            ->first();

        $payload = [
            'ticket_id' => $ticket->id,
            'order_perbaikan_id' => $order->id,
            'kategori_name' => $kategoriName,
            'sync_enabled' => true,
            'created_by' => $actor?->id ?? auth()->id(),
        ];

        if ($link) {
            $link->update($payload);

            return $link;
        }

        return PengajuanLink::create($payload);
    }

    public function linkedOrder(Ticket $ticket): ?OrderPerbaikan
    {
        return PengajuanLink::where('ticket_id', $ticket->id)->first()?->orderPerbaikan;
    }

    public function linkedTicket(OrderPerbaikan $order): ?Ticket
    {
        return PengajuanLink::where('order_perbaikan_id', $order->id)->first()?->ticket;
    }

    // =====================================================================
    // Pembuatan pasangan
    // =====================================================================

    /**
     * Dari sebuah tiket target → buat Order Perbaikan + tautkan.
     */
    public function createCounterpartForTicket(User $actor, Ticket $ticket): ?OrderPerbaikan
    {
        if (self::$creating) {
            return null;
        }
        if (! $this->isTarget($ticket->category_id, $ticket->category)) {
            return null;
        }
        if ($this->linkedOrder($ticket)) {
            return null;
        }

        $kategoriName = $ticket->category;
        if (! $this->kategoriOrderExists($kategoriName)) {
            return null;
        }

        $validated = [
            'tanggal' => now()->format('Y-m-d H:i:s'),
            'department_id' => $ticket->department_id,
            'kategori_order' => $kategoriName,
            'lokasi' => $ticket->location_id,
            'gedung' => $ticket->building,
            'keluhan' => $ticket->description,
            'prioritas' => $this->mapPriorityTicketToOrder($ticket->priority),
        ];

        self::$creating = true;
        try {
            $order = app(OrderPerbaikanService::class)->create($actor, $validated, null, 'Sinkron Tiket');
        } catch (\Throwable $e) {
            Log::warning('Sinkron pengajuan: gagal buat order dari tiket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        } finally {
            self::$creating = false;
        }

        // Salin foto tiket (initial) ke order agar konteks visual sama.
        $paths = $ticket->photos()->where('type', 'initial')->pluck('photo_path')->all();
        if (! empty($paths)) {
            $order->update(['foto' => array_values($paths)]);
        }

        $this->link($ticket, $order, $kategoriName, $actor);

        return $order;
    }

    /**
     * Dari sebuah order target → buat Tiket IT + tautkan.
     */
    public function createCounterpartForOrder(User $actor, OrderPerbaikan $order): ?Ticket
    {
        if (self::$creating) {
            return null;
        }
        if (! $this->isTarget(null, $order->kategori_order)) {
            return null;
        }
        if ($this->linkedTicket($order)) {
            return null;
        }

        $categoryId = $this->categoryIdByName((string) $order->kategori_order);
        if (! $categoryId) {
            return null;
        }

        $data = [
            'description' => $order->keluhan ?: ('Pengajuan terkait order '.$order->nomor),
            'category_id' => $categoryId,
            'priority' => $this->mapPriorityOrderToTicket($order->prioritas),
            'department_id' => $order->department_id,
            'location_id' => $order->lokasi,
        ];

        self::$creating = true;
        try {
            $ticket = app(TicketService::class)->create($actor, $data, null);
        } catch (\Throwable $e) {
            Log::warning('Sinkron pengajuan: gagal buat tiket dari order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        } finally {
            self::$creating = false;
        }

        // Salin foto order ke tiket agar konteks visual sama.
        foreach ($order->getFotoPathsAttribute() as $path) {
            TicketPhoto::create([
                'ticket_id' => $ticket->id,
                'photo_path' => $path,
                'type' => 'initial',
            ]);
        }

        $this->link($ticket, $order, (string) $order->kategori_order, $actor);

        return $ticket;
    }

    // =====================================================================
    // Sinkron dua arah (guard anti-loop: tulis langsung ke model, tidak
    // lewat service yang memicu sync lagi)
    // =====================================================================

    public function syncFromTicket(Ticket $ticket, ?User $actor = null, string $source = 'web'): void
    {
        if (self::$syncing) {
            return;
        }

        $link = PengajuanLink::where('ticket_id', $ticket->id)->where('sync_enabled', true)->first();
        $order = $link?->orderPerbaikan;
        if (! $order) {
            return;
        }

        $target = $this->mapTicketToOrder($ticket->status);
        if (! $target || $order->status === $target) {
            return;
        }

        self::$syncing = true;
        try {
            $this->applyOrderStatus(
                $order,
                $target,
                $actor,
                "Disinkronkan dari Tiket #{$ticket->ticket_number} (status: {$ticket->status}) [{$source}]"
            );
        } finally {
            self::$syncing = false;
        }
    }

    public function syncFromOrder(OrderPerbaikan $order, ?User $actor = null, string $source = 'web'): void
    {
        if (self::$syncing) {
            return;
        }

        $link = PengajuanLink::where('order_perbaikan_id', $order->id)->where('sync_enabled', true)->first();
        $ticket = $link?->ticket;
        if (! $ticket) {
            return;
        }

        $target = $this->mapOrderToTicket($order->status);
        if (! $target || $ticket->status === $target) {
            return;
        }

        self::$syncing = true;
        try {
            $this->applyTicketStatus(
                $ticket,
                $target,
                $actor,
                "Disinkronkan dari Order {$order->nomor} (status: {$order->status}) [{$source}]"
            );
        } finally {
            self::$syncing = false;
        }
    }

    protected function mapTicketToOrder(string $status): ?string
    {
        return match ($status) {
            'open' => OrderPerbaikan::STATUS_OPEN,
            'in_progress' => OrderPerbaikan::STATUS_IN_PROGRESS,
            'closed', 'pending' => OrderPerbaikan::STATUS_TUTUP,
            'confirmed' => OrderPerbaikan::STATUS_CONFIRMED,
            default => null,
        };
    }

    protected function mapOrderToTicket(string $status): ?string
    {
        return match ($status) {
            OrderPerbaikan::STATUS_OPEN => 'open',
            OrderPerbaikan::STATUS_IN_PROGRESS => 'in_progress',
            OrderPerbaikan::STATUS_TUTUP => 'closed',
            OrderPerbaikan::STATUS_CONFIRMED => 'confirmed',
            OrderPerbaikan::STATUS_REJECTED => 'in_progress',
            default => null,
        };
    }

    protected function mapPriorityTicketToOrder(?string $priority): string
    {
        return match ($priority) {
            'high' => 'TINGGI/URGENT',
            'low' => 'RENDAH',
            default => 'SEDANG',
        };
    }

    protected function mapPriorityOrderToTicket(?string $prioritas): string
    {
        return match ($prioritas) {
            'TINGGI/URGENT' => 'high',
            'RENDAH' => 'low',
            default => 'medium',
        };
    }

    protected function applyOrderStatus(OrderPerbaikan $order, string $status, ?User $actor, string $note): void
    {
        DB::transaction(function () use ($order, $status, $actor, $note) {
            $data = [
                'status' => $status,
                'follow_up' => $note,
            ];
            if ($actor) {
                $data['updated_by'] = $actor->id;
            }
            if ($status === OrderPerbaikan::STATUS_TUTUP) {
                $data['closed_at'] = now();
            }

            $order->update($data);

            $order->history()->create([
                'status' => $status,
                'follow_up' => $note,
                'keterangan' => $note,
                'created_by' => $actor?->id,
            ]);
        });

        try {
            $fresh = $order->fresh();
            $targets = $fresh->penanggungJawab;
            if ($targets->isEmpty()) {
                $targets = User::adminUmum()->get();
            }
            foreach ($targets as $target) {
                $target->notify(new OrderPerbaikanStatusUpdated($fresh));
            }
        } catch (\Throwable $e) {
            Log::warning('Sinkron pengajuan: gagal notif order', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    protected function applyTicketStatus(Ticket $ticket, string $status, ?User $actor, string $note): void
    {
        DB::transaction(function () use ($ticket, $status, $actor, $note) {
            $responses = $ticket->admin_responses;
            if (is_string($responses)) {
                $responses = json_decode($responses, true);
            }
            if (! is_array($responses)) {
                $responses = [];
            }

            $responses[] = [
                'notes' => $note,
                'timestamp' => now()->toDateTimeString(),
                'status' => $status,
                'admin_id' => $actor?->id,
                'admin_name' => $actor?->name ?? 'Sistem (Sinkron)',
                'sync' => true,
            ];

            $data = [
                'admin_responses' => json_encode($responses),
                'status' => $status,
            ];
            if ($status === 'in_progress') {
                $data['in_progress_at'] = now();
            }
            if ($status === 'closed') {
                $data['closed_at'] = now();
            }
            if ($status === 'confirmed') {
                $data['user_confirmation'] = true;
                $data['user_confirmed_at'] = now();
            }

            $ticket->update($data);
        });

        try {
            $fresh = $ticket->fresh();
            $admins = User::adminIT()->get();
            foreach ($admins as $admin) {
                $admin->notify(new TicketRespondedNotification($fresh, $actor ?? $admin, $note, true, 'updated'));
            }
            if ($actor) {
                Notify::ticketToUser($fresh, 'ticket_updated', $actor, $note);
            }
        } catch (\Throwable $e) {
            Log::warning('Sinkron pengajuan: gagal notif tiket', ['ticket_id' => $ticket->id, 'error' => $e->getMessage()]);
        }
    }

    protected function normalize(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }
}
