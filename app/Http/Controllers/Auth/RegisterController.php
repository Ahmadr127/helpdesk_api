<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        $positions = Position::where('status', true)
            ->orderBy('name')
            ->get()
            ->pluck('name', 'code');
            
        return view('auth.register', compact('positions'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username',
            'email' => 'required|string|max:255|unique:users,email',
            'password' => 'required|string|min:3|confirmed',
            'phone' => 'required|string|max:255',
            'position' => 'required|exists:positions,code',
        ]);

        // Generate username if not provided: from email prefix
        $username = $request->username;
        if (empty($username)) {
            $base = explode('@', $request->email)[0];
            $base = preg_replace('/[^A-Za-z0-9._-]/', '', strtolower($base));
            $username = $base;
            $i=1;
            while (User::where('username', $username)->exists()) {
                $username = $base.$i++;
            }
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'position' => $request->position,
            'department_id' => 1,  // Set default department
            'role' => 'user',      // Set default role
            'status' => 1          // Set active by default (1)
        ]);

        auth()->login($user);

        return redirect()->route('user.dashboard');
    }
} 