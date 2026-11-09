<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\MapsLegacyPengajuan;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\LegacyMap;
use App\Models\Pengajuan;
use App\Models\PengajuanOrderDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrationOrders extends Command
{
    use MapsLegacyPengajuan;

    protected $signature = 'migration:orders {--dry-run}';

    protected $description = 'Migrate legacy order_perbaikan into pengajuan';

    public function handle(): int
    {
        $jenisIpsrs = JenisPengajuan::where('code', 'IPSRS')->first();
        $dry = (bool) $this->option('dry-run');

        $orders = DB::table('order_perbaikan')->orderBy('created_at')->orderBy('id')->get();
        $this->info("Legacy orders: {$orders->count()}");

        $catMap = $this->buildCatMap($jenisIpsrs);

        if ($dry) {
            $this->warn('DRY-RUN');

            $would = 0;
            $wouldRepair = 0;
            foreach ($orders as $o) {
                $existing = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $o->id)->first();
                if (! $existing) {
                    $would++;
                    continue;
                }
                $wouldRepair += $this->countRepair($existing, $o, $catMap);
            }
            $this->line("would migrate: {$would} orders; would repair: {$wouldRepair} fields on existing.");

            return self::SUCCESS;
        }

        $migrated = 0;
        $repaired = 0;
        $skipped = 0;

        DB::transaction(function () use ($orders, $catMap, $jenisIpsrs, &$migrated, &$repaired, &$skipped): void {
            $firstUserId = $this->firstUserId();

            foreach ($orders as $o) {
                $existing = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $o->id)->first();
                if ($existing) {
                    $repaired += $this->repairExisting($existing, $o, $catMap);
                    $skipped++;
                    continue;
                }

                $kategoriId = $this->resolveKategori($o, $catMap, $jenisIpsrs);
                $pemohonId = $o->created_by && DB::table('users')->where('id', $o->created_by)->exists() ? (int) $o->created_by : $firstUserId;

                $status = $this->mapStatus($o->status ?? 'open');

                $pengajuan = new Pengajuan([
                    'nomor' => $o->nomor ?? ('OP-'.$o->id),
                    'kategori_id' => $kategoriId,
                    'pemohon_id' => $pemohonId,
                    'pemohon_nama' => $o->nama_peminta ?? DB::table('users')->where('id', $pemohonId)->value('name') ?? 'Unknown',
                    'department_id' => $o->department_id ?? null,
                    'lokasi_id' => $o->lokasi ?? null,
                    'building_id' => null,
                    'deskripsi' => $o->keluhan ?? '-',
                    'prioritas' => $this->mapPrioritas($o->prioritas ?? 'medium'),
                    'status' => $status,
                    'follow_up' => $o->follow_up ?? null,
                    'tanggal' => $o->tanggal ?? $o->created_at ?? now(),
                    'started_at' => $status === 'in_progress' ? ($o->updated_at ?? $o->created_at ?? now()) : null,
                    'closed_at' => $status === 'ditutup' ? ($o->updated_at ?? $o->created_at ?? now()) : null,
                    'confirmed_at' => $status === 'confirmed' ? ($o->updated_at ?? $o->created_at ?? now()) : null,
                    'rejected_at' => $status === 'rejected' ? ($o->updated_at ?? $o->created_at ?? now()) : null,
                    'created_by' => $pemohonId,
                    'updated_by' => $o->updated_by,
                    'legacy_source' => 'order',
                    'legacy_id' => $o->id,
                ]);

                $pengajuan->created_at = $o->created_at ?? $o->tanggal ?? now();
                $pengajuan->updated_at = $o->updated_at ?? $o->tanggal ?? $o->created_at ?? now();
                $pengajuan->save();

                LegacyMap::firstOrCreate([
                    'source_table' => 'order_perbaikan',
                    'source_id' => $o->id,
                ], [
                    'pengajuan_id' => $pengajuan->id,
                ]);

                PengajuanOrderDetail::firstOrCreate([
                    'pengajuan_id' => $pengajuan->id,
                ], [
                    'unit_penerima' => $o->unit_penerima ?? null,
                    'kode_inventaris' => $o->kode_inventaris ?? null,
                    'nama_barang' => $o->nama_barang ?? null,
                    'unit_proses_id' => $o->unit_proses_id ?? null,
                ]);

                if (! $this->historiExists($pengajuan->id, 'migrated', 'Migrasi dari order #'.$o->id, $pengajuan->created_at)) {
                    $pengajuan->historis()->create([
                        'status' => $pengajuan->status,
                        'action' => 'migrated',
                        'notes' => 'Migrasi dari order #'.$o->id,
                        'actor_id' => null,
                        'actor_type' => 'system',
                        'created_at' => $pengajuan->created_at,
                        'updated_at' => $pengajuan->created_at,
                    ]);
                }

                $migrated++;
            }
        });

        $this->info("Migrated: {$migrated} orders; skipped (already migrated, repaired fields): {$skipped} (repaired: {$repaired} fields).");

        return self::SUCCESS;
    }

    protected function buildCatMap($jenisIpsrs): array
    {
        $catMap = [];
        if (! $jenisIpsrs) {
            return $catMap;
        }

        foreach (DB::table('kategori_order')->get() as $ko) {
            $nama = $ko->nama ?? $ko->name ?? ('Kategori Order '.$ko->id);
            $kp = KategoriPengajuan::where('jenis_id', $jenisIpsrs->id)->where('name', $nama)->first();
            if ($kp) {
                $catMap[$ko->id] = $kp->id;
                $catMap[(string) $nama] = $kp->id;
                $catMap[(string) $ko->name] = $kp->id;
            }
        }

        return $catMap;
    }

    protected function resolveKategori(object $o, array $catMap, $jenisIpsrs): ?int
    {
        $key = $o->kategori_order ?? null;
        if ($key !== null && array_key_exists($key, $catMap)) {
            return $catMap[$key];
        }

        if (is_numeric($key) && isset($catMap[(int) $key])) {
            return $catMap[(int) $key];
        }

        return KategoriPengajuan::where('jenis_id', $jenisIpsrs?->id)->first()?->id;
    }

    protected function countRepair(Pengajuan $pengajuan, object $o, array $catMap): int
    {
        return count($this->expectedUpdates($pengajuan, $o, $catMap));
    }

    protected function expectedUpdates(Pengajuan $pengajuan, object $o, array $catMap): array
    {
        $jenisIpsrs = JenisPengajuan::where('code', 'IPSRS')->first();
        $updates = [];

        $expectedPrioritas = $this->mapPrioritas($o->prioritas ?? 'medium');
        if ((string) $pengajuan->prioritas !== $expectedPrioritas) {
            $updates['prioritas'] = $expectedPrioritas;
        }

        $expectedKategori = $this->resolveKategori($o, $catMap, $jenisIpsrs);
        if ($expectedKategori && (int) $pengajuan->kategori_id !== (int) $expectedKategori) {
            $updates['kategori_id'] = $expectedKategori;
        }

        $expectedStatus = $this->statusForRepair($pengajuan, $o);
        if ((string) $pengajuan->status !== $expectedStatus) {
            $updates['status'] = $expectedStatus;
        }

        return $updates;
    }

    protected function statusForRepair(Pengajuan $pengajuan, object $o): string
    {
        $legacy = $this->mapStatus($o->status ?? 'open');

        // Constraint DB lama memakai pending_confirmation; workflow sekarang ditutup.
        // Normalisasi pending_confirmation -> ditutup untuk perbaikan.
        if ((string) $pengajuan->status === 'pending_confirmation' && $legacy === 'ditutup') {
            return 'ditutup';
        }

        return $legacy;
    }

    protected function repairExisting(Pengajuan $pengajuan, object $o, array $catMap): int
    {
        $updates = $this->expectedUpdates($pengajuan, $o, $catMap);

        if ($updates !== []) {
            $pengajuan->update($updates);
        }

        return count($updates);
    }
}
