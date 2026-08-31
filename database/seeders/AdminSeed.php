<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create IT department
        $itDepartment = Department::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Information Technology']
        );

        // Create Admin IT
        User::create([
            'name' => 'Admin IT',
            'email' => 'adminIT',
            'password' => Hash::make('123'),
            'phone' => '1234567890',
            'position' => 'IT',
            'role' => 'admin',
            'status' => 1,
            'department' => $itDepartment->code,
            'email_verified_at' => now(),
        ]);
        User::create([
            'name' => 'Admin Administrasi',
            'email' => 'administrasi2',
            'password' => Hash::make('1234'),
            'phone' => '1234567890',
            'position' => 'Administrasi',
            'role' => 'admin',
            'status' => 1,
            'department' => $itDepartment->code,
            'email_verified_at' => now(),
        ]);
    }
}