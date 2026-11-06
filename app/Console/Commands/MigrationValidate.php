<?php

namespace App\Console\Commands;

use App\Models\Pengajuan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrationValidate extends Command
{
    protected $signature = 'migration:validate';

    protected $description = 'Validate unified pengajuan migration counts';

    public function handle(): int
    {
        $tickets = DB::table('tickets')->count();
        $orders = DB::table('order_perbaikan')->count();
        $migratedTickets = Pengajuan::where('legacy_source', 'ticket')->count();
        $migratedOrders = Pengajuan::where('legacy_source', 'order')->count();

        $this->table(['Metric', 'Legacy', 'Migrated'], [
            ['Tickets', $tickets, $migratedTickets],
            ['Orders', $orders, $migratedOrders],
        ]);

        $ok = ($tickets === $migratedTickets) && ($orders === $migratedOrders);
        $this->info($ok ? 'VALIDATION PASSED' : 'VALIDATION FAILED');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}