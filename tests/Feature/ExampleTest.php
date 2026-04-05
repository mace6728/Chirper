<?php

test('the home page is accessible', function (): void {
    /** @var \Tests\TestCase $this */
    $response = $this->get('/');

    $response->assertSuccessful();
});
