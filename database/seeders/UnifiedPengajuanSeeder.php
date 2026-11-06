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
            ['name' => 'Tiket IT / SIRS', 'prefix' => 'T', 'is_active' => true]
        );

        $jenisIpsrs = JenisPengajuan::firstOrCreate(
            ['code' => 'IPSRS'],
            ['name' => 'Order Perbaikan / IPSRS', 'prefix' => 'OP/RTG/MTC', 'is_active' => true]
        );

        $itKategoris = [];
        foreach (Category::with('unitProses')->where('status', 1)->get() as $category) {
            if (! $category->unitProses || $category->unitProses->code !== 'SIRS') {
                continue;
            }

            $kategori = KategoriPengajuan::firstOrCreate(
                ['jenis_id' => $jenisIt->id, 'name' => $category->name],
                ['code' => null, 'description' => null, 'status' => (int) $category->status]
            );

            $itKategoris[] = $kategori->id;
        }

        $ipsrsKategoris = [];
        foreach (KategoriOrder::where('status', 1)->get() as $kategoriOrder) {
            $kategori = KategoriPengajuan::firstOrCreate(
                ['jenis_id' => $jenisIpsrs->id, 'name' => $kategoriOrder->name],
                ['code' => $kategoriOrder->code, 'description' => null, 'status' => (int) $kategoriOrder->status]
            );

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
