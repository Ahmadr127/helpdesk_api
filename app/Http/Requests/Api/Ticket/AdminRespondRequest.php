<?php

namespace App\Http\Requests\Api\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class AdminRespondRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'notes' => 'required|string',
            'photo' => 'nullable|image|max:5120',
            'status' => 'required|in:in_progress,closed',
        ];
    }
}
