<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\MapsLegacyPengajuan;
use App\Console\Commands\Concerns\MigratesTicketMessages;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\LegacyMap;
use App\Models\Pengajuan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrationTickets extends Command
{
    use MapsLegacyPengajuan, MigratesTicketMessages;

    protected $signature = 'migration:tickets {--dry-run}';

    protected $description = 'Migrate legacy tickets into pengajuan';

    public function handle(): int
    {
        $jenisIt = JenisPengajuan::where('code', 'IT')->first();
        $dry = (bool) $this->option('dry-run');

        $tickets = DB::table('tickets')->orderBy('created_at')->orderBy('id')->get();
        $this->info("Legacy tickets: {$tickets->count()}");

        $catMap = $this->buildCatMap($jenisIt);

        if ($dry) {
            $this->warn('DRY-RUN');

            $would = 0;
            $wouldRepair = 0;
            $wouldMessages = 0;
            $wouldHistori = 0;
            $wouldLampiran = 0;
            foreach ($tickets as $t) {
                $existing = Pengajuan::where('legacy_source', 'ticket')->where('legacy_id', $t->id)->first();
                if (! $existing) {
                    $would++;
                    $pendingPemohonId = $t->user_id && DB::table('users')->where('id', $t->user_id)->exists() ? (int) $t->user_id : $this->firstUserId();
                    $stats = $this->migrateTicketMessages($this->previewPengajuan($t, $catMap, $jenisIt, $pendingPemohonId), $t, $pendingPemohonId, true);
                    $wouldMessages += $stats['pesan'];
                    $wouldHistori += $stats['histori'];
                    $wouldLampiran += $stats['lampiran'];
                    continue;
                }
                $wouldRepair += $this->repairExisting($existing, $t, $catMap, $dry);
                $stats = $this->migrateTicketMessages($existing, $t, (int) $existing->pemohon_id, true);
                if ($stats['pesan'] + $stats['histori'] + $stats['lampiran'] > 0) {
                    $wouldMessages += $stats['pesan'];
                    $wouldHistori += $stats['histori'];
                    $wouldLampiran += $stats['lampiran'];
                }
            }
            $this->line("would migrate: {$would} tickets; would repair: {$wouldRepair} fields; would migrate: {$wouldMessages} pesan, {$wouldHistori} histori, {$wouldLampiran} lampiran.");

            return self::SUCCESS;
        }

        $migrated = 0;
        $repaired = 0;
        $skipped = 0;
        $statsPesan = 0;
        $statsHistori = 0;
        $statsLampiran = 0;

        DB::transaction(function () use ($tickets, $catMap, $jenisIt, &$migrated, &$repaired, &$skipped, &$statsPesan, &$statsHistori, &$statsLampiran): void {
            $firstUserId = $this->firstUserId();

            foreach ($tickets as $t) {
                $existing = Pengajuan::where('legacy_source', 'ticket')->where('legacy_id', $t->id)->first();
                if ($existing) {
                    $repaired += $this->repairExisting($existing, $t, $catMap, false);
                    $stats = $this->migrateTicketMessages($existing, $t, (int) $existing->pemohon_id);
                    $statsPesan += $stats['pesan'];
                    $statsHistori += $stats['histori'];
                    $statsLampiran += $stats['lampiran'];
                    $skipped++;
                    continue;
                }

                $pemohonId = $t->user_id && DB::table('users')->where('id', $t->user_id)->exists() ? (int) $t->user_id : $firstUserId;

                $pengajuan = $this->createPengajuan($t, $catMap, $jenisIt, $pemohonId);

                $stats = $this->migrateTicketMessages($pengajuan, $t, $pemohonId);
                $statsPesan += $stats['pesan'];
                $statsHistori += $stats['histori'];
                $statsLampiran += $stats['lampiran'];

                $migrated++;
            }
        });

        $this->info("Migrated: {$migrated} tickets; skipped (already migrated, repaired fields): {$skipped} (repaired: {$repaired} fields); backfilled: {$statsPesan} pesan, {$statsHistori} histori, {$statsLampiran} lampiran.");

        return self::SUCCESS;
    }

    protected function buildCatMap($jenisIt): array
    {
        $catMap = [];
        if (! $jenisIt) {
            return $catMap;
        }

        foreach (DB::table('categories')->get() as $c) {
            $nama = $c->name ?? $c->nama ?? ('Kategori '.$c->id);
            $kp = KategoriPengajuan::whereHas('jenisList', fn ($q) => $q->where('code', 'IT'))->where('name', $nama)->first();
            if ($kp) {
                $catMap[$c->id] = $kp->id;
                $catMap[(string) $nama] = $kp->id;
                $catMap[(string) $c->name] = $kp->id;
            }
        }

        return $catMap;
    }

    protected function resolveKategori(object $t, array $catMap, $jenisIt): ?int
    {
        foreach ([$t->category_id ?? null, $t->kategori_id ?? null, $t->category ?? null] as $key) {
            if ($key !== null && array_key_exists($key, $catMap)) {
                return $catMap[$key];
            }
            if (is_numeric($key) && isset($catMap[(int) $key])) {
                return $catMap[(int) $key];
            }
        }

        return KategoriPengajuan::whereHas('jenisList', fn ($q) => $q->where('code', 'IT'))->first()?->id;
    }

    protected function previewPengajuan(object $t, array $catMap, $jenisIt, ?int $pemohonId): Pengajuan
    {
        $pengajuan = new Pengajuan([
            'nomor' => $t->ticket_number ?? ('T-'.str_pad((string) $t->id, 3, '0', STR_PAD_LEFT)),
            'kategori_id' => $this->resolveKategori($t, $catMap, $jenisIt),
            'pemohon_id' => $pemohonId ?? 0,
            'status' => $this->mapStatus($t->status ?? 'open'),
            'created_by' => $pemohonId ?? 0,
            'legacy_source' => 'ticket',
            'legacy_id' => $t->id,
        ]);
        $pengajuan->created_at = $t->created_at ?? now();
        $pengajuan->updated_at = $t->updated_at ?? $t->created_at ?? now();

        return $pengajuan;
    }

    protected function createPengajuan(object $t, array $catMap, $jenisIt, ?int $pemohonId): Pengajuan
    {
        $kategoriId = $this->resolveKategori($t, $catMap, $jenisIt);

        $status = $this->mapStatus($t->status ?? 'open');

        $pengajuan = new Pengajuan([
            'nomor' => $t->ticket_number ?? ('T-'.str_pad((string) $t->id, 3, '0', STR_PAD_LEFT)),
            'kategori_id' => $kategoriId,
            'pemohon_id' => $pemohonId,
            'pemohon_nama' => $t->nama_pemohon ?? DB::table('users')->where('id', $pemohonId)->value('name') ?? 'Unknown',
            'department_id' => $t->department_id ?? null,
            'lokasi_id' => $t->location_id ?? $t->lokasi_id ?? null,
            'building_id' => $t->building_id ?? null,
            'deskripsi' => $t->description ?? $t->deskripsi ?? '-',
            'prioritas' => $this->mapPrioritas($t->priority ?? 'medium'),
            'status' => $status,
            'follow_up' => $t->admin_notes ?? null,
            'tanggal' => $t->opened_at ?? $t->created_at ?? now(),
            'started_at' => $t->in_progress_at ?? (($status === 'in_progress') ? ($t->updated_at ?? $t->created_at ?? now()) : null),
            'closed_at' => $t->closed_at ?? (($status === 'ditutup') ? ($t->updated_at ?? $t->created_at ?? now()) : null),
            'confirmed_at' => $t->confirmed_at ?? (($status === 'confirmed') ? ($t->updated_at ?? $t->created_at ?? now()) : null),
            'rejected_at' => $t->last_rejection_at ?? (($status === 'rejected') ? ($t->updated_at ?? $t->created_at ?? now()) : null),
            'rejection_count' => (int) ($t->rejection_count ?? 0),
            'user_confirmation' => (bool) ($t->user_confirmation ?? false),
            'user_confirmed_at' => $t->user_confirmed_at ?? null,
            'created_by' => $pemohonId,
            'updated_by' => null,
            'legacy_source' => 'ticket',
            'legacy_id' => $t->id,
        ]);

        $pengajuan->created_at = $t->created_at ?? now();
        $pengajuan->updated_at = $t->updated_at ?? $t->created_at ?? now();
        if (! empty($t->deleted_at)) {
            $pengajuan->deleted_at = $t->deleted_at;
        }
        $pengajuan->save();

        LegacyMap::firstOrCreate([
            'source_table' => 'tickets',
            'source_id' => $t->id,
        ], [
            'pengajuan_id' => $pengajuan->id,
        ]);

        if (! $this->historiExists($pengajuan->id, 'created', 'Migrasi dari ticket #'.$t->id, $pengajuan->created_at)) {
            $this->createHistori($pengajuan, 'open', 'created', 'Migrasi dari ticket #'.$t->id, null, 'system', $pengajuan->created_at);
        }

        if ($pengajuan->started_at && ! $this->historiExists($pengajuan->id, 'started', 'Pengajuan dimulai.', $pengajuan->started_at)) {
            $this->createHistori($pengajuan, 'in_progress', 'started', 'Pengajuan dimulai.', null, 'system', $pengajuan->started_at);
        }

        if ($pengajuan->closed_at && ! $this->historiExists($pengajuan->id, 'closed', 'Pengajuan ditutup.', $pengajuan->closed_at)) {
            $this->createHistori($pengajuan, 'ditutup', 'closed', 'Pengajuan ditutup.', null, 'system', $pengajuan->closed_at);
        }

        if ((int) $pengajuan->rejection_count > 0 || $pengajuan->rejected_at) {
            $ts = $pengajuan->rejected_at ?? $pengajuan->updated_at;
            if (! $this->historiExists($pengajuan->id, 'rejected', 'Riwayat penolakan migrasi.', $ts)) {
                $this->createHistori(
                    $pengajuan,
                    'rejected',
                    'rejected',
                    'Riwayat penolakan migrasi.',
                    null,
                    'system',
                    $ts,
                    ['rejection_count' => (int) $pengajuan->rejection_count, 'synthetic' => true]
                );
            }
        }

        if ($pengajuan->user_confirmation || $pengajuan->confirmed_at) {
            $ts = $pengajuan->confirmed_at ?? $pengajuan->user_confirmed_at ?? $pengajuan->updated_at;
            if (! $this->historiExists($pengajuan->id, 'confirmed', 'Pengajuan dikonfirmasi.', $ts)) {
                $this->createHistori(
                    $pengajuan,
                    'confirmed',
                    'confirmed',
                    'Pengajuan dikonfirmasi.',
                    null,
                    'system',
                    $ts
                );
            }
        }

        if (! empty($t->photo)) {
            $this->createLampiran($pengajuan, (string) $t->photo, 'initial', $pemohonId, $pengajuan->created_at);
        }

        return $pengajuan;
    }

    protected function repairExisting(Pengajuan $pengajuan, object $t, array $catMap, bool $dry): int
    {
        $jenisIt = JenisPengajuan::where('code', 'IT')->first();
        $updates = [];

        $expectedPrioritas = $this->mapPrioritas($t->priority ?? 'medium');
        if ((string) $pengajuan->prioritas !== $expectedPrioritas) {
            $updates['prioritas'] = $expectedPrioritas;
        }

        $expectedKategori = $this->resolveKategori($t, $catMap, $jenisIt);
        if ($expectedKategori && (int) $pengajuan->kategori_id !== (int) $expectedKategori) {
            $updates['kategori_id'] = $expectedKategori;
        }

        $expectedStatus = $this->statusForRepair($pengajuan, $t);
        if ((string) $pengajuan->status !== $expectedStatus) {
            $updates['status'] = $expectedStatus;
        }

        foreach ([
            'started_at' => $t->in_progress_at ?? null,
            'closed_at' => $t->closed_at ?? null,
            'confirmed_at' => $t->confirmed_at ?? null,
            'rejected_at' => $t->last_rejection_at ?? null,
        ] as $field => $legacyRaw) {
            $expected = $legacyRaw ? (string) $this->parseDate($legacyRaw) : null;
            $current = $pengajuan->{$field} ? (string) $this->parseDate($pengajuan->{$field}) : null;
            if ($current !== $expected) {
                $updates[$field] = $expected;
            }
        }

        if ($updates !== [] && ! $dry) {
            $pengajuan->update($updates);
        }

        return count($updates);
    }

    protected function statusForRepair(Pengajuan $pengajuan, object $t): string
    {
        $legacy = $this->mapStatus($t->status ?? 'open');

        if ((string) $pengajuan->status === 'pending_confirmation' && $legacy === 'ditutup') {
            return 'ditutup';
        }

        return $legacy;
    }

    protected function createHistori(Pengajuan $pengajuan, string $status, string $action, ?string $notes, ?int $actorId, string $actorType, $createdAt, array $metadata = []): void
    {
        $histori = $pengajuan->historis()->create([
            'status' => $status,
            'action' => $action,
            'notes' => $notes,
            'actor_id' => $actorId,
            'actor_type' => $actorType,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);

        $timestamp = $this->parseDate($createdAt);
        if ($timestamp) {
            $histori->created_at = $timestamp;
            $histori->updated_at = $timestamp;
            $histori->saveQuietly();
        }
    }

    protected function createLampiran(Pengajuan $pengajuan, string $path, string $kind, ?int $uploadedBy, $createdAt, ?int $pesanId = null): void
    {
        if (DB::table('pengajuan_lampiran')->where('pengajuan_id', $pengajuan->id)->where('path', $path)->exists()) {
            return;
        }

        DB::table('pengajuan_lampiran')->insert([
            'pengajuan_id' => $pengajuan->id,
            'histori_id' => null,
            'pesan_id' => $pesanId,
            'kind' => $kind,
            'path' => $path,
            'original_name' => basename($path),
            'mime' => null,
            'size' => null,
            'uploaded_by' => $uploadedBy,
            'created_at' => $this->parseDate($createdAt) ?? now(),
            'updated_at' => $this->parseDate($createdAt) ?? now(),
        ]);
    }
}
