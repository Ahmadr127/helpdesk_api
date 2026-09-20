<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Services\Access\RoleService;
use App\Services\Api\UserService;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected RoleService $roles,
    ) {}

    public function index(Request $request)
    {
        $users = $this->userService->list(
            $request->only(['search', 'role', 'status', 'department', 'position']),
            10
        )->withQueryString();

        $roles = $this->roles->all();
        $departments = Department::where('status', 1)->orderBy('name')->get();
        $positions = Position::where('status', 1)->orderBy('name')->get();

        return view('admin.users_management.index', compact('users', 'roles', 'departments', 'positions'));
    }

    public function create()
    {
        $roles = $this->roles->all();
        $selectedRole = old('role', 'user');
        $departments = Department::where('status', 1)->get();
        $positions = Position::where('status', 1)->pluck('name', 'code');

        return view('admin.users_management.create', compact('roles', 'selectedRole', 'departments', 'positions'));
    }

    public function store(StoreUserRequest $request)
    {
        $this->userService->create($request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles = $this->roles->all();
        $selectedRole = old('role', $this->roles->exists($user->role) ? $user->role : 'user');
        $departments = Department::where('status', 1)->get();
        $positions = Position::where('status', 1)->pluck('name', 'code');

        return view('admin.users_management.edit', compact('user', 'roles', 'selectedRole', 'departments', 'positions'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->update($user, $request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->userService->delete($user);

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
