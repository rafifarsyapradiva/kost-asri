<?php

declare(strict_types=1);

it('applies mandatory security headers on all responses (SEC-021)', function () {
    $response = $this->get('/');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    expect($response->headers->get('Content-Security-Policy'))->toContain('app.sandbox.midtrans.com');
});
