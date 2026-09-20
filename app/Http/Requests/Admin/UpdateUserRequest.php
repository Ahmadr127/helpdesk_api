<?php

namespace App\Http\Requests\Admin;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('user.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('position')) {
            $raw = trim((string) $this->input('position'));
            if ($raw !== '') {
                $lower = strtolower($raw);
                $map = ['staff'=>'STAFF','manager'=>'MANAGER','direktur utama'=>'DIR_UT','user'=>'STAFF','admin'=>'STAFF'];
                if (isset($map[$lower])) {
                    $this->merge(['position' => $map[$lower]]);
                } else {
                    // cari by code/name case-insensitive
                    $found = \App\Models\Position::whereRaw('LOWER(code)=?', [$lower])->orWhereRaw('LOWER(name)=?', [$lower])->first();
                    if ($found) $this->merge(['position' => $found->code]);
                    else $this->merge(['position' => strtoupper($raw)]);
                }
            }
        }
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('id');

        $rules = [
            'name' => 'required|string|max:255',
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($userId)],
            'email' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($userId)],
            'role' => ['required', 'string', Rule::exists('roles', 'slug')],
            'department' => ['required', 'string', 'max:255', function ($attr, $val, $fail) {
                if (! Department::where('code', $val)->orWhere('name', $val)->exists()) {
                    $fail('Department tidak valid.');
                }
            }],
            'status' => 'required|boolean',
            'phone' => 'required|string|max:20',
            // Position is display-only, never used for access control.
            'position' => ['nullable', 'string', 'max:255', Rule::exists('positions', 'code')],
        ];

        if ($this->filled('password')) {
            $rules['password'] = ['required', 'string', 'min:3', 'confirmed'];
        }

        return $rules;
    }
}
