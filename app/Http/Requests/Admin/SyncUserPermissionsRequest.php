<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SyncUserPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('user.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ];
    }
}
