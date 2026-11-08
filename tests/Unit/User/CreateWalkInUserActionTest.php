<?php

declare(strict_types=1);

use App\Actions\User\CreateWalkInUserAction;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates walk-in user with random password and normalized phone (BE-004, FR-AUTH-06)', function () {
    $action = app(CreateWalkInUserAction::class);
    $result = $action->execute([
        'name' => 'Budi Santoso Walk-in',
        'phone' => '085712345678',
        'email' => null,
    ]);

    expect($result['user'])->toBeInstanceOf(User::class)
        ->and($result['user']->name)->toBe('Budi Santoso Walk-in')
        ->and($result['user']->phone)->toBe('6285712345678')
        ->and($result['user']->email)->toBeNull()
        ->and($result['user']->role)->toBe(UserRole::Prospect)
        ->and(strlen($result['plain_password']))->toBeGreaterThanOrEqual(10)
        ->and(Hash::check($result['plain_password'], $result['user']->password))->toBeTrue();
});
