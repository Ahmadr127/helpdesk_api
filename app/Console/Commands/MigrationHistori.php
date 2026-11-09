<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\MapsLegacyPengajuan;
use App\Models\Pengajuan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrationHistori extends Command
{
    use MapsLegacyPengajuan;
    protected $signature = 'migration:histori {--dry-run}';

    protected $description = 'Migrate order_perbaikan_histories into pengajuan_histori';

    public function handle(): int
    {
        $rows = DB::table('order_perbaikan_histories')->orderBy('created_at')->orderBy('id')->get();
        $this->info("Legacy order histories: {$rows->count()}");

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN');

            $dryMigrated = 0; $drySkippedNoPengajuan = 0; $drySkippedExisting = 0;
            foreach ($rows as $h) {
                $pengajuan = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $h->order_perbaikan_id ?? $h->order_id ?? null)->first();
                if (! $pengajuan) {
                    $drySkippedNoPengajuan++;
                    continue;
                }
                $notes = $h->keterangan ?? $h->notes ?? $h->catatan ?? null;
                if ($this->historiExists($pengajuan->id, $h->action ?? 'migrated_history', $notes, $h->created_at ?? now())) {
                    $drySkippedExisting++;
                    continue;
                }
                $dryMigrated++;
            }
            $this->line("would migrate: {$dryMigrated}; skipped (no pengajuan): {$drySkippedNoPengajuan}; skipped (already migrated): {$drySkippedExisting}.");

            return self::SUCCESS;
        }

        $migrated = 0; $skippedNoPengajuan = 0; $skippedExisting = 0;
        DB::transaction(function () use ($rows, &$migrated, &$skippedNoPengajuan, &$skippedExisting): void {
            foreach ($rows as $h) {
                $pengajuan = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $h->order_perbaikan_id ?? $h->order_id ?? null)->first();
                if (! $pengajuan) {
                    $skippedNoPengajuan++;
                    continue;
                }

                $notes = $h->keterangan ?? $h->notes ?? $h->catatan ?? null;
                $timestamp = $h->created_at ?? now();

                if ($this->historiExists($pengajuan->id, $h->action ?? 'migrated_history', $notes, $timestamp)) {
                    $skippedExisting++;
                    continue;
                }

                $histori = $pengajuan->historis()->create([
                    'status' => $this->mapStatus($h->status ?? $pengajuan->status),
                    'action' => $h->action ?? 'migrated_history',
                    'notes' => $notes,
                    'actor_id' => $h->user_id ?? $h->created_by ?? null,
                    'actor_type' => 'user',
                ]);

                $histori->created_at = $timestamp;
                $histori->updated_at = $h->updated_at ?? $timestamp;
                $histori->saveQuietly();

                if (! empty($h->lampiran)) {
                    $alreadyLampiran = DB::table('pengajuan_lampiran')
                        ->where('pengajuan_id', $pengajuan->id)
                        ->where('histori_id', $histori->id)
                        ->where('path', $h->lampiran)
                        ->exists();

                    if (! $alreadyLampiran) {
                        DB::table('pengajuan_lampiran')->insert([
                            'pengajuan_id' => $pengajuan->id,
                            'histori_id' => $histori->id,
                            'pesan_id' => null,
                            'kind' => 'history',
                            'path' => $h->lampiran,
                            'original_name' => basename((string) $h->lampiran),
                            'mime' => null,
                            'size' => null,
                            'uploaded_by' => $h->user_id ?? $h->created_by ?? $pengajuan->pemohon_id,
                            'created_at' => $timestamp,
                            'updated_at' => $h->updated_at ?? $timestamp,
                        ]);
                    }
                }

                $migrated++;
            }
        });

        $this->info("Migrated: {$migrated} histories; skipped (no pengajuan): {$skippedNoPengajuan}; skipped (already migrated): {$skippedExisting}.");

        return self::SUCCESS;
    }
}