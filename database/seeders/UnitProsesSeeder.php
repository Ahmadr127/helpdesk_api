<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UnitProses;

class UnitProsesSeeder extends Seeder
{
    public function run(): void
    {
        $unitProses = [
            ['name' => 'Logistik Farmasi', 'code' => 'LOGF', 'status' => 1],
            ['name' => 'Instalasi Pemeliharaan (IPSRS)', 'code' => 'RTG', 'status' => 1],
            ['name' => 'Sistem Informasi Rumah Sakit', 'code' => 'SIRS', 'status' => 1],
            ['name' => 'Sanitasi', 'code' => 'SNT', 'status' => 1],
            ['name' => 'Sarana (IPSRS)', 'code' => 'SRNS', 'status' => 1],
        ];
        
        foreach ($unitProses as $unit) {
            UnitProses::firstOrCreate(['code' => $unit['code']], $unit);
        }
    }
} 