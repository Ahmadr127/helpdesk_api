<?php

namespace App\Console\Commands;

use App\Models\Pengajuan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrationHistori extends Command
{
    protected $signature = 'migration:histori {--dry-run}';

    protected $description = 'Migrate order_perbaikan_histories into pengajuan_histori';

    public function handle(): int
    {
        $rows = DB::table('order_perbaikan_histories')->get();
        $this->info("Legacy order histories: {$rows->count()}");

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN');

            return self::SUCCESS;
        }

        $migrated = 0;
        DB::transaction(function () use ($rows, &$migrated): void {
            foreach ($rows as $h) {
                $pengajuan = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $h->order_perbaikan_id ?? $h->order_id ?? null)->first();
                if (! $pengajuan) {
                    continue;
                }

                $histori = $pengajuan->historis()->create([
                    'status' => $this->mapStatus($h->status ?? $pengajuan->status),
                    'action' => $h->action ?? 'migrated_history',
                    'notes' => $h->notes ?? $h->catatan ?? null,
                    'actor_id' => $h->user_id ?? $h->created_by ?? null,
                    'actor_type' => 'user',
                ]);
                // created_at tidak ada di $fillable, jadi diset manual agar tidak jadi waktu migrasi.
                $histori->created_at = $h->created_at ?? now();
                $histori->updated_at = $h->updated_at ?? $h->created_at ?? now();
                $histori->saveQuietly();

                $migrated++;
            }
        });

        $this->info("Migrated: {$migrated} histories.");

        return self::SUCCESS;
    }

    protected function mapStatus(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'tutup', 'closed' => 'pending_confirmation',
            'confirmed', 'completed', 'done' => 'confirmed',
            'rejected' => 'rejected',
            'in_progress', 'process' => 'in_progress',
            default => 'open',
        };
    }
}