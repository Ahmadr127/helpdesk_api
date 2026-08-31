<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('id');
        return [
            'name' => 'required|string|max:255',
            'email' => ['required','string','max:255', Rule::unique('users')->ignore($userId)],
            'role' => 'required|in:admin,user',
            'department' => 'required|exists:departments,code',
            'status' => 'required|boolean',
            'phone' => 'required|string|max:20',
            'position' => 'required|exists:positions,code',
            'password' => ['nullable','string','min:3','confirmed'],
        ];
    }
}
