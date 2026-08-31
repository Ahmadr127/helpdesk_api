<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:15',
            'position' => 'required|string|max:255',
            'role' => 'required|in:admin,user',
            'status' => 'required|boolean',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
} 