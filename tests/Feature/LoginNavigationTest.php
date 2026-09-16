<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoginNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function grantPermission(string $slug, string $role): void
    {
        Role::firstOrCreate(['slug' => $role], ['slug' => $role, 'name' => $role]);
        $perm = Permission::firstOrCreate(['slug' => $slug], [
            'name' => ucwords(str_replace('.', ' ', $slug)),
            'slug' => $slug,
            'group' => 'Admin',
        ]);
        $key = ['role' => $role, 'permission_id' => $perm->id];
        if (Schema::hasColumn('role_permissions', 'position')) {
            $key['position'] = null;
        }
        DB::table('role_permissions')->updateOrInsert(
            $key,
            ['created_at' => now(), 'updated_at' => now()]
        );
    }

    private function makeUser(array $overrides): User
    {
        Role::firstOrCreate(['slug' => $overrides['role'] ?? 'user'], [
            'slug' => $overrides['role'] ?? 'user',
            'name' => $overrides['role'] ?? 'user',
        ]);

        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('secret123'),
            'phone' => '0811',
            'role' => 'user',
            'position' => null,
            'department' => 'IT',
            'status' => 1,
        ], $overrides));
    }

    public function test_admin_it_login_redirects_to_admin_dashboard()
    {
        $this->grantPermission('admin.dashboard', 'admin');
        $this->makeUser(['name' => 'Admin IT', 'email' => 'adminit@example.com', 'role' => 'admin', 'position' => 'IT']);

        $this->post('/login', ['login' => 'adminit@example.com', 'password' => 'secret123'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_umum_login_redirects_to_admin_ipsrs_dashboard()
    {
        $this->grantPermission('ipsrs.dashboard', 'ipsrs');
        $this->makeUser(['name' => 'Admin Umum', 'email' => 'adminumum@example.com', 'role' => 'ipsrs', 'position' => 'Administrasi']);

        $this->post('/login', ['login' => 'adminumum@example.com', 'password' => 'secret123'])
            ->assertRedirect(route('admin.ipsrs.dashboard'));
    }

    public function test_regular_user_login_redirects_to_user_dashboard()
    {
        // Seed permission rows agar fallback (permissions kosong => semua akses) tidak aktif
        Permission::create(['name' => 'Admin Dashboard Access', 'slug' => 'admin.dashboard', 'group' => 'Admin']);
        Permission::create(['name' => 'IPSRS Dashboard Access', 'slug' => 'ipsrs.dashboard', 'group' => 'Admin']);
        $this->makeUser(['name' => 'User', 'email' => 'user@example.com', 'role' => 'user', 'position' => null]);

        $this->post('/login', ['login' => 'user@example.com', 'password' => 'secret123'])
            ->assertRedirect(route('user.dashboard'));
    }

    public function test_login_uses_role_permission_not_position_for_admin_it()
    {
        $this->grantPermission('admin.dashboard', 'admin');
        // Position display yang salah tetap dapat admin.dashboard karena akses berbasis role
        $this->makeUser(['name' => 'Admin IT', 'email' => 'adm1@example.com', 'role' => 'admin', 'position' => 'Administrasi']);

        $this->post('/login', ['login' => 'adm1@example.com', 'password' => 'secret123'])
            ->assertRedirect(route('admin.dashboard'));
    }
}
