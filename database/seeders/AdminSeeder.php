<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
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

        // Ensure positions for API validation exist (IT, user, Administrasi)
        $extraPositions = [
            ['name' => 'IT', 'code' => 'IT', 'status' => true],
            ['name' => 'User', 'code' => 'user', 'status' => true],
            ['name' => 'ADMINISTRASI', 'code' => 'ADMINISTRASI', 'status' => true],
        ];
        foreach ($extraPositions as $pos) {
            Position::firstOrCreate(['code' => $pos['code']], $pos);
        }

        // Create Admin IT
        User::firstOrCreate(['email' => 'admin'], [
            'name' => 'Admin IT',
            'password' => Hash::make('123'),
            'phone' => '1234567890',
            'position' => 'IT',
            'role' => 'admin',
            'status' => 1,
            'department' => $itDepartment->code,
            'email_verified_at' => now(),
        ]);
        User::firstOrCreate(['email' => 'administrasi'], [
            'name' => 'Admin Administrasi',
            'password' => Hash::make('123'),
            'phone' => '1234567890',
            'position' => 'Administrasi',
            'role' => 'admin',
            'status' => 1,
            'department' => $itDepartment->code,
            'email_verified_at' => now(),
        ]);
        // Create regular user untuk testing Postman & API
        User::firstOrCreate(['email' => 'user@rsazra.com'], [
            'name' => 'User Regular',
            'password' => Hash::make('123'),
            'phone' => '08123456789',
            'position' => 'user',
            'role' => 'user',
            'status' => 1,
            'department' => $itDepartment->code,
            'email_verified_at' => now(),
        ]);
        // Alias untuk kompatibilitas koleksi lama / test: user@example.com
        User::firstOrCreate(['email' => 'user@example.com'], [
            'name' => 'User Example',
            'password' => Hash::make('123'),
            'phone' => '08123456788',
            'position' => 'user',
            'role' => 'user',
            'status' => 1,
            'department' => $itDepartment->code,
            'email_verified_at' => now(),
        ]);
        // Additional user with general department
        $generalDept = Department::firstOrCreate(['code' => 'GENERAL'], ['name' => 'GENERAL']);
        User::firstOrCreate(['email' => 'user2@rsazra.com'], [
            'name' => 'User 2',
            'password' => Hash::make('123'),
            'phone' => '08123456780',
            'position' => 'STF_IT',
            'role' => 'user',
            'status' => 1,
            'department' => $generalDept->code,
            'email_verified_at' => now(),
        ]);
    }
}