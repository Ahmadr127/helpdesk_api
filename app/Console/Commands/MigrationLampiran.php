<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\MapsLegacyPengajuan;
use App\Models\Pengajuan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrationLampiran extends Command
{
    use MapsLegacyPengajuan;

    protected $signature = 'migration:lampiran {--dry-run}';

    protected $description = 'Migrate ticket_photos and order_photos into pengajuan_lampiran';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $ticketPhotos = DB::table('ticket_photos')->orderBy('created_at')->orderBy('id')->get();
        $orderPhotos = DB::table('order_photos')->orderBy('created_at')->orderBy('id')->get();
        $ticketPhotosNull = DB::table('tickets')->whereNotNull('photo')->orderBy('created_at')->orderBy('id')->get();
        $orderFoto = DB::table('order_perbaikan')->whereNotNull('foto')->orderBy('created_at')->orderBy('id')->get();

        $this->info('Legacy: '.count($ticketPhotos).' ticket_photos, '.count($orderPhotos).' order_photos, '.count($ticketPhotosNull).' tickets.photo, '.count($orderFoto).' order_perbaikan.foto.');

        if ($dry) {
            $this->warn('DRY-RUN');

            $would = 0;
            $would += $this->countPending($ticketPhotos, 'ticket', 'ticket_id', 'photo_path');
            $would += $this->countPending($orderPhotos, 'order', 'order_id', 'path');
            $would += $this->countPending($ticketPhotosNull, 'ticket', 'id', 'photo');
            $would += $this->countPending($orderFoto, 'order', 'id', 'foto');
            $this->line("would migrate: {$would} lampiran.");

            return self::SUCCESS;
        }

        $migrated = 0;
        $skippedNoPengajuan = 0;
        $skippedExisting = 0;

        DB::transaction(function () use ($ticketPhotos, $orderPhotos, $ticketPhotosNull, $orderFoto, &$migrated, &$skippedNoPengajuan, &$skippedExisting): void {
            foreach ($ticketPhotos as $ph) {
                $pengajuan = Pengajuan::where('legacy_source', 'ticket')->where('legacy_id', $ph->ticket_id)->first();
                if (! $pengajuan) {
                    $skippedNoPengajuan++;
                    continue;
                }

                if ($this->lampiranExists($pengajuan->id, (string) $ph->photo_path)) {
                    $skippedExisting++;
                    continue;
                }

                $kind = in_array($ph->type, ['initial', 'admin_response', 'user_response', 'user_rejection', 'history', 'support'], true)
                    ? $ph->type
                    : 'initial';

                DB::table('pengajuan_lampiran')->insert([
                    'pengajuan_id' => $pengajuan->id,
                    'histori_id' => null,
                    'pesan_id' => null,
                    'kind' => $kind,
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

            foreach ($orderPhotos as $ph) {
                $pengajuan = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $ph->order_id)->first();
                if (! $pengajuan) {
                    $skippedNoPengajuan++;
                    continue;
                }

                if ($this->lampiranExists($pengajuan->id, (string) $ph->path)) {
                    $skippedExisting++;
                    continue;
                }

                DB::table('pengajuan_lampiran')->insert([
                    'pengajuan_id' => $pengajuan->id,
                    'histori_id' => null,
                    'pesan_id' => null,
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

            foreach ($ticketPhotosNull as $t) {
                $pengajuan = Pengajuan::where('legacy_source', 'ticket')->where('legacy_id', $t->id)->first();
                if (! $pengajuan) {
                    $skippedNoPengajuan++;
                    continue;
                }
                if ($this->lampiranExists($pengajuan->id, (string) $t->photo)) {
                    $skippedExisting++;
                    continue;
                }

                DB::table('pengajuan_lampiran')->insert([
                    'pengajuan_id' => $pengajuan->id,
                    'histori_id' => null,
                    'pesan_id' => null,
                    'kind' => 'initial',
                    'path' => $t->photo,
                    'original_name' => basename((string) $t->photo),
                    'mime' => null,
                    'size' => null,
                    'uploaded_by' => $pengajuan->pemohon_id,
                    'created_at' => $t->created_at ?? now(),
                    'updated_at' => $t->updated_at ?? now(),
                ]);
                $migrated++;
            }

            foreach ($orderFoto as $o) {
                $pengajuan = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $o->id)->first();
                if (! $pengajuan) {
                    $skippedNoPengajuan++;
                    continue;
                }
                if ($this->lampiranExists($pengajuan->id, (string) $o->foto)) {
                    $skippedExisting++;
                    continue;
                }

                DB::table('pengajuan_lampiran')->insert([
                    'pengajuan_id' => $pengajuan->id,
                    'histori_id' => null,
                    'pesan_id' => null,
                    'kind' => 'initial',
                    'path' => $o->foto,
                    'original_name' => basename((string) $o->foto),
                    'mime' => null,
                    'size' => null,
                    'uploaded_by' => $pengajuan->pemohon_id,
                    'created_at' => $o->created_at ?? now(),
                    'updated_at' => $o->updated_at ?? now(),
                ]);
                $migrated++;
            }
        });

        $this->info("Migrated: {$migrated} lampiran; skipped (no pengajuan): {$skippedNoPengajuan}; skipped (already migrated): {$skippedExisting}.");

        return self::SUCCESS;
    }

    protected function countPending(iterable $rows, string $source, string $fk, string $pathCol): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $pengajuan = Pengajuan::where('legacy_source', $source)->where('legacy_id', $row->$fk ?? $row->id ?? null)->first();
            if (! $pengajuan) {
                continue;
            }
            if ($this->lampiranExists($pengajuan->id, (string) ($row->$pathCol ?? ''))) {
                continue;
            }
            $count++;
        }

        return $count;
    }

    protected function lampiranExists(int $pengajuanId, string $path): bool
    {
        return DB::table('pengajuan_lampiran')
            ->where('pengajuan_id', $pengajuanId)
            ->where('path', $path)
            ->exists();
    }
}
