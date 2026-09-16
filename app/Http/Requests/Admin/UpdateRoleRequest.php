<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('user.manage') ?? false;
    }

    public function rules(): array
    {
        $slug = $this->route('role')?->slug ?? $this->route('slug');

        return [
            'slug' => ['sometimes', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', Rule::unique('roles', 'slug')->ignore($slug, 'slug')],
            'name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string|max:255',
        ];
    }
}
