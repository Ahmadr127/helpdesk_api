<?php

namespace App\Http\Requests\Api\OrderPerbaikan;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderPerbaikanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'unit_proses_code' => [
                'required','exists:unit_proses,code',
                function($attribute,$value,$fail){
                    if ($value === 'SIRS') $fail('Unit proses SIRS tidak dapat dipilih untuk order barang.');
                }
            ],
            'jenis_barang' => 'required|in:Umum,Inventaris',
            'kode_inventaris' => 'required|string',
            'nama_barang' => 'required|string',
            'lokasi' => 'required|exists:locations,id',
            'keluhan' => 'required|string',
            'prioritas' => 'required|in:RENDAH,SEDANG,TINGGI/URGENT',
            'tanggal' => 'required|date',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ];
    }
}
