<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255|unique:users,email',
            'password' => ['required','string','confirmed','min:3'],
            'password_confirmation' => 'required|string',
            'role' => 'required|in:admin,user',
            'department' => 'required|exists:departments,code',
            'status' => 'required|boolean',
            'phone' => 'required|string|max:20',
            'position' => 'required|exists:positions,code',
        ];
    }
}
