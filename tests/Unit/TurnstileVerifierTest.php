<?php

use App\Services\TurnstileVerifier;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    config()->set('services.turnstile.secret_key', 'test-secret');
    config()->set('services.turnstile.siteverify_url', 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
});

test('it accepts a successful verification response', function (): void {
    Http::fake([
        '*turnstile/v0/siteverify*' => Http::response([
            'success' => true,
            'action' => 'login',
        ]),
    ]);

    $verifier = app(TurnstileVerifier::class);

    expect($verifier->verify('token', '127.0.0.1', 'login'))->toBeNull();
});

test('it returns a friendly message for invalid verification responses', function (): void {
    Http::fake([
        '*turnstile/v0/siteverify*' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ]),
    ]);

    $verifier = app(TurnstileVerifier::class);

    expect($verifier->verify('token', '127.0.0.1', 'login'))->toBe('Verification failed. Please try again.');
});

test('it returns an expiry message for duplicate or expired tokens', function (): void {
    Http::fake([
        '*turnstile/v0/siteverify*' => Http::response([
            'success' => false,
            'error-codes' => ['timeout-or-duplicate'],
        ]),
    ]);

    $verifier = app(TurnstileVerifier::class);

    expect($verifier->verify('token', '127.0.0.1', 'login'))->toBe('Verification expired. Please try again.');
});

test('it rejects action mismatches', function (): void {
    Http::fake([
        '*turnstile/v0/siteverify*' => Http::response([
            'success' => true,
            'action' => 'register',
        ]),
    ]);

    $verifier = app(TurnstileVerifier::class);

    expect($verifier->verify('token', '127.0.0.1', 'login'))->toBe('Verification failed. Please try again.');
});
