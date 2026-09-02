<?php

namespace App\Http\Requests\Firebase;

use Illuminate\Foundation\Http\FormRequest;

class SendFirebaseNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'min:10'],
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
            // Backward compat: allow title/body at top level? We'll handle in controller, but keep strict here
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'FCM token wajib diisi',
            'notification.title.required' => 'Judul notification wajib diisi',
            'notification.body.required' => 'Body notification wajib diisi',
        ];
    }
}
