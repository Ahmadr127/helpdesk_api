<?php

namespace App\Http\Requests\Api\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:open,pending,in_progress,closed',
        ];
    }
}
