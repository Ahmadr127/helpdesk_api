<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\JenisPengajuan;
use App\Models\KategoriOrder;
use App\Models\KategoriPengajuan;
use App\Models\Role;
use Illuminate\Database\Seeder;

class UnifiedPengajuanSeeder extends Seeder
{
    public function run(): void
    {
        $jenisIt = JenisPengajuan::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'SIRS', 'prefix' => 'T', 'is_active' => true]
        );

        // Pastikan label sinkron dengan nama flavour baru (SIRS / UMUM).
        // Code dipertahankan agar data lama tidak perlu migrasi.
        JenisPengajuan::where('code', 'IT')->update(['name' => 'SIRS']);

        $jenisIpsrs = JenisPengajuan::firstOrCreate(
            ['code' => 'IPSRS'],
            ['name' => 'UMUM', 'prefix' => 'OP/RTG/MTC', 'is_active' => true]
        );

        JenisPengajuan::where('code', 'IPSRS')->update(['name' => 'UMUM']);

        $itKategoris = [];
        foreach (Category::with('unitProses')->where('status', 1)->get() as $category) {
            if (! $category->unitProses || $category->unitProses->code !== 'SIRS') {
                continue;
            }

            $kategori = KategoriPengajuan::withTrashed()
                ->whereHas('jenisList', fn ($q) => $q->where('code', 'IT'))
                ->where('name', $category->name)
                ->first();

            if (! $kategori) {
                $kategori = KategoriPengajuan::create([
                    'name' => $category->name,
                    'code' => null,
                    'description' => null,
                    'status' => (int) $category->status,
                ]);
                $kategori->syncJenis([$jenisIt->id]);
            } else {
                $kategori->syncJenis(array_unique(array_merge(
                    $kategori->jenisList()->pluck('jenis_pengajuan.id')->all(),
                    [$jenisIt->id]
                )));
            }

            $itKategoris[] = $kategori->id;
        }

        $ipsrsKategoris = [];
        foreach (KategoriOrder::where('status', 1)->get() as $kategoriOrder) {
            $kategori = KategoriPengajuan::withTrashed()
                ->whereHas('jenisList', fn ($q) => $q->where('code', 'IPSRS'))
                ->where('name', $kategoriOrder->name)
                ->first();

            if (! $kategori) {
                $kategori = KategoriPengajuan::create([
                    'name' => $kategoriOrder->name,
                    'code' => $kategoriOrder->code,
                    'description' => null,
                    'status' => (int) $kategoriOrder->status,
                ]);
                $kategori->syncJenis([$jenisIpsrs->id]);
            } else {
                $kategori->syncJenis(array_unique(array_merge(
                    $kategori->jenisList()->pluck('jenis_pengajuan.id')->all(),
                    [$jenisIpsrs->id]
                )));
            }

            $ipsrsKategoris[] = $kategori->id;
        }

        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole && $itKategoris !== []) {
            $adminRole->kategoris()->sync($itKategoris);
        }

        $ipsrsRole = Role::where('slug', 'ipsrs')->first();
        if ($ipsrsRole && $ipsrsKategoris !== []) {
            $ipsrsRole->kategoris()->sync($ipsrsKategoris);
        }
    }
}
