<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidTurnstile;
use App\Services\TurnstileVerifier;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cf-turnstile-response' => ['bail', 'required', 'string', 'max:2048', new ValidTurnstile($turnstileVerifier, $this->ip(), 'register')],
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
