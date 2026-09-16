<?php

namespace App\Services\Access;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleService
{
    /**
     * Single source of truth for role slugs + labels + badge colors.
     * New roles are created via DB (RoleSeeder / admin UI), this map only
     * provides human labels for known slugs; unknown slugs fall back gracefully.
     */
    public function all(): Collection
    {
        return Role::orderBy('slug')->get();
    }

    public function slugs(): array
    {
        return Role::orderBy('slug')->pluck('slug')->all()
            ?: array_keys($this->labels());
    }

    public function labels(): array
    {
        return [
            'user' => 'User (Regular)',
            'admin' => 'Admin IT',
            'ipsrs' => 'Admin IPSRS / Administrasi Umum',
        ];
    }

    public function label(string $slug): string
    {
        return $this->labels()[$slug]
            ?? Role::where('slug', $slug)->value('name')
            ?? ucfirst($slug);
    }

    public function badgeClasses(string $slug): string
    {
        return match ($slug) {
            'admin' => 'bg-blue-100 text-blue-700 border border-blue-200',
            'ipsrs' => 'bg-green-100 text-green-700 border border-green-200',
            default => 'bg-gray-100 text-gray-700 border border-gray-200',
        };
    }

    public function exists(string $slug): bool
    {
        return Role::where('slug', $slug)->exists();
    }

    public function validationRule(): Rule
    {
        return Rule::exists('roles', 'slug');
    }

    public function syncPermissions(string $slug, array $permissionIds): void
    {
        $role = Role::where('slug', $slug)->firstOrFail();

        $validIds = Permission::whereIn('id', $permissionIds)->pluck('id')->all();

        DB::transaction(function () use ($slug, $validIds) {
            DB::table('role_permissions')->where('role', $slug)->delete();
            foreach ($validIds as $permissionId) {
                DB::table('role_permissions')->insert([
                    'role' => $slug,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        unset($role);
    }

    public function slugsForPermission(string $permissionSlug): array
    {
        return DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('permissions.slug', $permissionSlug)
            ->pluck('role_permissions.role')
            ->unique()
            ->values()
            ->all();
    }
}
