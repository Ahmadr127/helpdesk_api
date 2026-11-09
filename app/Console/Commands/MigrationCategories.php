<?php

namespace App\Console\Commands;

use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrationCategories extends Command
{
    protected $signature = 'migration:categories {--dry-run : Show counts without writing}';

    protected $description = 'Migrate legacy categories and kategori_order into kategori_pengajuan';

    public function handle(): int
    {
        $jenisIt = JenisPengajuan::where('code', 'IT')->first();
        $jenisIpsrs = JenisPengajuan::where('code', 'IPSRS')->first();

        if (! $jenisIt || ! $jenisIpsrs) {
            $this->error('Jenis pengajuan IT/IPSRS belum di-seed.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');

        $categories = DB::table('categories')->orderBy('id')->get();
        $kategoriOrders = DB::table('kategori_order')->orderBy('id')->get();

        $itCount = $categories->count();
        $ipsrsCount = $kategoriOrders->count();

        $this->info("Legacy: {$itCount} categories (IT), {$ipsrsCount} kategori_order (IPSRS).");

        if ($dry) {
            $this->warn('DRY-RUN: tidak ada perubahan.');

            $itActive = $kategoriOrders->filter(fn ($ko) => empty($ko->deleted_at))->count();
            $itDeleted = $kategoriOrders->filter(fn ($ko) => ! empty($ko->deleted_at))->count();
            $this->line("would create IT: {$itCount}; IPSRS active: {$itActive}; IPSRS soft-deleted: {$itDeleted}.");

            return self::SUCCESS;
        }

        $itKategoriIds = [];
        $ipsrsKategoriIds = [];

        DB::transaction(function () use ($categories, $kategoriOrders, $jenisIt, $jenisIpsrs, &$itKategoriIds, &$ipsrsKategoriIds): void {
            foreach ($categories as $cat) {
                $nama = $cat->name ?? $cat->nama ?? ('Kategori '.$cat->id);

                $kategori = KategoriPengajuan::firstOrCreate(
                    ['jenis_id' => $jenisIt->id, 'name' => $nama],
                    [
                        'code' => 'IT-'.$cat->id,
                        'description' => null,
                        'status' => (int) ($cat->status ?? 1),
                    ]
                );

                $itKategoriIds[] = $kategori->id;
            }

            foreach ($kategoriOrders as $ko) {
                $nama = $ko->nama ?? $ko->name ?? ('Kategori Order '.$ko->id);

                // Cari juga yang soft-deleted agar tidak duplikat saat re-run.
                $kategori = KategoriPengajuan::withTrashed()
                    ->where('jenis_id', $jenisIpsrs->id)
                    ->where('name', $nama)
                    ->first();

                if (! $kategori) {
                    $kategori = KategoriPengajuan::create([
                        'jenis_id' => $jenisIpsrs->id,
                        'name' => $nama,
                        'code' => $ko->code ?? null,
                        'description' => null,
                        'status' => (int) ($ko->status ?? 1),
                    ]);
                } else {
                    // Selaraskan deleted_at / status dengan legacy
                    if (! empty($ko->deleted_at) && $kategori->deleted_at === null) {
                        $kategori->delete();
                    } elseif (empty($ko->deleted_at) && $kategori->deleted_at !== null) {
                        $kategori->restore();
                        $kategori->update(['status' => (int) ($ko->status ?? 1), 'code' => $ko->code ?? $kategori->code]);
                    }
                }

                $ipsrsKategoriIds[] = $kategori->id;
            }

            $adminRole = Role::where('slug', 'admin')->first();
            if ($adminRole && $itKategoriIds !== []) {
                $adminRole->kategoris()->sync($itKategoriIds);
            }

            $ipsrsRole = Role::where('slug', 'ipsrs')->first();
            if ($ipsrsRole && $ipsrsKategoriIds !== []) {
                $syncIds = KategoriPengajuan::whereIn('id', $ipsrsKategoriIds)->whereNull('deleted_at')->pluck('id')->all();
                if ($syncIds !== []) {
                    $ipsrsRole->kategoris()->sync($syncIds);
                }
            }
        });

        $newIt = KategoriPengajuan::where('jenis_id', $jenisIt->id)->count();
        $newIpsrs = KategoriPengajuan::where('jenis_id', $jenisIpsrs->id)->count();
        $newIpsrsActive = KategoriPengajuan::where('jenis_id', $jenisIpsrs->id)->whereNull('deleted_at')->count();
        $this->info("Hasil: {$newIt} kategori IT, {$newIpsrs} kategori IPSRS (aktif: {$newIpsrsActive}).");

        return self::SUCCESS;
    }
}
