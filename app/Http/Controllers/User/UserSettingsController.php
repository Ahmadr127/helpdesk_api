<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Department;
use App\Models\Position;

class UserSettingsController extends Controller
{
    public function index()
    {
        $departments = Department::orderBy('name')->get();
        $positions = Position::where('status', true)
            ->orderBy('name')
            ->get()
            ->pluck('name', 'code');
        
        return view('user.settings', [
            'user' => Auth::user(),
            'departments' => $departments,
            'positions' => $positions
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) use ($request) {
                // Check if email is unique except for current user
                if (\App\Models\User::where('email', $value)
                    ->where('id', '!=', Auth::id())
                    ->exists()) {
                    $fail('The email has already been taken.');
                }
            }],
            'phone' => 'required|string|max:15',
            'position' => 'nullable|exists:positions,code',
            'department' => 'nullable|exists:departments,code',
            'new_password' => 'nullable|min:3|confirmed',
        ]);

        $user = Auth::user();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->position = $request->position;
        $user->department = $request->department;

        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }
} 