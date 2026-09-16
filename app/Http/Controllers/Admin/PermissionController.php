<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\SyncRolePermissionsRequest;
use App\Http\Requests\Admin\SyncUserPermissionsRequest;
use App\Models\Permission;
use App\Models\User;
use App\Services\Access\PermissionService;
use App\Services\Access\RoleService;

class PermissionController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected RoleService $roles,
    ) {}

    public function index()
    {
        $grouped = $this->permissions->grouped();
        $roles = $this->roles->all()->map(fn ($role) => [
            'slug' => $role->slug,
            'label' => $role->name ?: $this->roles->label($role->slug),
            'assigned' => $this->permissions->slugsForRole($role->slug),
        ]);

        return view('admin.permissions.index', [
            'permissions' => $grouped,
            'roles' => $roles,
        ]);
    }

    public function updateRole(SyncRolePermissionsRequest $request)
    {
        $this->permissions->syncRole(
            $request->validated()['role'],
            $request->validated()['permissions'] ?? []
        );

        return back()->with('success', 'Permission role berhasil diperbarui.');
    }

    public function updateUser(SyncUserPermissionsRequest $request, User $user)
    {
        $this->permissions->syncUser($user, $request->validated()['permissions'] ?? []);

        return back()->with('success', 'Permission user '.$user->name.' berhasil diperbarui.');
    }

    public function store(StorePermissionRequest $request)
    {
        Permission::create($request->validated());

        return back()->with('success', 'Permission baru berhasil dibuat.');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return back()->with('success', 'Permission dihapus.');
    }
}
