<?php

namespace App\Http\Requests\Firebase;

use Illuminate\Foundation\Http\FormRequest;

class SendFirebaseNotificationManyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tokens' => ['required', 'array', 'min:1', 'max:500'],
            'tokens.*' => ['required', 'string', 'min:10'],
            'notification' => ['required', 'array'],
            'notification.title' => ['required', 'string', 'max:200'],
            'notification.body' => ['required', 'string', 'max:1000'],
            'notification.image' => ['nullable', 'url'],
            'notification.sound' => ['nullable', 'string', 'max:50'],
            'notification.channel_id' => ['nullable', 'string', 'max:100'],
            'notification.channelId' => ['nullable', 'string', 'max:100'],
            'data' => ['nullable', 'array'],
            'data.*' => ['nullable'],
            'queue' => ['nullable', 'boolean'],
        ];
    }
}
