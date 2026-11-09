<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('permission') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'permission_ids' => $this->input('permission_ids', []),
            'kategori_ids' => $this->input('kategori_ids', []),
        ]);
    }

    public function rules(): array
    {
        return [
            'slug' => 'required|string|max:50|unique:roles,slug|regex:/^[a-z0-9_]+$/',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
            'kategori_ids' => 'nullable|array',
            'kategori_ids.*' => 'integer|exists:kategori_pengajuan,id',
        ];
    }
}
