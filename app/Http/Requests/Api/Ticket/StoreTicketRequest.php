<?php

namespace App\Http\Requests\Api\Ticket;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    $category = Category::with('unitProses')->find($value);
                    if (!$category || !$category->unitProses || $category->unitProses->code !== 'SIRS') {
                        $fail('Kategori yang dipilih harus kategori dari unit SIRS.');
                    }
                }
            ],
            'location_id' => 'required|exists:locations,id',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'photo' => 'nullable|image|max:5120',
        ];
    }
}
