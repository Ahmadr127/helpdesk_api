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
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Username tidak ditemukan.'],
            ]);
        }

        if ((int)$user->status === 0) {
            throw ValidationException::withMessages([
                'email' => ['Akun anda telah dinonaktifkan. Silahkan hubungi administrator.'],
            ]);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
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
        $user = User::create([
            'name' => $data['name'],
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
