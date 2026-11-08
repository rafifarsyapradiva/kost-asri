<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('renders custom 404 error page with Warm Biophilic style (BE-054)', function () {
    $response = $this->get('/non-existent-page-url-xyz');

    $response->assertNotFound();
    $response->assertSee('404');
    $response->assertSee('Halaman Tidak Ditemukan');
});

it('renders custom 403 error page (BE-054)', function () {
    Route::get('/test-403', fn () => abort(403, 'Akses dilarang'));

    $response = $this->get('/test-403');
    $response->assertForbidden();
    $response->assertSee('403');
});
