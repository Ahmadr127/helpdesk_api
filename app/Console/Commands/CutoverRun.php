<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CutoverRun extends Command
{
    protected $signature = 'cutover:run {--dry-run}';

    protected $description = 'Run full unified pengajuan migration sequence locally';

    public function handle(): int
    {
        $dry = $this->option('dry-run') ? ' --dry-run' : '';

        $steps = [
            'migration:preflight',
            'migration:categories'.$dry,
            'migration:tickets'.$dry,
            'migration:orders'.$dry,
            'migration:histori'.$dry,
            'migration:lampiran'.$dry,
            'migration:penugasan'.$dry,
            'migration:validate',
        ];

        foreach ($steps as $step) {
            $this->info(">>> {$step}");
            $code = $this->call(trim($step));
            if ($code !== 0) {
                $this->error("Step failed: {$step}");

                return self::FAILURE;
            }
        }

        $this->info('Cutover migration sequence completed.');

        return self::SUCCESS;
    }
}