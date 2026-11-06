<?php

namespace App\Console\Commands;

use App\Models\Pengajuan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrationLampiran extends Command
{
    protected $signature = 'migration:lampiran {--dry-run}';

    protected $description = 'Migrate ticket_photos and order_photos into pengajuan_lampiran';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $tp = DB::table('ticket_photos')->count();
        $op = DB::table('order_photos')->count();
        $this->info("Legacy: {$tp} ticket_photos, {$op} order_photos.");

        if ($dry) {
            $this->warn('DRY-RUN');

            return self::SUCCESS;
        }

        $migrated = 0;
        DB::transaction(function () use (&$migrated): void {
            foreach (DB::table('ticket_photos')->get() as $ph) {
                $pengajuan = Pengajuan::where('legacy_source', 'ticket')->where('legacy_id', $ph->ticket_id)->first();
                if (! $pengajuan) {
                    continue;
                }
                DB::table('pengajuan_lampiran')->insert([
                    'pengajuan_id' => $pengajuan->id,
                    'kind' => $ph->type ?? 'initial',
                    'path' => $ph->photo_path,
                    'original_name' => basename((string) $ph->photo_path),
                    'mime' => null,
                    'size' => null,
                    'uploaded_by' => $pengajuan->pemohon_id,
                    'created_at' => $ph->created_at ?? now(),
                    'updated_at' => $ph->updated_at ?? now(),
                ]);
                $migrated++;
            }

            foreach (DB::table('order_photos')->get() as $ph) {
                $pengajuan = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $ph->order_id)->first();
                if (! $pengajuan) {
                    continue;
                }
                DB::table('pengajuan_lampiran')->insert([
                    'pengajuan_id' => $pengajuan->id,
                    'kind' => 'initial',
                    'path' => $ph->path,
                    'original_name' => $ph->original_name ?? basename((string) $ph->path),
                    'mime' => $ph->mime_type ?? null,
                    'size' => $ph->size ?? null,
                    'uploaded_by' => $pengajuan->pemohon_id,
                    'created_at' => $ph->created_at ?? now(),
                    'updated_at' => $ph->updated_at ?? now(),
                ]);
                $migrated++;
            }
        });

        $this->info("Migrated: {$migrated} lampiran.");

        return self::SUCCESS;
    }
}