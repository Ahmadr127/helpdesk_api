<?php

namespace App\Rules;

use App\Models\KategoriOrder;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validasi kategori order: harus ada dan aktif.
 *
 * Assignment user/unit pada kategori TIDAK membatasi siapa yang boleh memakai
 * kategori ini — assignment dipakai untuk menentukan Petugas Penanggung Jawab
 * (PJ) order, bukan untuk menyembunyikan pilihan.
 */
class KategoriOrderAllowed implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $exists = KategoriOrder::query()
            ->where('name', $value)
            ->where('status', 1)
            ->exists();

        if (! $exists) {
            $fail('Kategori order tidak valid atau tidak aktif.');
        }
    }
}
