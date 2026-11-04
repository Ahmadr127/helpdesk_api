<?php

namespace App\Console\Commands;

use App\Models\OrderPerbaikan;
use App\Models\Ticket;
use App\Services\Api\OrderPerbaikanService;
use App\Services\Api\TicketService;
use Illuminate\Console\Command;

class AutoConfirmStaleSubmissions extends Command
{
    protected $signature = 'pengajuan:auto-confirm {--hours=24 : Jam threshold sebelum auto-confirm} {--dry-run : Tampilkan tanpa mengubah data}';

    protected $description = 'Auto-confirm pengajuan (order & tiket) yang sudah menunggu konfirmasi user lebih dari N jam';

    public function handle(OrderPerbaikanService $orderService, TicketService $ticketService): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = (bool) $this->option('dry-run');

        $orderCount = OrderPerbaikan::where('status', OrderPerbaikan::STATUS_TUTUP)
            ->where('closed_at', '<=', now()->subHours($hours))
            ->count();
        $ticketCount = Ticket::where('status', 'pending')
            ->where('user_confirmation', false)
            ->where('closed_at', '<=', now()->subHours($hours))
            ->count();

        if ($dryRun) {
            $this->info("Dry-run: order={$orderCount}, ticket={$ticketCount} (threshold {$hours} jam).");
            return self::SUCCESS;
        }

        $orders = $orderService->autoConfirmStale($hours);
        $tickets = $ticketService->autoConfirmStale($hours);

        $this->info("Auto-confirm selesai: order={$orders}, ticket={$tickets}.");

        return self::SUCCESS;
    }
}
