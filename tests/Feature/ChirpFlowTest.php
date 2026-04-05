<?php

use App\Models\Chirp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
test('guests cannot create chirps', function (): void {
    /** @var \Tests\TestCase $this */
    $response = $this->post('/chirps', [
        'message' => 'Guest chirp',
    ]);

    $response->assertRedirect('/login');
    $this->assertGuest();
});

test('authenticated users can create chirps', function (): void {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/chirps', [
        'message' => 'My first chirp',
    ]);

    $response->assertRedirect('/');
    $this->assertDatabaseHas('chirps', [
        'user_id' => $user->id,
        'message' => 'My first chirp',
    ]);
});

test('chirp creation requires a message', function (): void {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from('/')
        ->post('/chirps', [
            'message' => '',
        ]);

    $response->assertRedirect('/');
    $response->assertInvalid(['message']);
});

test('chirp creation rejects messages longer than 255 characters', function (): void {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from('/')
        ->post('/chirps', [
            'message' => str_repeat('a', 256),
        ]);

    $response->assertRedirect('/');
    $response->assertInvalid(['message']);
});

test('owners can update their chirps', function (): void {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create();
    $chirp = Chirp::factory()->for($user)->create([
        'message' => 'Original message',
    ]);

    $response = $this->actingAs($user)->put('/chirps/'.$chirp->id, [
        'message' => 'Updated message',
    ]);

    $response->assertRedirect('/');
    $this->assertDatabaseHas('chirps', [
        'id' => $chirp->id,
        'message' => 'Updated message',
    ]);
});

test('chirp update requires a message', function (): void {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create();
    $chirp = Chirp::factory()->for($user)->create([
        'message' => 'Original message',
    ]);

    $response = $this->actingAs($user)
        ->from('/chirps/'.$chirp->id.'/edit')
        ->put('/chirps/'.$chirp->id, [
            'message' => '',
        ]);

    $response->assertRedirect('/chirps/'.$chirp->id.'/edit');
    $response->assertInvalid(['message']);
});
