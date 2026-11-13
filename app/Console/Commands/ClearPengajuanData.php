<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ClearPengajuanData extends Command
{
    protected $signature = 'pengajuan:clear
        {--force : Skip confirmation prompt}
        {--dry-run : Show counts without deleting anything}
        {--keep-files : Do not delete uploaded files in storage}';

    protected $description = 'Clear all data pengajuan (tickets, order perbaikan, orders, feedback, reports, documents)';

    /**
     * Tables to clear, ordered child-first to respect foreign keys.
     * Legacy tables are listed first; tables dropped by migrations are
     * skipped automatically (Schema::hasTable check in handle()).
     */
    protected array $tables = [
        'pengajuan_penugasan',
        'pengajuan_pesan',
        'pengajuan_lampiran',
        'pengajuan_histori',
        'pengajuan',
        'ticket_photos',
        'tickets',
        'order_photos',
        'order_perbaikan_histories',
        'order_perbaikan',
        'order_barang',
        'orders',
        'feedback',
        'reports',
        'formulir',
        'dokumen',
        'prosedur',
    ];

    protected array $storageDirs = [
        'ticket-photos',
        'ticket-replies',
        'ticket-responses',
        'ticket-rejections',
        'order-photos',
        'order-lampiran',
        'reports',
    ];

    public function handle(): int
    {
        $this->info('=== Clear Data Pengajuan ===');
        $this->newLine();

        // Filter to tables that actually exist
        $existing = [];
        foreach ($this->tables as $t) {
            if (Schema::hasTable($t)) {
                $existing[] = $t;
            }
        }

        // Show counts
        $rows = [];
        $total = 0;
        foreach ($existing as $t) {
            $c = DB::table($t)->count();
            $rows[] = [$t, $c];
            $total += $c;
        }
        $this->table(['Table', 'Rows'], $rows);
        $this->line("Total rows: {$total}");
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN: Tidak ada data yang dihapus.');

            return self::SUCCESS;
        }

        if ($total === 0 && ! $this->option('force')) {
            $this->info('Tidak ada data untuk dihapus.');
        }

        if (! $this->option('force')) {
            if (! $this->confirm('Hapus semua data pengajuan di atas? Tindakan ini tidak dapat dibatalkan.', false)) {
                $this->info('Dibatalkan.');

                return self::SUCCESS;
            }
        }

        // Delete DB rows in child-first order
        $this->info('Menghapus data dari database...');
        DB::beginTransaction();
        try {
            foreach ($existing as $t) {
                $deleted = DB::table($t)->delete();
                $this->line("  - {$t}: {$deleted} rows deleted");
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal menghapus data: '.$e->getMessage());

            return self::FAILURE;
        }

        // Clean uploaded files
        if (! $this->option('keep-files')) {
            $this->info('Membersihkan file upload...');
            foreach ($this->storageDirs as $dir) {
                $path = storage_path('app/public/'.$dir);
                if (! is_dir($path)) {
                    continue;
                }
                try {
                    foreach (File::files($path) as $file) {
                        File::delete($file);
                    }
                    // remove subdirectories too
                    foreach (File::directories($path) as $sub) {
                        File::deleteDirectory($sub);
                    }
                    $this->line("  - {$dir}: cleaned");
                } catch (\Throwable $e) {
                    $this->warn("  - {$dir}: ".$e->getMessage());
                }
            }
        } else {
            $this->line('File upload dilewati (--keep-files).');
        }

        $this->newLine();
        $this->info('Selesai. Data pengajuan telah dibersihkan.');

        return self::SUCCESS;
    }
}