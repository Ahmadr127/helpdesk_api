<?php

namespace App\Http\Requests\Api\OrderPerbaikan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:in_progress,tutup,confirmed,rejected',
            'follow_up' => 'required|string',
            'prioritas' => 'sometimes|required|in:RENDAH,SEDANG,TINGGI/URGENT',
            'nama_penanggung_jawab' => 'nullable|string',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
