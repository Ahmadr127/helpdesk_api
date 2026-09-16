<?php

namespace App\Services\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(array $credentials): array
    {
        $login = $credentials['login'] ?? $credentials['email'] ?? $credentials['username'] ?? null;
        if (!$login) {
            throw ValidationException::withMessages(['login' => ['Username / email wajib diisi.']]);
        }
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;
        $query = User::query();
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'username')) {
            $query->where(function($q) use ($login, $isEmail){
                if ($isEmail) {
                    $q->where('email', $login)->orWhere('username', $login);
                } else {
                    $q->where('username', $login)->orWhere('email', $login);
                }
            });
        } else {
            $query->where('email', $login);
        }
        $user = $query->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'login' => ['Username tidak ditemukan.'],
                'email' => ['Username tidak ditemukan.'],
            ]);
        }

        if ((int)$user->status === 0) {
            throw ValidationException::withMessages([
                'login' => ['Akun anda telah dinonaktifkan. Silahkan hubungi administrator.'],
                'email' => ['Akun anda telah dinonaktifkan. Silahkan hubungi administrator.'],
            ]);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Username atau password salah.'],
                'email' => ['Username atau password salah.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function register(array $data): array
    {
        // Auto-generate username if not provided
        $username = $data['username'] ?? null;
        if (empty($username) && !empty($data['email'])) {
            $base = explode('@', $data['email'])[0];
            $base = preg_replace('/[^A-Za-z0-9._-]/', '', strtolower($base));
            $username = $base;
            $i=1;
            while (User::where('username', $username)->exists()) {
                $username = $base.$i++;
            }
        }
        $user = User::create([
            'name' => $data['name'],
            'username' => $username,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'position' => $data['position'] ?? 'user',
            'role' => $data['role'] ?? 'user',
            'department' => $data['department'] ?? null,
            'status' => 1,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        } else {
            // Fallback: delete latest token if current not set (e.g., testing edge)
            $user->tokens()->latest()->limit(1)->delete();
        }
    }

    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
    }
}
