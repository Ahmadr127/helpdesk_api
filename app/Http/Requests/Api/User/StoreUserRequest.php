<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username',
            'email' => 'required|string|max:255|unique:users,email',
            'password' => ['required', 'string', 'confirmed', 'min:3'],
            'password_confirmation' => 'required|string',
            'role' => ['required', 'string', Rule::exists('roles', 'slug')],
            'department' => ['required', 'string', 'max:255', function ($attr, $val, $fail) {
                if (! \App\Models\Department::where('code', $val)->orWhere('name', $val)->exists()) {
                    $fail('Department tidak valid.');
                }
            }],
            'status' => 'required|boolean',
            'phone' => 'required|string|max:20',
            // Position is display-only, never used for access control.
            'position' => ['nullable', 'string', 'max:255', Rule::exists('positions', 'code')],
        ];
    }
}
