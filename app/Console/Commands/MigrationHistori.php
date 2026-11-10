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

            $dryMigrated = 0; $drySkippedNoPengajuan = 0; $drySkippedExisting = 0; $dryPesan = 0;
            foreach ($rows as $h) {
                $pengajuan = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $h->order_perbaikan_id ?? $h->order_id ?? null)->first();
                if (! $pengajuan) {
                    $drySkippedNoPengajuan++;
                    continue;
                }
                $notes = $h->keterangan ?? $h->notes ?? $h->catatan ?? null;
                $timestamp = $this->parseDate($h->created_at ?? null) ?? now();
                $action = $h->action ?? 'migrated_history';
                if ($this->historiExists($pengajuan->id, $action, $notes, $h->created_at ?? now())) {
                    $drySkippedExisting++;
                } else {
                    $dryMigrated++;
                }
                if ($this->shouldBackfillPesan($h, $pengajuan, $notes, $timestamp)) {
                    $dryPesan++;
                }
            }
            $this->line("would migrate: {$dryMigrated} histories; would backfill: {$dryPesan} pesan; skipped (no pengajuan): {$drySkippedNoPengajuan}; skipped (already migrated): {$drySkippedExisting}.");

            return self::SUCCESS;
        }

        $migrated = 0; $skippedNoPengajuan = 0; $skippedExisting = 0; $pesanBackfilled = 0;
        DB::transaction(function () use ($rows, &$migrated, &$skippedNoPengajuan, &$skippedExisting, &$pesanBackfilled): void {
            foreach ($rows as $h) {
                $pengajuan = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $h->order_perbaikan_id ?? $h->order_id ?? null)->first();
                if (! $pengajuan) {
                    $skippedNoPengajuan++;
                    continue;
                }

                $notes = $h->keterangan ?? $h->notes ?? $h->catatan ?? null;
                $timestamp = $this->parseDate($h->created_at ?? null) ?? now();
                $action = $h->action ?? 'migrated_history';

                $histori = null;

                if (! $this->historiExists($pengajuan->id, $action, $notes, $h->created_at ?? now())) {
                    $histori = $pengajuan->historis()->create([
                        'status' => $this->mapStatus($h->status ?? $pengajuan->status),
                        'action' => $action,
                        'notes' => $notes,
                        'actor_id' => $h->user_id ?? $h->created_by ?? null,
                        'actor_type' => 'user',
                    ]);

                    $histori->created_at = $timestamp;
                    $histori->updated_at = $this->parseDate($h->updated_at ?? null) ?? $timestamp;
                    $histori->saveQuietly();

                    $migrated++;
                } else {
                    $skippedExisting++;
                }

                if ($this->backfillPesan($h, $pengajuan, $notes, $timestamp)) {
                    $pesanBackfilled++;
                }

                if (! empty($h->lampiran)) {
                    $histori = $histori ?? $this->findHistori($pengajuan->id, $action, $notes, $timestamp);

                    $alreadyLampiran = DB::table('pengajuan_lampiran')
                        ->where('pengajuan_id', $pengajuan->id)
                        ->where('histori_id', $histori?->id)
                        ->where('path', $h->lampiran)
                        ->exists();

                    if (! $alreadyLampiran) {
                        DB::table('pengajuan_lampiran')->insert([
                            'pengajuan_id' => $pengajuan->id,
                            'histori_id' => $histori?->id,
                            'pesan_id' => null,
                            'kind' => 'history',
                            'path' => $h->lampiran,
                            'original_name' => basename((string) $h->lampiran),
                            'mime' => null,
                            'size' => null,
                            'uploaded_by' => $h->user_id ?? $h->created_by ?? $pengajuan->pemohon_id,
                            'created_at' => $timestamp,
                            'updated_at' => $this->parseDate($h->updated_at ?? null) ?? $timestamp,
                        ]);
                    }
                }
            }
        });

        $this->info("Migrated: {$migrated} histories; backfilled: {$pesanBackfilled} pesan; skipped (no pengajuan): {$skippedNoPengajuan}; skipped (already migrated): {$skippedExisting}.");

        return self::SUCCESS;
    }

    protected function findHistori(int $pengajuanId, string $action, $notes, $timestamp)
    {
        $notes = $notes !== null && trim((string) $notes) === '' ? null : $notes;

        $q = DB::table('pengajuan_histori')
            ->where('pengajuan_id', $pengajuanId)
            ->where('action', $action);

        if ($notes !== null) {
            $q->where('notes', $notes);
        } else {
            $q->where(function ($qq) {
                $qq->whereNull('notes')->orWhere('notes', '');
            });
        }

        if ($timestamp) {
            $q->where('created_at', $timestamp);
        }

        $row = $q->orderBy('id')->first();

        return $row ? (object) $row : null;
    }

    protected function shouldBackfillPesan($h, Pengajuan $pengajuan, $notes, $timestamp): bool
    {
        if ($notes === null || trim((string) $notes) === '') {
            return false;
        }

        $senderId = ($h->user_id ?? $h->created_by ?? null) ? (int) ($h->user_id ?? $h->created_by) : null;

        return ! $this->pesanExists($pengajuan->id, $senderId, $notes, $timestamp);
    }

    protected function backfillPesan($h, Pengajuan $pengajuan, $notes, $timestamp): bool
    {
        if (! $this->shouldBackfillPesan($h, $pengajuan, $notes, $timestamp)) {
            return false;
        }

        $senderId = ($h->user_id ?? $h->created_by ?? null) ? (int) ($h->user_id ?? $h->created_by) : null;
        $senderType = $senderId !== null && $senderId === (int) $pengajuan->pemohon_id ? 'user' : 'admin';

        $pesan = $pengajuan->pesans()->create([
            'sender_id' => $senderId,
            'sender_type' => $senderType,
            'body' => $notes,
        ]);

        $pesan->created_at = $timestamp;
        $pesan->updated_at = $timestamp;
        $pesan->saveQuietly();

        return true;
    }
}