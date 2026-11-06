<?php

namespace App\Services\Access;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleKategori;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleService
{
    public function all(): Collection
    {
        return Role::with(['permissions', 'kategoris'])
            ->orderBy('slug')
            ->get();
    }

    public function slugs(): array
    {
        return Role::orderBy('slug')->pluck('slug')->all();
    }

    public function labels(): array
    {
        return Role::orderBy('slug')->pluck('name', 'slug')->all();
    }

    public function label(string $slug): string
    {
        return Role::where('slug', $slug)->value('name')
            ?? ucfirst(str_replace('_', ' ', $slug));
    }

    public function badgeClasses(string $slug): string
    {
        return match ($slug) {
            'admin' => 'bg-blue-100 text-blue-700 border border-blue-200',
            'ipsrs' => 'bg-green-100 text-green-700 border border-green-200',
            'user' => 'bg-gray-100 text-gray-700 border border-gray-200',
            default => 'bg-indigo-100 text-indigo-700 border border-indigo-200',
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

    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'slug' => $data['slug'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_system' => (bool) ($data['is_system'] ?? false),
            ]);

            $this->syncPermissionsBySlug($role->slug, $data['permission_ids'] ?? []);
            $this->syncCategoriesBySlug($role->slug, $data['kategori_ids'] ?? []);

            return $role->fresh(['permissions', 'kategoris']);
        });
    }

    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $role->update([
                'name' => $data['name'] ?? $role->name,
                'description' => array_key_exists('description', $data) ? $data['description'] : $role->description,
            ]);

            if (array_key_exists('permission_ids', $data)) {
                $this->syncPermissionsBySlug($role->slug, $data['permission_ids'] ?? []);
            }

            if (array_key_exists('kategori_ids', $data)) {
                $this->syncCategoriesBySlug($role->slug, $data['kategori_ids'] ?? []);
            }

            return $role->fresh(['permissions', 'kategoris']);
        });
    }

    public function delete(Role $role): void
    {
        if ($role->is_system) {
            throw new \RuntimeException('Role sistem tidak dapat dihapus.');
        }

        if ($role->users()->exists()) {
            throw new \RuntimeException('Role masih dipakai user dan tidak dapat dihapus.');
        }

        if ($role->roleKategoris()->exists()) {
            throw new \RuntimeException('Role masih ditugaskan ke kategori dan tidak dapat dihapus.');
        }

        DB::transaction(function () use ($role) {
            $role->permissions()->detach();
            $role->kategoris()->detach();
            $role->delete();
        });
    }

    public function syncPermissions(string $slug, array $permissionIds): void
    {
        $this->syncPermissionsBySlug($slug, $permissionIds);
    }

    public function syncCategories(string $slug, array $kategoriIds): void
    {
        $this->syncCategoriesBySlug($slug, $kategoriIds);
    }

    protected function syncPermissionsBySlug(string $slug, array $permissionIds): void
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        $validIds = Permission::whereIn('id', $permissionIds)->pluck('id')->all();

        DB::transaction(function () use ($role, $validIds): void {
            DB::table('role_permissions')->where('role_id', $role->id)->delete();

            foreach ($validIds as $permissionId) {
                DB::table('role_permissions')->insert([
                    'role' => $role->slug,
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    protected function syncCategoriesBySlug(string $slug, array $kategoriIds): void
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        $validIds = KategoriPengajuan::whereIn('id', $kategoriIds)->pluck('id')->all();

        $role->kategoris()->sync($validIds);
    }

    public function slugsForPermission(string $permissionSlug): array
    {
        return DB::table('role_permissions')
            ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('permissions.slug', $permissionSlug)
            ->pluck('roles.slug')
            ->unique()
            ->values()
            ->all();
    }
}
