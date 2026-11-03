<?php

namespace App\Http\Requests\Api\Ticket;

class UpdateTicketSubmissionRequest extends UpdateTicketRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'penanggung_jawab_ids' => 'nullable|array',
            'penanggung_jawab_ids.*' => 'exists:users,id',
        ]);
    }
}
