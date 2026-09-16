<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
        $roles = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->select('role_permissions.role', 'role_permissions.position', 'permissions.slug')
            ->get()
            ->groupBy(function($row){ return $row->role . '|' . ($row->position ?? 'null'); });

        // Build role list
        $roleList = [
            ['role' => 'user', 'position' => null, 'label' => 'User (Regular)'],
            ['role' => 'admin', 'position' => 'IT', 'label' => 'Admin IT'],
            ['role' => 'admin', 'position' => 'Administrasi', 'label' => 'Admin IPSRS / Administrasi Umum'],
        ];

        return view('admin.permissions.index', compact('permissions', 'roles', 'roleList'));
    }

    public function updateRole(Request $request)
    {
        $request->validate([
            'role' => 'required|in:user,admin',
            'position' => 'nullable|string|max:100',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = $request->role;
        $position = $request->position ?: null;
        // Normalize empty string to null for unique constraint
        if ($position === '') $position = null;

        DB::transaction(function() use ($role, $position, $request){
            DB::table('role_permissions')->where('role', $role)->where(function($q) use ($position){
                if (is_null($position)) $q->whereNull('position');
                else $q->where('position', $position);
            })->delete();

            $perms = $request->input('permissions', []);
            foreach ($perms as $permId) {
                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'position' => $position,
                    'permission_id' => $permId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Permission role berhasil diperbarui.');
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $user->permissions()->sync($request->input('permissions', []));

        return back()->with('success', 'Permission user '.$user->name.' berhasil diperbarui.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:permissions,slug',
            'group' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        Permission::create($request->only(['name','slug','group','description']));

        return back()->with('success', 'Permission baru berhasil dibuat.');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return back()->with('success', 'Permission dihapus.');
    }
}
