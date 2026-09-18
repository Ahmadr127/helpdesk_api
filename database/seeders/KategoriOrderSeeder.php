<?php

namespace Database\Seeders;

use App\Models\KategoriOrder;
use Illuminate\Database\Seeder;

class KategoriOrderSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            ['name' => 'Perbaikan', 'code' => 'PRB', 'status' => true],
            ['name' => 'Pengadaan', 'code' => 'PENG', 'status' => true],
            ['name' => 'Kalibrasi', 'code' => 'KAL', 'status' => true],
            ['name' => 'Penggantian', 'code' => 'GNT', 'status' => true],
            ['name' => 'Lainnya', 'code' => 'LL', 'status' => true],
        ];

        foreach ($kategori as $data) {
            KategoriOrder::firstOrCreate(['code' => $data['code']], $data);
        }
    }
}
