<?php

namespace App\Http\Requests\Api\OrderPerbaikan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderPerbaikanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode_inventaris' => 'nullable|string',
            'nama_barang' => 'nullable|string',
            'kategori_order' => 'nullable|string|exists:kategori_order,name',
            'lokasi' => 'nullable|exists:locations,id',
            'keluhan' => 'required|string',
            'prioritas' => 'required|in:RENDAH,SEDANG,TINGGI/URGENT',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ];
    }
}
