<?php

namespace App\Http\Requests\Firebase;

use Illuminate\Foundation\Http\FormRequest;

class SendFirebaseTopicNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'regex:/^[a-zA-Z0-9\-_\.~%]+$/', 'max:900'],
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
