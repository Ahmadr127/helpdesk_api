<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            DepartmentSeeder::class,
            PositionSeeder::class,
            MasterDataSeeder::class,
            UnitProsesSeeder::class,
            AdminSeeder::class,
        ]);
    }
}