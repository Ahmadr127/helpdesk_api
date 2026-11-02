<?php

namespace App\Http\Requests\Api\OrderPerbaikan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keluhan' => 'required|string',
            'prioritas' => 'required|in:RENDAH,SEDANG,TINGGI/URGENT',
            'kode_inventaris' => 'nullable|string',
            'nama_barang' => 'nullable|string',
            'kategori_order' => 'nullable|string',
            'lokasi' => 'nullable|exists:locations,id',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'penanggung_jawab_ids' => 'nullable|array',
            'penanggung_jawab_ids.*' => 'exists:users,id',
        ];
    }
}
