<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed default accounts for local dev & Postman collection.
     * Password for all accounts: rsazra.
     */
    public function run(): void
    {
        $itDepartment = Department::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Information Technology']
        );

        // Position is display-only: ensure codes referenced by users exist.
        foreach ([
            ['name' => 'IT', 'code' => 'IT', 'status' => true],
            ['name' => 'User', 'code' => 'user', 'status' => true],
            ['name' => 'Administrasi', 'code' => 'Administrasi', 'status' => true],
        ] as $pos) {
            Position::firstOrCreate(['code' => $pos['code']], $pos);
        }

        // Ensure dynamic roles exist even if RoleSeeder was skipped.
        foreach (['user', 'admin', 'ipsrs'] as $slug) {
            Role::firstOrCreate(['slug' => $slug], ['slug' => $slug, 'name' => $slug]);
        }

        $accounts = [
            [
                'email' => 'admin',
                'username' => 'admin',
                'name' => 'Admin IT',
                'role' => 'admin',
                'position' => 'IT',
            ],
            [
                'email' => 'administrasi',
                'username' => 'administrasi',
                'name' => 'Admin Administrasi',
                'role' => 'ipsrs',
                'position' => 'Administrasi',
            ],
            [
                'email' => 'user@rsazra.com',
                'username' => 'user',
                'name' => 'User Regular',
                'phone' => '08123456789',
                'role' => 'user',
                'position' => 'user',
            ],
        ];

        foreach ($accounts as $account) {
            User::firstOrCreate(['email' => $account['email']], [
                'name' => $account['name'],
                'username' => $account['username'],
                'password' => Hash::make('rsazra'),
                'phone' => $account['phone'] ?? '1234567890',
                'position' => $account['position'],
                'role' => $account['role'],
                'status' => 1,
                'department' => $itDepartment->code,
                'email_verified_at' => now(),
            ]);
        }
    }
}
