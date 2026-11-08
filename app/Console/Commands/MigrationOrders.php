<?php

namespace App\Console\Commands;

use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\PengajuanOrderDetail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrationOrders extends Command
{
    protected $signature = 'migration:orders {--dry-run}';

    protected $description = 'Migrate legacy order_perbaikan into pengajuan';

    public function handle(): int
    {
        $jenisIpsrs = JenisPengajuan::where('code', 'IPSRS')->first();
        $dry = (bool) $this->option('dry-run');

        $orders = DB::table('order_perbaikan')->get();
        $this->info("Legacy orders: {$orders->count()}");

        if ($dry) {
            $this->warn('DRY-RUN');

            return self::SUCCESS;
        }

        $catMap = [];
        foreach (DB::table('kategori_order')->get() as $ko) {
            $nama = $ko->nama ?? $ko->name ?? ('Kategori Order '.$ko->id);
            $kp = KategoriPengajuan::where('jenis_id', $jenisIpsrs->id)->where('name', $nama)->first();
            if ($kp) {
                $catMap[$ko->id] = $kp->id;
            }
        }

        $migrated = 0;
        DB::transaction(function () use ($orders, $catMap, $jenisIpsrs, &$migrated): void {
            foreach ($orders as $o) {
                $existing = Pengajuan::where('legacy_source', 'order')->where('legacy_id', $o->id)->first();
                if ($existing) {
                    continue;
                }

                $kategoriId = $catMap[$o->kategori_order ?? null] ?? KategoriPengajuan::where('jenis_id', $jenisIpsrs->id)->first()?->id;
                $pemohon = $o->created_by ? User::find($o->created_by) : null;

                $pengajuan = new Pengajuan([
                    'nomor' => $o->nomor ?? ('OP-'.$o->id),
                    'kategori_id' => $kategoriId,
                    'pemohon_id' => $pemohon?->id ?? User::first()->id,
                    'pemohon_nama' => $o->nama_peminta ?? $pemohon?->name ?? 'Unknown',
                    'department_id' => $o->department_id ?? null,
                    'lokasi_id' => null,
                    'building_id' => null,
                    'deskripsi' => $o->keluhan ?? '-',
                    'prioritas' => $this->mapPrioritas($o->prioritas ?? 'medium'),
                    'status' => $this->mapStatus($o->status ?? 'open'),
                    'follow_up' => $o->follow_up ?? null,
                    'tanggal' => $o->tanggal ?? $o->created_at ?? now(),
                    'created_by' => $pemohon?->id ?? User::first()->id,
                    'updated_by' => $o->updated_by,
                    'legacy_source' => 'order',
                    'legacy_id' => $o->id,
                ]);
                // Jangan ikut waktu migrasi: pertahankan timestamp asli legacy.
                $pengajuan->created_at = $o->created_at ?? $o->tanggal ?? now();
                $pengajuan->updated_at = $o->updated_at ?? $o->tanggal ?? $o->created_at ?? now();
                $pengajuan->save();

                PengajuanOrderDetail::create([
                    'pengajuan_id' => $pengajuan->id,
                    'unit_penerima' => $o->unit_penerima ?? null,
                    'kode_inventaris' => $o->kode_inventaris ?? null,
                    'nama_barang' => $o->nama_barang ?? null,
                    'unit_proses_id' => $o->unit_proses_id ?? null,
                ]);

                $pengajuan->historis()->create([
                    'status' => $pengajuan->status,
                    'action' => 'migrated',
                    'notes' => 'Migrasi dari order #'.$o->id,
                    'actor_id' => null,
                    'actor_type' => 'system',
                ]);

                $migrated++;
            }
        });

        $this->info("Migrated: {$migrated} orders.");

        return self::SUCCESS;
    }

    protected function mapPrioritas(?string $p): string
    {
        $v = strtolower(trim((string) $p));
        return match (true) {
            in_array($v, ['high', 'tinggi', 'urgent', 'sedang?no'], true) => 'high',
            in_array($v, ['low', 'rendah'], true) => 'low',
            default => 'medium',
        };
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