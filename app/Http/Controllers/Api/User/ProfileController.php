<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\Api\UserResource;
use Illuminate\Support\Facades\Hash;

class ProfileController extends BaseApiController
{
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? $user->phone,
            'position' => $data['position'] ?? $user->position,
            'department' => $data['department'] ?? $user->department,
        ]);
        return $this->success(new UserResource($user->fresh()), 'Profile berhasil diperbarui');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        if (! Hash::check($request->input('current_password'), $user->password)) {
            return $this->error('Password lama tidak sesuai', 422, ['current_password' => ['Password lama tidak sesuai']]);
        }
        $user->update(['password' => Hash::make($request->input('new_password'))]);
        return $this->success(null, 'Password berhasil diubah');
    }
}
