<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidTurnstile;
use App\Services\TurnstileVerifier;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(TurnstileVerifier $turnstileVerifier): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'cf-turnstile-response' => ['bail', 'required', 'string', 'max:2048', new ValidTurnstile($turnstileVerifier, $this->ip(), 'login')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cf-turnstile-response.required' => 'Please complete the verification challenge.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cf-turnstile-response' => 'verification challenge',
        ];
    }
}
