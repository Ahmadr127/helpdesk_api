<?php

namespace App\Http\Requests\Api\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'notes' => 'required|string',
            'photo' => 'nullable|image|max:5120',
            'status' => 'nullable|in:in_progress,closed',
            'action' => 'nullable|in:reply',
        ];
    }
}
