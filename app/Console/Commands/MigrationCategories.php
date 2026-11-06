<?php

namespace App\Console\Commands;

use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
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

        $itCount = DB::table('categories')->count();
        $ipsrsCount = DB::table('kategori_order')->count();

        $this->info("Legacy: {$itCount} categories (IT), {$ipsrsCount} kategori_order (IPSRS).");

        if ($dry) {
            $this->warn('DRY-RUN: tidak ada perubahan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($jenisIt, $jenisIpsrs): void {
            foreach (DB::table('categories')->get() as $cat) {
                $nama = $cat->name ?? $cat->nama ?? ('Kategori '.$cat->id);
                KategoriPengajuan::firstOrCreate(
                    ['jenis_id' => $jenisIt->id, 'name' => $nama],
                    ['name' => $nama, 'status' => $cat->status ?? 1]
                );
            }

            foreach (DB::table('kategori_order')->get() as $ko) {
                $nama = $ko->nama ?? $ko->name ?? ('Kategori Order '.$ko->id);
                KategoriPengajuan::firstOrCreate(
                    ['jenis_id' => $jenisIpsrs->id, 'name' => $nama],
                    ['name' => $nama, 'status' => $ko->status ?? 1]
                );
            }
        });

        $newIt = KategoriPengajuan::where('jenis_id', $jenisIt->id)->count();
        $newIpsrs = KategoriPengajuan::where('jenis_id', $jenisIpsrs->id)->count();
        $this->info("Hasil: {$newIt} kategori IT, {$newIpsrs} kategori IPSRS.");

        return self::SUCCESS;
    }
}