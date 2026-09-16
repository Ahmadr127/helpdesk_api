<?php

namespace Tests\Concerns;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Schema;

trait SeedsAccessControl
{
    protected function seedRoles(): void
    {
        foreach ([
            ['slug' => 'user', 'name' => 'User (Regular)'],
            ['slug' => 'admin', 'name' => 'Admin IT'],
            ['slug' => 'ipsrs', 'name' => 'Admin IPSRS / Administrasi Umum'],
        ] as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }

    protected function grantPermission(string $slug, string $role): void
    {
        $this->seedRoles();
        $perm = Permission::firstOrCreate(['slug' => $slug], [
            'name' => ucwords(str_replace('.', ' ', $slug)),
            'slug' => $slug,
            'group' => 'Admin',
        ]);
        $key = ['role' => $role, 'permission_id' => $perm->id];
        if (Schema::hasColumn('role_permissions', 'position')) {
            $key['position'] = null;
        }
        \Illuminate\Support\Facades\DB::table('role_permissions')->updateOrInsert(
            $key,
            ['created_at' => now(), 'updated_at' => now()]
        );
    }

    protected function seedRolePermissions(): void
    {
        $this->seedRoles();
        foreach (\Database\Seeders\PermissionSeeder::rolePermissionsMap() as $role => $slugs) {
            foreach ($slugs as $slug) {
                $this->grantPermission($slug, $role);
            }
        }
    }
}
