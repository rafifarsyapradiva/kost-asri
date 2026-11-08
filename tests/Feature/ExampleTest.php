<?php

declare(strict_types=1);

it('returns a successful response from the home route', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
