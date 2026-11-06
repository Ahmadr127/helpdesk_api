<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('permission') ?? false;
    }

    public function rules(): array
    {
        $permission = $this->route('permission');

        return [
            'name' => 'required|string|max:100',
            'slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('permissions', 'slug')->ignore($permission instanceof \App\Models\Permission ? $permission->id : $permission),
            ],
            'group' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
        ];
    }
}
