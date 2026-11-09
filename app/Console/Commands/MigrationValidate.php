<?php

namespace App\Console\Commands;

use App\Models\Pengajuan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrationValidate extends Command
{
    protected $signature = 'migration:validate {--dry-run}';

    protected $description = 'Validate unified pengajuan migration counts';

    public function handle(): int
    {
        $tickets = DB::table('tickets')->count();
        $orders = DB::table('order_perbaikan')->count();
        $migratedTickets = Pengajuan::where('legacy_source', 'ticket')->count();
        $migratedOrders = Pengajuan::where('legacy_source', 'order')->count();

        $rows = [
            ['Tickets', $tickets, $migratedTickets],
            ['Orders', $orders, $migratedOrders],
        ];

        $ok = ($tickets === $migratedTickets) && ($orders === $migratedOrders);

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN: validate (tidak menulis).');
            $this->table(['Metric', 'Legacy', 'Migrated'], $rows);
            $this->info($ok ? 'VALIDATION PASSED' : 'VALIDATION FAILED');

            return $ok ? self::SUCCESS : self::FAILURE;
        }

        // Counts
        $this->table(['Metric', 'Legacy', 'Migrated'], $rows);

        // Lampiran
        $lampiranRows = $this->lampiranReport();
        if ($lampiranRows !== []) {
            $this->newLine();
            $this->info('Lampiran:');
            $this->table(['Source', 'Legacy', 'Migrated', 'OK'], $lampiranRows);
            foreach ($lampiranRows as $row) {
                if (! $row[3]) {
                    $ok = false;
                }
            }
        }

        // Histori order
        if (Schema::hasTable('order_perbaikan_histories')) {
            $legacyHistori = DB::table('order_perbaikan_histories')->count();
            $migratedHistori = DB::table('pengajuan_histori')
                ->join('pengajuan', 'pengajuan.id', '=', 'pengajuan_histori.pengajuan_id')
                ->where('pengajuan.legacy_source', 'order')
                ->where('pengajuan_histori.action', 'migrated_history')
                ->count();
            $this->newLine();
            $this->info('Histori order:');
            $this->table(['Metric', 'Legacy', 'Migrated', 'OK'], [
                ['order_perbaikan_histories', $legacyHistori, $migratedHistori, $legacyHistori === $migratedHistori ? 'OK' : 'MISSING'],
            ]);
            if ($legacyHistori !== $migratedHistori) {
                $ok = false;
            }
        }

        // Pesan (informational, tidak fail)
        if (Schema::hasTable('pengajuan_pesan')) {
            $pesanCount = DB::table('pengajuan_pesan')->count();
            $this->newLine();
            $this->info("Pesan migrasi: {$pesanCount} (informational).");
        }

        // Penugasan
        if (Schema::hasTable('order_perbaikan') && Schema::hasTable('pengajuan_penugasan')) {
            $ordersWithPJ = DB::table('order_perbaikan')
                ->whereNotNull('nama_penanggung_jawab')
                ->where('nama_penanggung_jawab', '!=', '')
                ->count();
            $penugasanCount = DB::table('pengajuan_penugasan')->count();
            $this->newLine();
            $this->info("Penugasan: orders with PJ name={$ordersWithPJ}, penugasan rows={$penugasanCount} (orphans possible if name tidak match user).");
        }

        $this->newLine();
        $this->info($ok ? 'VALIDATION PASSED' : 'VALIDATION FAILED');

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    protected function lampiranReport(): array
    {
        $rows = [];

        if (Schema::hasTable('ticket_photos')) {
            $legacy = DB::table('ticket_photos')->count();
            $migrated = DB::table('pengajuan_lampiran')
                ->join('pengajuan', 'pengajuan.id', '=', 'pengajuan_lampiran.pengajuan_id')
                ->where('pengajuan.legacy_source', 'ticket')
                ->count();
            // Accurate per-path check
            $missing = 0;
            foreach (DB::table('ticket_photos')->get() as $ph) {
                $pengajuan = Pengajuan::where('legacy_source', 'ticket')->where('legacy_id', $ph->ticket_id)->first();
                if (! $pengajuan) {
                    $missing++;
                    continue;
                }
                $exists = DB::table('pengajuan_lampiran')->where('pengajuan_id', $pengajuan->id)->where('path', $ph->photo_path)->exists();
                if (! $exists) {
                    $missing++;
                }
            }
            $rows[] = ['ticket_photos', $legacy, $legacy - $missing, $missing === 0 ? 'OK' : 'MISSING '.$missing];
        }

        if (Schema::hasTable('order_photos')) {
            $legacy = DB::table('order_photos')->count();
            $missing = 0;
            foreach (DB::table('order_photos')->get() as $ph) {
                $pengajuan = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $ph->order_id)->first();
                if (! $pengajuan) {
                    $missing++;
                    continue;
                }
                $exists = DB::table('pengajuan_lampiran')->where('pengajuan_id', $pengajuan->id)->where('path', $ph->path)->exists();
                if (! $exists) {
                    $missing++;
                }
            }
            $rows[] = ['order_photos', $legacy, $legacy - $missing, $missing === 0 ? 'OK' : 'MISSING '.$missing];
        }

        if (Schema::hasTable('order_perbaikan')) {
            $legacy = DB::table('order_perbaikan')->whereNotNull('foto')->count();
            $missing = 0;
            foreach (DB::table('order_perbaikan')->whereNotNull('foto')->get() as $o) {
                $pengajuan = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $o->id)->first();
                if (! $pengajuan) {
                    $missing++;
                    continue;
                }
                $exists = DB::table('pengajuan_lampiran')->where('pengajuan_id', $pengajuan->id)->where('path', $o->foto)->exists();
                if (! $exists) {
                    $missing++;
                }
            }
            $rows[] = ['order_perbaikan.foto', $legacy, $legacy - $missing, $missing === 0 ? 'OK' : 'MISSING '.$missing];
        }

        if (Schema::hasTable('tickets')) {
            $legacy = DB::table('tickets')->whereNotNull('photo')->count();
            $missing = 0;
            foreach (DB::table('tickets')->whereNotNull('photo')->get() as $t) {
                $pengajuan = Pengajuan::where('legacy_source', 'ticket')->where('legacy_id', $t->id)->first();
                if (! $pengajuan) {
                    $missing++;
                    continue;
                }
                $exists = DB::table('pengajuan_lampiran')->where('pengajuan_id', $pengajuan->id)->where('path', $t->photo)->exists();
                if (! $exists) {
                    $missing++;
                }
            }
            $rows[] = ['tickets.photo', $legacy, $legacy - $missing, $missing === 0 ? 'OK' : 'MISSING '.$missing];
        }

        return $rows;
    }
}
