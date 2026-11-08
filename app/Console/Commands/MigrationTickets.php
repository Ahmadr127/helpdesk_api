<?php

namespace App\Console\Commands;

use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrationTickets extends Command
{
    protected $signature = 'migration:tickets {--dry-run}';

    protected $description = 'Migrate legacy tickets into pengajuan';

    public function handle(): int
    {
        $jenisIt = JenisPengajuan::where('code', 'IT')->first();
        $dry = (bool) $this->option('dry-run');

        $tickets = DB::table('tickets')->get();
        $this->info("Legacy tickets: {$tickets->count()}");

        if ($dry) {
            $this->warn('DRY-RUN');

            return self::SUCCESS;
        }

        // map legacy category id -> kategori_pengajuan id by name
        $catMap = [];
        foreach (DB::table('categories')->get() as $c) {
            $nama = $c->name ?? $c->nama ?? ('Kategori '.$c->id);
            $kp = KategoriPengajuan::where('jenis_id', $jenisIt->id)->where('name', $nama)->first();
            if ($kp) {
                $catMap[$c->id] = $kp->id;
            }
        }

        $migrated = 0;
        DB::transaction(function () use ($tickets, $catMap, $jenisIt, &$migrated): void {
            foreach ($tickets as $t) {
                $existing = Pengajuan::where('legacy_source', 'ticket')->where('legacy_id', $t->id)->first();
                if ($existing) {
                    continue;
                }

                $kategoriId = $catMap[$t->category_id ?? $t->kategori_id ?? null] ?? null;
                if (! $kategoriId) {
                    // fallback first IT kategori
                    $kategoriId = KategoriPengajuan::where('jenis_id', $jenisIt->id)->first()?->id;
                }

                $pemohon = $t->user_id ? User::find($t->user_id) : null;

                $pengajuan = new Pengajuan([
                    'nomor' => $t->ticket_number ?? ('T-'.str_pad((string) $t->id, 3, '0', STR_PAD_LEFT)),
                    'kategori_id' => $kategoriId,
                    'pemohon_id' => $pemohon?->id ?? User::first()->id,
                    'pemohon_nama' => $pemohon?->name ?? ($t->nama_pemohon ?? 'Unknown'),
                    'department_id' => $t->department_id ?? null,
                    'lokasi_id' => $t->location_id ?? $t->lokasi_id ?? null,
                    'building_id' => $t->building_id ?? null,
                    'deskripsi' => $t->description ?? $t->deskripsi ?? '-',
                    'prioritas' => $this->mapPrioritas($t->prioritas ?? 'medium'),
                    'status' => $this->mapStatus($t->status ?? 'open'),
                    'tanggal' => $t->created_at ?? now(),
                    'created_by' => $pemohon?->id ?? User::first()->id,
                    'updated_by' => null,
                    'legacy_source' => 'ticket',
                    'legacy_id' => $t->id,
                ]);
                // Jangan ikut waktu migrasi: pertahankan timestamp asli legacy.
                $pengajuan->created_at = $t->created_at ?? now();
                $pengajuan->updated_at = $t->updated_at ?? $t->created_at ?? now();
                $pengajuan->save();

                $pengajuan->historis()->create([
                    'status' => $pengajuan->status,
                    'action' => 'migrated',
                    'notes' => 'Migrasi dari ticket #'.$t->id,
                    'actor_id' => null,
                    'actor_type' => 'system',
                ]);

                $migrated++;
            }
        });

        $this->info("Migrated: {$migrated} tickets.");

        return self::SUCCESS;
    }

    protected function mapPrioritas(?string $p): string
    {
        $v = strtolower(trim((string) $p));
        return match (true) {
            in_array($v, ['high', 'tinggi', 'urgent'], true) => 'high',
            in_array($v, ['low', 'rendah'], true) => 'low',
            default => 'medium',
        };
    }

    protected function mapStatus(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'closed', 'pending' => 'pending_confirmation',
            'confirmed', 'completed', 'done' => 'confirmed',
            'rejected' => 'rejected',
            'in_progress', 'process' => 'in_progress',
            default => 'open',
        };
    }
}