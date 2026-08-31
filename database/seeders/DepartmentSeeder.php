<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        $departments = [
            ['code' => 'GENERAL', 'name' => 'GENERAL'],
            ['code' => 'IT', 'name' => 'IT'],
            ['code' => 'KLINIK_PSIKIATER', 'name' => 'KLINIK PSIKIATER'],
            ['code' => 'AHLI_GIZI', 'name' => 'AHLI GIZI'],
            ['code' => 'AMBULANCE', 'name' => 'AMBULANCE'],
            ['code' => 'APOTIK', 'name' => 'APOTIK'],
            ['code' => 'HEMODIALISA', 'name' => 'HEMODIALISA'],
            ['code' => 'MEDICAL_CHECK_UP', 'name' => 'MEDICAL CHECK UP'],
            ['code' => 'RUANG_OK', 'name' => 'RUANG OK'],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
} 