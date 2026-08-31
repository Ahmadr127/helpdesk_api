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
            'email' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:3|confirmed',
            'phone' => 'required|string|max:255',
            'position' => 'required|exists:positions,code',
        ]);

        $user = User::create([
            'name' => $request->name,
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