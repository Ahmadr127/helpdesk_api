<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CutoverRun extends Command
{
    protected $signature = 'cutover:run {--dry-run}';

    protected $description = 'Run full unified pengajuan migration sequence locally';

    public function handle(): int
    {
        $dry = $this->option('dry-run');
        $dryOpts = $dry ? ['--dry-run' => true] : [];

        $steps = [
            ['migration:preflight', []],
            ['migration:categories', $dryOpts],
            ['migration:tickets', $dryOpts],
            ['migration:orders', $dryOpts],
            ['migration:histori', $dryOpts],
            ['migration:lampiran', $dryOpts],
            ['migration:penugasan', $dryOpts],
            ['migration:validate', $dryOpts],
        ];

        if ($dry) {
            $this->warn('DRY-RUN MODE: tidak ada data yang ditulis.');
        }

        $failed = false;

        foreach ($steps as [$command, $options]) {
            $label = $command.($options ? ' --dry-run' : '');
            $this->info(">>> {$label}");
            $code = $this->call($command, $options);
            if ($code !== 0) {
                $failed = true;
                if (! $dry) {
                    $this->error("Step failed: {$label}");

                    return self::FAILURE;
                }
                $this->warn("Step failed (dry-run, lanjut): {$label}");
            }
        }

        if ($failed) {
            $this->error('Cutover sequence selesai dengan kegagalan pada satu atau lebih langkah.');

            return self::FAILURE;
        }

        $this->info('Cutover migration sequence completed.');

        return self::SUCCESS;
    }
}
