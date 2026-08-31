<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(10);
        return view('admin.users_management.index', compact('users'));
    }

    public function create()
    {
        $positions = Position::where('status', true)
            ->orderBy('name')
            ->get()
            ->pluck('name', 'code');
        $departments = Department::where('status', 1)->get();
        return view('admin.users_management.create', compact('positions', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', 'min:3'],
            'role' => 'required|in:admin,user',
            'department' => 'required|exists:departments,code',
            'status' => 'required|boolean',
            'phone' => 'required|string|max:20',
            'position' => 'required|exists:positions,code',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'department' => $validated['department'],
            'status' => (int)$request->status,
            'phone' => $request->phone,
            'position' => $request->position,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $positions = Position::where('status', true)
            ->orderBy('name')
            ->get()
            ->pluck('name', 'code');
        $departments = Department::where('status', 1)->get();
        return view('admin.users_management.edit', compact('user', 'positions', 'departments'));
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|in:admin,user',
            'department' => 'required|exists:departments,code',
            'status' => 'required|boolean',
            'phone' => 'required|string|max:20',
            'position' => 'required|exists:positions,code',
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'string', 'min:3', 'confirmed'];
        }

        $validated = $request->validate($rules);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'department' => $validated['department'],
            'status' => (int)$validated['status'],
            'phone' => $validated['phone'],
            'position' => $validated['position']
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($validated['password'])
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}