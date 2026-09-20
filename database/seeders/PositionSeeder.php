<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hanya 3 posisi: Staff, Manager (dari Kepala), Direktur Utama
        $positions = [
            [
                'name' => 'Staff',
                'code' => 'STAFF',
                'status' => true,
            ],
            [
                'name' => 'Manager',
                'code' => 'MANAGER',
                'status' => true,
            ],
            [
                'name' => 'Direktur Utama',
                'code' => 'DIR_UT',
                'status' => true,
            ],
        ];

        foreach ($positions as $position) {
            Position::updateOrCreate(
                ['code' => $position['code']],
                $position
            );
        }
    }
}
