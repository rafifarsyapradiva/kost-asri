<?php

declare(strict_types=1);

test('it provides actingAsAdmin helper authenticating user with admin role', function () {
    actingAsAdmin();

    expect(auth()->check())->toBeTrue()
        ->and(auth()->user()->role->value ?? auth()->user()->role)->toBe('admin');
});

test('it provides actingAsTenant helper authenticating user with penyewa role', function () {
    actingAsTenant();

    expect(auth()->check())->toBeTrue()
        ->and(auth()->user()->role->value ?? auth()->user()->role)->toBe('penyewa');
});

test('it provides actingAsProspect helper authenticating user with calon_penyewa role', function () {
    actingAsProspect();

    expect(auth()->check())->toBeTrue()
        ->and(auth()->user()->role->value ?? auth()->user()->role)->toBe('calon_penyewa');
});
