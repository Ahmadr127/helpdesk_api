<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Department;
use App\Models\Building;
use App\Models\Location;
use App\Models\UnitProses;

class MasterDataSeeder extends DatabaseSeeder
{
    public function run(): void
    {
        // Unit Proses
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

        // Get Unit Proses IDs
        $rtgId = UnitProses::where('code', 'RTG')->first()->id;
        $sirsId = UnitProses::where('code', 'SIRS')->first()->id;

        // Categories with their respective Unit Proses
        $categories = [
            ['name' => 'Termedic', 'status' => 1, 'unit_proses_id' => $rtgId],
            ['name' => 'Printer', 'status' => 1, 'unit_proses_id' => $sirsId],
            ['name' => 'CCTV', 'status' => 1, 'unit_proses_id' => $sirsId],
            ['name' => 'Software', 'status' => 1, 'unit_proses_id' => $sirsId],
            ['name' => 'Hardware', 'status' => 1, 'unit_proses_id' => $sirsId],
        ];
        
        foreach ($categories as $category) {
            Category::create($category);
        }

        // Buildings
        $buildings = [
            ['name' => 'Gedung A', 'code' => 'A', 'status' => 1],
            ['name' => 'Gedung B', 'code' => 'B', 'status' => 1],
            ['name' => 'Gedung C', 'code' => 'C', 'status' => 1],
        ];

        foreach ($buildings as $building) {
            Building::create($building);
        }

        // Locations
        $locations = [
            ['name' => 'UGD', 'building_id' => 1, 'status' => 1], // Gedung A
            ['name' => 'BPJS', 'building_id' => 2, 'status' => 1], // Gedung B
            ['name' => 'Parkiran', 'building_id' => 3, 'status' => 1], // Gedung C
            ['name' => 'Taman Bermain Anak', 'building_id' => 3, 'status' => 1], // Gedung C
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}