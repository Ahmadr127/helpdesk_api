<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Location;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // Buildings
        $buildings = [
            ['name' => 'Gedung A', 'code' => 'A', 'status' => 1],
            ['name' => 'Gedung B', 'code' => 'B', 'status' => 1],
            ['name' => 'Gedung C', 'code' => 'C', 'status' => 1],
        ];

        foreach ($buildings as $building) {
            Building::firstOrCreate(['code' => $building['code']], $building);
        }

        // Locations (building_id dihapus dari locations — pindah ke departments)
        $locations = [
            ['name' => 'UGD', 'status' => 1],
            ['name' => 'BPJS', 'status' => 1],
            ['name' => 'Parkiran', 'status' => 1],
            ['name' => 'Taman Bermain Anak', 'status' => 1],
        ];

        foreach ($locations as $location) {
            Location::firstOrCreate(['name' => $location['name']], $location);
        }
    }
}
