<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| External Webhook Routes (Midtrans & Fonnte)
|--------------------------------------------------------------------------
|
| Loaded under the 'web' group with explicit, narrow CSRF exemptions in
| bootstrap/app.php for ONLY 'webhook/midtrans' and 'webhook/fonnte'
| (SET-017, SEC-022). Dedicated controllers replace these 501 stubs in
| API-006 and API-013.
|
*/

Route::prefix('webhook')
    ->middleware('throttle:120,1')
    ->group(function (): void {
        Route::post('/midtrans', fn () => response()->json([
            'message' => 'Midtrans webhook endpoint registered (pending API-006).',
        ], 501))->name('webhook.midtrans');

        Route::post('/fonnte', fn () => response()->json([
            'ok' => false,
            'message' => 'Fonnte webhook endpoint registered (pending API-013).',
        ], 501))->name('webhook.fonnte');
    });
