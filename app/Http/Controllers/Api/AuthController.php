<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\Api\UserResource;
use App\Services\Api\AuthService;
use Illuminate\Http\Request;

class AuthController extends BaseApiController
{
    public function __construct(protected AuthService $authService){}

    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login($request->validated());
            return $this->success([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ], 'Login berhasil');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validasi gagal', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 401);
        }
    }

    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->authService->register($request->validated());
            return $this->success([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ], 'Registrasi berhasil', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function me(Request $request)
    {
        return $this->success(new UserResource($request->user()), 'User profile');
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());
        return $this->success(null, 'Logout berhasil');
    }

    public function logoutAll(Request $request)
    {
        $this->authService->logoutAll($request->user());
        return $this->success(null, 'Logout dari semua device berhasil');
    }
}
