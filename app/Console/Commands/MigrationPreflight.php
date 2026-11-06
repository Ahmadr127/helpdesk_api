<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrationPreflight extends Command
{
    protected $signature = 'migration:preflight {--json : Output as JSON}';

    protected $description = 'Preflight report for unified pengajuan migration';

    public function handle(): int
    {
        $report = [];

        $tables = ['tickets', 'order_perbaikan', 'categories', 'kategori_order', 'users', 'departments', 'locations', 'buildings'];
        foreach ($tables as $t) {
            $report['counts'][$t] = Schema::hasTable($t) ? DB::table($t)->count() : null;
        }

        // Orphan checks
        $orphans = [];
        if (Schema::hasTable('tickets')) {
            $orphans['tickets_without_user'] = DB::table('tickets')
                ->leftJoin('users', 'tickets.user_id', '=', 'users.id')
                ->whereNull('users.id')->count();
        }
        if (Schema::hasTable('order_perbaikan')) {
            $orphans['orders_without_user'] = DB::table('order_perbaikan')
                ->leftJoin('users', 'order_perbaikan.created_by', '=', 'users.id')
                ->whereNull('users.id')->count();
        }
        $report['orphans'] = $orphans;

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));
        } else {
            $this->info('=== Migration Preflight ===');
            $this->table(['Table', 'Rows'], collect($report['counts'])->map(fn ($v, $k) => [$k, $v])->values()->toArray());
            $this->newLine();
            $this->info('Orphans:');
            foreach ($report['orphans'] as $k => $v) {
                $this->line("  {$k}: {$v}");
            }
        }

        return self::SUCCESS;
    }
}