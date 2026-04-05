<?php

use App\Models\Chirp;
use App\Models\User;

test('users cannot view the edit page for chirps they do not own', function (): void {
    /** @var \Tests\TestCase $this */
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $chirp = Chirp::factory()->for($owner)->create();

    $response = $this->actingAs($intruder)->get('/chirps/'.$chirp->id.'/edit');

    $response->assertForbidden();
});

test('users cannot update chirps they do not own', function (): void {
    /** @var \Tests\TestCase $this */
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $chirp = Chirp::factory()->for($owner)->create([
        'message' => 'Owner message',
    ]);

    $response = $this->actingAs($intruder)->put('/chirps/'.$chirp->id, [
        'message' => 'Intruder message',
    ]);

    $response->assertForbidden();
    expect($chirp->fresh()->message)->toBe('Owner message');
});

test('users can delete their own chirps', function (): void {
    /** @var \Tests\TestCase $this */
    $owner = User::factory()->create();
    $chirp = Chirp::factory()->for($owner)->create();

    $response = $this->actingAs($owner)->delete('/chirps/'.$chirp->id);

    $response->assertRedirect('/');
    $this->assertDatabaseMissing('chirps', [
        'id' => $chirp->id,
    ]);
});

test('users cannot delete chirps they do not own', function (): void {
    /** @var \Tests\TestCase $this */
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $chirp = Chirp::factory()->for($owner)->create();

    $response = $this->actingAs($intruder)->delete('/chirps/'.$chirp->id);

    $response->assertForbidden();
    $this->assertDatabaseHas('chirps', [
        'id' => $chirp->id,
    ]);
});
