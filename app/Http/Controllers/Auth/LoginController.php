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
            'login' => 'required|string',
            'password' => 'required',
        ]);

        $login = trim($request->input('login'));
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;

        // Backwards compat: support old field name 'email'
        if (empty($login) && $request->filled('email')) {
            $login = trim($request->input('email'));
            $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;
        }

        // Check user status before login - search by username OR email
        $userQuery = User::query();
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'username')) {
            $userQuery->where(function ($q) use ($login, $isEmail) {
                if ($isEmail) {
                    $q->where('email', $login)->orWhere('username', $login);
                } else {
                    $q->where('username', $login)->orWhere('email', $login);
                }
            });
        } else {
            $userQuery->where('email', $login);
        }
        $user = $userQuery->first();

        if (! $user) {
            return back()->withErrors([
                'login' => 'Username tidak ditemukan.',
                'email' => 'Username tidak ditemukan.',
            ])->withInput($request->except('password'));
        }

        if ((int) $user->status === 0) {
            return back()->withErrors([
                'login' => 'Akun anda telah dinonaktifkan. Silahkan hubungi administrator.',
                'email' => 'Akun anda telah dinonaktifkan. Silahkan hubungi administrator.',
            ])->withInput($request->except('password'));
        }

        // Determine credentials for Auth::attempt
        $credentials = ['password' => $request->password];
        // Try username first if input is not email and username column exists
        $attempts = [];
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'username')) {
            if ($isEmail) {
                $attempts[] = ['email' => $login, 'password' => $request->password];
                $attempts[] = ['username' => $login, 'password' => $request->password];
            } else {
                $attempts[] = ['username' => $login, 'password' => $request->password];
                $attempts[] = ['email' => $login, 'password' => $request->password];
            }
        } else {
            $attempts[] = ['email' => $login, 'password' => $request->password];
        }

        foreach ($attempts as $cred) {
            if (Auth::attempt($cred)) {
                $request->session()->regenerate();

                return $this->redirectBasedOnRole(Auth::user());
            }
        }

        return back()->withErrors([
            'login' => 'Username atau password salah.',
            'email' => 'Username atau password salah.',
        ])->withInput($request->except('password'));
    }

    protected function redirectBasedOnRole($user)
    {
        if ($user->hasPermission('admin.dashboard')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasPermission('ipsrs.dashboard')) {
            return redirect()->route('admin.ipsrs.dashboard');
        }

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
