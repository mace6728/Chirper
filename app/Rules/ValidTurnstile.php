<?php

namespace App\Rules;

use App\Services\TurnstileVerifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTurnstile implements ValidationRule
{
    public function __construct(
        private readonly TurnstileVerifier $turnstileVerifier,
        private readonly ?string $remoteIp = null,
        private readonly ?string $expectedAction = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('Please complete the verification challenge.');

            return;
        }

        $message = $this->turnstileVerifier->verify($value, $this->remoteIp, $this->expectedAction);

        if ($message !== null) {
            $fail($message);
        }
    }
}
