<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('user.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'slug' => 'required|string|max:50|unique:roles,slug|regex:/^[a-z0-9_]+$/',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ];
    }
}
