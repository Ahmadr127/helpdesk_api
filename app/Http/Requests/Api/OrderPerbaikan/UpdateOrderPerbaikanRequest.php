<?php

namespace App\Http\Requests\Api\OrderPerbaikan;

use App\Rules\KategoriOrderAllowed;
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

            'kategori_order' => ['nullable', 'string', new KategoriOrderAllowed()],
            'lokasi' => 'nullable|exists:locations,id',
            'keluhan' => 'required|string',
            'prioritas' => 'required|in:RENDAH,SEDANG,TINGGI/URGENT',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
