<?php

namespace App\Http\Requests\Api\OrderPerbaikan;

use App\Rules\KategoriOrderAllowed;
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
            'keluhan' => 'nullable|string',
            'prioritas' => 'required|in:RENDAH,SEDANG,TINGGI/URGENT',
            'kategori_order' => ['required', 'string', new KategoriOrderAllowed()],
            'lokasi' => 'nullable|exists:locations,id',
            'gedung' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'fotos' => 'nullable|array|max:3',
            'fotos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'penanggung_jawab_ids' => 'nullable|array',
            'penanggung_jawab_ids.*' => 'exists:users,id',
        ];
    }
}
