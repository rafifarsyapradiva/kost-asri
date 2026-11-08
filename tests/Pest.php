<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(function (): void {
        Http::preventStrayRequests();
    })
    ->in('Feature');

function actingAsAdmin(?User $user = null): TestCase
{
    /** @var TestCase $test */
    $test = test();

    $user ??= User::factory()->create([
        'role' => 'admin',
    ]);

    return $test->actingAs($user);
}

function actingAsTenant(?User $user = null): TestCase
{
    /** @var TestCase $test */
    $test = test();

    $user ??= User::factory()->create([
        'role' => 'penyewa',
    ]);

    return $test->actingAs($user);
}

function actingAsProspect(?User $user = null): TestCase
{
    /** @var TestCase $test */
    $test = test();

    $user ??= User::factory()->create([
        'role' => 'calon_penyewa',
    ]);

    return $test->actingAs($user);
}
