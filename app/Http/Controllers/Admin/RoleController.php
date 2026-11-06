<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\KategoriPengajuan;
use App\Models\Role;
use App\Services\Access\PermissionService;
use App\Services\Access\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roles,
        protected PermissionService $permissions,
    ) {}

    public function index()
    {
        return view('admin.roles.index', [
            'roles' => $this->roles->all(),
            'permissions' => $this->permissions->grouped(),
            'kategoris' => KategoriPengajuan::with('jenis')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.roles.create', [
            'permissions' => $this->permissions->grouped(),
            'kategoris' => KategoriPengajuan::with('jenis')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreRoleRequest $request)
    {
        $this->roles->create($request->validated());

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role berhasil dibuat.');
    }

    public function edit(Role $role)
    {
        $role->load(['permissions', 'kategoris']);

        return view('admin.roles.edit', [
            'role' => $role,
            'permissions' => $this->permissions->grouped(),
            'kategoris' => KategoriPengajuan::with('jenis')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $this->roles->update($role, $request->validated());

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        try {
            $this->roles->delete($role);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }
}
