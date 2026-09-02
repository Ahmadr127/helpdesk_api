<?php

namespace App\Http\Requests\Firebase;

use Illuminate\Foundation\Http\FormRequest;

class FirebaseTopicSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'regex:/^[a-zA-Z0-9\-_\.~%]+$/', 'max:900'],
            'tokens' => ['required', 'array', 'min:1', 'max:1000'],
            'tokens.*' => ['required', 'string', 'min:10'],
            // Also accept singular token for convenience
            'token' => ['nullable', 'string', 'min:10'],
        ];
    }

    public function getTokens(): array
    {
        if ($this->has('tokens')) {
            return $this->input('tokens');
        }
        if ($this->has('token')) {
            return [$this->input('token')];
        }
        return [];
    }
}
