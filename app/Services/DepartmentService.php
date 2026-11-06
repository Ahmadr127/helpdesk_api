<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;

class DepartmentService
{
    /**
     * Resolve departemen user: utamakan department_id, lalu fallback lookup
     * code/name (case-insensitive). Bila $createIfMissing true dan masih tidak
     * ketemu, buat departemen baru dari string department user.
     */
    public function resolveForUser(?User $user, bool $createIfMissing = false): ?Department
    {
        if (! $user) {
            return null;
        }

        if (! empty($user->department_id)) {
            $department = Department::with(['location', 'building'])->find($user->department_id);
            if ($department) {
                return $department;
            }
        }

        $value = $user->department;

        if (! $value) {
            return null;
        }

        $department = Department::with(['location', 'building'])->where('code', $value)->first()
            ?? Department::with(['location', 'building'])->where('name', $value)->first()
            ?? Department::with(['location', 'building'])->whereRaw('LOWER(code) = LOWER(?)', [$value])->first()
            ?? Department::with(['location', 'building'])->whereRaw('LOWER(name) = LOWER(?)', [$value])->first();

        if ($department) {
            return $department;
        }

        if (! $createIfMissing) {
            return null;
        }

        try {
            return Department::create([
                'code' => strtoupper(str_replace(' ', '_', trim($value))),
                'name' => trim($value),
                'status' => 1,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('DepartmentService auto-create failed for value '.$value.': '.$e->getMessage());

            return null;
        }
    }
}
