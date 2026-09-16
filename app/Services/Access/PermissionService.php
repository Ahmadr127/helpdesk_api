<?php

namespace App\Services\Access;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    public function grouped(): Collection
    {
        return Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
    }

    public function slugsForRole(string $role): array
    {
        return DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role', $role)
            ->pluck('permissions.slug')
            ->all();
    }

    public function groupedWithRoleFlags(string $role): Collection
    {
        $assigned = $this->slugsForRole($role);

        return $this->grouped()->map(fn (Collection $perms) => $perms->map(fn (Permission $perm) => [
            'permission' => $perm,
            'assigned' => in_array($perm->slug, $assigned, true),
        ]));
    }

    public function syncRole(string $role, array $permissionIds): void
    {
        app(RoleService::class)->syncPermissions($role, $permissionIds);
    }

    public function syncUser(User $user, array $permissionIds): void
    {
        $validIds = Permission::whereIn('id', $permissionIds)->pluck('id')->all();
        $user->permissions()->sync($validIds);
    }
}
