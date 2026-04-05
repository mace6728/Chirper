<?php

use App\Models\User;

test('a guest can register and is authenticated', function (): void {
    /** @var \Tests\TestCase $this */
    $response = $this->post('/register', [
        'name' => 'Alice Example',
        'email' => 'alice@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('/');

    $this->assertAuthenticated();
    expect(User::query()->where('email', 'alice@example.com')->exists())->toBeTrue();
});

test('registration rejects duplicate emails', function (): void {
    /** @var \Tests\TestCase $this */
    User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $response = $this->from('/register')->post('/register', [
        'name' => 'Other User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('/register');
    $response->assertInvalid(['email']);
    $this->assertGuest();
});

test('a user can login with valid credentials', function (): void {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'password' => 'password123',
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});

test('login fails with invalid credentials', function (): void {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'password' => 'password123',
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect('/login');
    $response->assertInvalid(['email']);
    $this->assertGuest();
});

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
