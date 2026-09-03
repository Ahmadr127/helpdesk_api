<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;

class EnsureItPositionSeeder extends Seeder
{
    /**
     * Pastikan position IT ada di production (idempoten).
     * Production hilang karena PositionSeeder hanya buat KA_IT/STF_IT, bukan code 'IT'.
     * Pakai updateOrCreate agar aman di-rerun tanpa duplicate.
     */
    public function run(): void
    {
        $items = [
            ['code' => 'IT',           'name' => 'IT',           'status' => true],
            ['code' => 'ADMINISTRASI', 'name' => 'ADMINISTRASI', 'status' => true],
        ];

        foreach ($items as $d) {
            Position::updateOrCreate(
                ['code' => $d['code']],
                ['name' => $d['name'], 'status' => $d['status']]
            );
        }

        $this->command->info('Ensure IT positions seeded: IT, ADMINISTRASI');
    }
}
