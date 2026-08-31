<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\UnitProses;

class UpdateCategoryUnitProsesSeeder extends Seeder
{
    public function run(): void
    {
        // Get Unit Proses IDs
        $rtgId = UnitProses::where('code', 'RTG')->first()->id;
        $sirsId = UnitProses::where('code', 'SIRS')->first()->id;

        // Update Termedic category
        Category::where('name', 'Termedic')->update([
            'unit_proses_id' => $rtgId
        ]);

        // Update IT-related categories
        Category::whereIn('name', ['Printer', 'CCTV', 'Software', 'Hardware'])->update([
            'unit_proses_id' => $sirsId
        ]);
    }
} 