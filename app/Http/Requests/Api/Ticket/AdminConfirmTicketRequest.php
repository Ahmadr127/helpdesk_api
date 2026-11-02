<?php

namespace App\Http\Requests\Api\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class AdminConfirmTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'confirmation_notes' => 'required|string',
        ];
    }
}
