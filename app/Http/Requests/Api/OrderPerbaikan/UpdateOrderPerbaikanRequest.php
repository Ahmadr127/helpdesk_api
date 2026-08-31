<?php

namespace App\Http\Requests\Api\OrderPerbaikan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderPerbaikanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'jenis_barang' => 'required|in:Umum,Inventaris',
            'kode_inventaris' => 'required|string',
            'nama_barang' => 'required|string',
            'lokasi' => 'required|exists:locations,id',
            'keluhan' => 'required|string',
            'prioritas' => 'required|in:RENDAH,SEDANG,TINGGI/URGENT',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ];
    }
}
