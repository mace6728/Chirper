<?php

use App\Models\User;
use App\Services\TurnstileVerifier;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

beforeEach(function (): void {
    config()->set('services.turnstile.site_key', '1x00000000000000000000AA');
    config()->set('services.turnstile.secret_key', '1x0000000000000000000000000000000AA');

    fakeTurnstileVerifier();
});

test('a guest can register and is authenticated', function (): void {
    /** @var \Tests\TestCase $this */
    $response = $this->post('/register', withTurnstile([
        'name' => 'Alice Example',
        'email' => 'alice@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], 'register'));

    $response->assertRedirect('/');

    $this->assertAuthenticated();
    expect(User::query()->where('email', 'alice@example.com')->exists())->toBeTrue();
});

test('registration rejects duplicate emails', function (): void {
    /** @var \Tests\TestCase $this */
    User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $response = $this->from('/register')->post('/register', withTurnstile([
        'name' => 'Other User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], 'register'));

    $response->assertRedirect('/register');
    $response->assertInvalid(['email']);
    $this->assertGuest();
});

test('a user can login with valid credentials', function (): void {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'password' => 'password123',
    ]);

    $response = $this->post('/login', withTurnstile([
        'email' => $user->email,
        'password' => 'password123',
    ], 'login'));

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});

test('login fails with invalid credentials', function (): void {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'password' => 'password123',
    ]);

    $response = $this->from('/login')->post('/login', withTurnstile([
        'email' => $user->email,
        'password' => 'wrong-password',
    ], 'login'));

    $response->assertRedirect('/login');
    $response->assertInvalid(['email']);
    $this->assertGuest();
});

test('guest auth forms require a turnstile token', function (string $previousUrl, string $endpoint, array $payload): void {
    /** @var \Tests\TestCase $this */
    $response = $this->from($previousUrl)->post($endpoint, $payload);

    $response->assertRedirect($previousUrl);
    $response->assertInvalid(['cf-turnstile-response']);
    $this->assertGuest();
})->with([
    'login' => ['/login', '/login', [
        'email' => 'guest@example.com',
        'password' => 'password123',
    ]],
    'register' => ['/register', '/register', [
        'name' => 'Guest User',
        'email' => 'guest@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]],
]);

test('guest auth forms reject failed turnstile verification', function (string $previousUrl, string $endpoint, array $payload, string $action): void {
    /** @var \Tests\TestCase $this */
    fakeTurnstileVerifier('Verification failed. Please try again.');

    if ($action === 'login') {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $payload['email'] = $user->email;
    }

    $response = $this->from($previousUrl)->post($endpoint, withTurnstile($payload, $action));

    $response->assertRedirect($previousUrl);
    $response->assertInvalid(['cf-turnstile-response']);
    $this->assertGuest();
})->with([
    'login' => ['/login', '/login', [
        'email' => 'guest@example.com',
        'password' => 'password123',
    ], 'login'],
    'register' => ['/register', '/register', [
        'name' => 'Guest User',
        'email' => 'guest@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], 'register'],
]);

test('logout invalidates the session and rotates csrf token', function (): void {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user);
    $this->startSession();

    $previousToken = session()->token();

    $response = $this->withSession([
        'custom_state' => 'active',
    ])->post('/logout');

    $response->assertRedirect('/');
    $response->assertSessionMissing('custom_state');

    $this->assertGuest();
    expect(session()->token())->not->toBe($previousToken);
});

function withTurnstile(array $payload, string $action): array
{
    return [
        ...$payload,
        'cf-turnstile-response' => turnstileToken($action),
    ];
}

function turnstileToken(string $action): string
{
    return $action.'-turnstile-token';
}

function fakeTurnstileVerifier(?string $message = null): MockInterface
{
    return mock(TurnstileVerifier::class, function (MockInterface $mock) use ($message): void {
        $mock->shouldReceive('verify')
            ->andReturn($message);
    });
}
