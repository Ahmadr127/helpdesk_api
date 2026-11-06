<?php

namespace App\Console\Commands;

use App\Models\Pengajuan;
use App\Services\Pengajuan\PengajuanWorkflowService;
use Illuminate\Console\Command;

class AutoConfirmStalePengajuan extends Command
{
    protected $signature = 'pengajuan:auto-confirm {--hours=24 : Jam sejak closed_at}';

    protected $description = 'Auto-confirm pengajuan yang pending_confirmation lebih dari N jam';

    public function handle(PengajuanWorkflowService $workflow): int
    {
        $hours = (int) $this->option('hours');

        $this->info("Auto-confirm pengajuan pending_confirmation > {$hours} jam...");

        $confirmed = $workflow->autoConfirmStale($hours);

        $this->info("Selesai: {$confirmed} pengajuan di-auto-confirm.");

        return self::SUCCESS;
    }
}