<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => 'sometimes|required|string',
            'email' => 'sometimes|required|string',
            'username' => 'sometimes|required|string',
            'password' => 'required|string',
        ];
    }

    protected function prepareForValidation()
    {
        // Normalize login field: accept login / email / username
        if (! $this->has('login') && $this->has('email')) {
            $this->merge(['login' => $this->input('email')]);
        }
        if (! $this->has('login') && $this->has('username')) {
            $this->merge(['login' => $this->input('username')]);
        }
    }
}
