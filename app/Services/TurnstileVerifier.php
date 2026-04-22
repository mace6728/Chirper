<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TurnstileVerifier
{
    public function verify(string $token, ?string $remoteIp = null, ?string $expectedAction = null): ?string
    {
        $secretKey = config('services.turnstile.secret_key');
        $siteverifyUrl = config('services.turnstile.siteverify_url');

        if (! is_string($secretKey) || $secretKey === '' || ! is_string($siteverifyUrl) || $siteverifyUrl === '') {
            report(new RuntimeException('Turnstile is not configured.'));

            return 'Verification is temporarily unavailable. Please try again later.';
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(10)
                ->post($siteverifyUrl, array_filter([
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ], static fn (mixed $field): bool => is_string($field) ? $field !== '' : $field !== null));
        } catch (ConnectionException $exception) {
            report($exception);

            return 'Verification could not be completed. Please try again.';
        }

        if (! $response->successful()) {
            report(new RuntimeException('Turnstile siteverify request failed with status '.$response->status().'.'));

            return 'Verification could not be completed. Please try again.';
        }

        $payload = $response->json();

        if (! is_array($payload) || ($payload['success'] ?? false) !== true) {
            return $this->failureMessage($payload);
        }

        if ($expectedAction !== null && ($payload['action'] ?? null) !== $expectedAction) {
            return 'Verification failed. Please try again.';
        }

        return null;
    }

    protected function failureMessage(mixed $payload): string
    {
        if (! is_array($payload)) {
            return 'Verification failed. Please try again.';
        }

        $errorCodes = $payload['error-codes'] ?? [];

        if (! is_array($errorCodes)) {
            return 'Verification failed. Please try again.';
        }

        if (in_array('missing-input-response', $errorCodes, true)) {
            return 'Please complete the verification challenge.';
        }

        if (in_array('timeout-or-duplicate', $errorCodes, true)) {
            return 'Verification expired. Please try again.';
        }

        return 'Verification failed. Please try again.';
    }
}
