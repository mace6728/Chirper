<?php

use App\Models\SocialAccount;
use App\Models\User;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery\MockInterface;

function fakeProviderUser(
    string $id,
    ?string $name = 'OAuth User',
    ?string $nickname = null,
    ?string $email = 'oauth@example.com',
    ?string $avatar = null,
): ProviderUser {
    return \Mockery::mock(ProviderUser::class, function (MockInterface $mock) use ($id, $name, $nickname, $email, $avatar): void {
        $mock->shouldReceive('getId')->andReturn($id);
        $mock->shouldReceive('getName')->andReturn($name);
        $mock->shouldReceive('getNickname')->andReturn($nickname);
        $mock->shouldReceive('getEmail')->andReturn($email);
        $mock->shouldReceive('getAvatar')->andReturn($avatar);
    });
}

test('oauth callback creates a new user and social account', function (): void {
    /** @var \Tests\TestCase $this */
    Socialite::shouldReceive('driver->user')
        ->once()
        ->andReturn(fakeProviderUser(
            id: 'google-123',
            name: 'Alice Google',
            email: 'alice@example.com',
        ));

    $response = $this->get(route('oauth.callback', ['provider' => 'google']));

    $response->assertRedirect('/');
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'alice@example.com')->first();

    expect($user)->not->toBeNull();

    expect(SocialAccount::query()
        ->where('user_id', $user->id)
        ->where('provider', 'google')
        ->where('provider_user_id', 'google-123')
        ->exists())->toBeTrue();
});

test('oauth callback logs in using an existing linked account', function (): void {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'email' => 'linked@example.com',
    ]);

    SocialAccount::query()->create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_user_id' => 'google-linked',
    ]);

    Socialite::shouldReceive('driver->user')
        ->once()
        ->andReturn(fakeProviderUser(
            id: 'google-linked',
            name: 'Linked User',
            email: 'linked@example.com',
        ));

    $response = $this->get(route('oauth.callback', ['provider' => 'google']));

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
    expect(SocialAccount::query()->count())->toBe(1);
});

test('oauth callback auto links an existing account with the same email', function (): void {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'email' => 'same-email@example.com',
    ]);

    Socialite::shouldReceive('driver->user')
        ->once()
        ->andReturn(fakeProviderUser(
            id: 'github-987',
            name: 'GitHub User',
            email: 'same-email@example.com',
        ));

    $response = $this->get(route('oauth.callback', ['provider' => 'github']));

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);

    expect(SocialAccount::query()
        ->where('user_id', $user->id)
        ->where('provider', 'github')
        ->where('provider_user_id', 'github-987')
        ->exists())->toBeTrue();
});

test('oauth routes reject unsupported providers', function (): void {
    /** @var \Tests\TestCase $this */
    $this->get('/auth/discord/redirect')->assertNotFound();
    $this->get('/auth/discord/callback')->assertNotFound();
});

test('oauth callback handles access denial gracefully', function (): void {
    /** @var \Tests\TestCase $this */
    $response = $this->get(route('oauth.callback', [
        'provider' => 'google',
        'error' => 'access_denied',
    ]));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('oauth');
    $this->assertGuest();
});
