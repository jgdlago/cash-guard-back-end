<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'password_confirmation' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'max:10', Rule::in(['pt-BR', 'en-US'])],
            'timezone' => ['nullable', 'timezone'],
            'currency_code' => ['nullable', 'string', 'size:3', 'uppercase'],
        ];
    }
}
