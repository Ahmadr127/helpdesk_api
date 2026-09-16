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
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            // use ILIKE for pgsql (case-insensitive), LIKE for others
            $like = (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') ? 'ilike' : 'like';
            $query->where(function($q) use ($search, $like){
                $q->where('name',$like,"%{$search}%")
                  ->orWhere('email',$like,"%{$search}%")
                  ->orWhere('phone',$like,"%{$search}%")
                  ->orWhere('department',$like,"%{$search}%")
                  ->orWhere('position',$like,"%{$search}%");
            });
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(10)->withQueryString();
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
            'username' => 'nullable|string|max:255|unique:users,username',
            'email' => 'required|string|max:255|unique:users,email',
            'password' => ['required', 'string', 'confirmed', 'min:3'],
            'role' => 'required|in:admin,user',
            'department' => ['required','string','max:255', function($attr,$val,$fail){
                if (!Department::where('code',$val)->orWhere('name',$val)->exists()) $fail('Department tidak valid.');
            }],
            'status' => 'required|boolean',
            'phone' => 'required|string|max:20',
            'position' => ['required','string','max:255', function($attr,$val,$fail){
                if (empty($val)) $fail('Position wajib diisi.');
            }],
        ]);

        // Auto-generate username if not provided
        $username = $validated['username'] ?? null;
        if (empty($username)) {
            $base = explode('@', $validated['email'])[0];
            $base = preg_replace('/[^A-Za-z0-9._-]/', '', strtolower($base));
            $username = $base;
            $i=1;
            while (User::where('username', $username)->exists()) { $username = $base.$i++; }
        }

        $user = User::create([
            'name' => $validated['name'],
            'username' => $username,
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
            'username' => ['nullable', 'string','max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|in:admin,user',
            'department' => ['required','string','max:255', function($attr,$val,$fail){
                if (!Department::where('code',$val)->orWhere('name',$val)->exists()) $fail('Department tidak valid.');
            }],
            'status' => 'required|boolean',
            'phone' => 'required|string|max:20',
            'position' => ['required','string','max:255', function($attr,$val,$fail){
                if (empty($val)) $fail('Position wajib diisi.');
            }],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'string', 'min:3', 'confirmed'];
        }

        $validated = $request->validate($rules);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'department' => $validated['department'],
            'status' => (int)$validated['status'],
            'phone' => $validated['phone'],
            'position' => $validated['position']
        ];
        if (!empty($validated['username'])) {
            $updateData['username'] = $validated['username'];
        } elseif (empty($user->username)) {
            // auto-generate if still empty
            $base = explode('@', $validated['email'])[0];
            $base = preg_replace('/[^A-Za-z0-9._-]/', '', strtolower($base));
            $username = $base; $i=1;
            while (User::where('username', $username)->where('id','!=',$user->id)->exists()) { $username = $base.$i++; }
            $updateData['username'] = $username;
        }

        $user->update($updateData);

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