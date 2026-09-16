<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['slug' => 'user', 'name' => 'User (Regular)', 'description' => 'Pengguna biasa'],
            ['slug' => 'admin', 'name' => 'Admin IT', 'description' => 'Administrator IT (tiket SIRS)'],
            ['slug' => 'ipsrs', 'name' => 'Admin IPSRS / Administrasi Umum', 'description' => 'Administrator IPSRS (order perbaikan)'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }

        $this->command->info('Roles seeded: '.Role::count());
    }
}
