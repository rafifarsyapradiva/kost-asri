<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

it('renders public landing shell on GET / using public Vite entry without Inertia root div (SET-009)', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('ASRI Boarding House')
        ->assertDontSee('data-page=');
});

it('renders Inertia responses with app.blade.php and Ziggy @routes when requested (SET-008)', function () {
    Route::middleware('web')->get('/_setup/inertia-probe', fn () => Inertia::render('Setup/AppShellProbe', [
        'kostName' => 'ASRI BOARDING HOUSE',
    ]));

    $htmlResponse = $this->get('/_setup/inertia-probe');
    $htmlResponse->assertOk()
        ->assertSee('data-page=')
        ->assertSee('Ziggy');

    expect(class_exists(HandleInertiaRequests::class))->toBeTrue();
});
