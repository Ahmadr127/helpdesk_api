<?php

namespace App\Http\Requests\Api\OrderPerbaikan;

use App\Rules\KategoriOrderAllowed;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderPerbaikanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_proses_code' => [
                'nullable', 'exists:unit_proses,code',
                function ($attribute, $value, $fail) {
                    if ($value === 'SIRS') {
                        $fail('Unit proses SIRS tidak dapat dipilih untuk order barang.');
                    }
                },
            ],
            'department_id' => 'nullable|exists:departments,id',

            'kategori_order' => ['nullable', 'string', new KategoriOrderAllowed()],
            'lokasi' => 'nullable|exists:locations,id',
            'gedung' => 'nullable|string',
            'keluhan' => 'required|string',
            'prioritas' => 'required|in:RENDAH,SEDANG,TINGGI/URGENT',
            'tanggal' => 'required|date',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
