<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (auth()->check()) {
            return $this->redirectBasedOnRole(auth()->user());
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required'
        ]);

        $email = $request->email;
        
        // Check user status before login
        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Username tidak ditemukan.',
            ])->withInput($request->except('password'));
        }

        if ($user->status === 0) {
            return back()->withErrors([
                'email' => 'Akun anda telah dinonaktifkan. Silahkan hubungi administrator.',
            ])->withInput($request->except('password'));
        }

        if (Auth::attempt(['email' => $email, 'password' => $request->password])) {
            $request->session()->regenerate();
            
            return $this->redirectBasedOnRole(Auth::user());
        }

        return back()->withErrors([
            'email' => 'Username atau password salah.',
        ])->withInput($request->except('password'));
    }

    protected function redirectBasedOnRole($user)
    {
        \Log::info('User Role: ' . $user->role . ', Position: ' . $user->position);
        
        if ($user->role === 'admin') {
            $position = strtolower($user->position);
            
            if ($position === 'administrasi') {
                \Log::info('Redirecting to administrasi-umum.dashboard');
                return redirect()->route('administrasi-umum.dashboard');
            } elseif ($position === 'it') {
                \Log::info('Redirecting to admin.dashboard');
                return redirect()->route('admin.dashboard');
            } else {
                \Log::info('Admin with invalid position, redirecting to user.dashboard');
                return redirect()->route('user.dashboard')
                    ->with('error', 'Posisi admin tidak valid. Silakan hubungi administrator.');
            }
        }
        
        \Log::info('Redirecting to user.dashboard');
        return redirect()->route('user.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
} 