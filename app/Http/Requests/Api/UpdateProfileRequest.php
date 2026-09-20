<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;
        return [
            'name' => 'required|string|max:255',
            'email' => ['required','email','max:255', Rule::unique('users','email')->ignore($userId)],
            'phone' => 'nullable|string|max:20',
            'position' => ['nullable','string','max:255', Rule::exists('positions','code')],
            'department' => ['nullable','string','max:255', function ($attr, $val, $fail) {
                if (! \App\Models\Department::where('code', $val)->orWhere('name', $val)->exists()) {
                    $fail('Department tidak valid.');
                }
            }],
        ];
    }
}
