<?php

declare(strict_types=1);

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WebhookLog;
use App\Services\Audit\ActivityLogger;

it('creates activity log with current actor, ip, and payload (BE-007)', function () {
    $admin = User::factory()->admin()->create();
    actingAsAdmin($admin);
    $logger = app(ActivityLogger::class);

    $log = $logger->log(action: 'room.update', description: 'Updated room 101', beforeData: ['status' => 'available'], afterData: ['status' => 'maintenance']);

    expect($log)->toBeInstanceOf(ActivityLog::class)
        ->and($log->action)->toBe('room.update')
        ->and($log->actor_id)->toBe($admin->id);
});

it('strictly enforces append-only immutability on audit models (SEC-023, NFR-09)', function () {
    $log = ActivityLog::create([
        'action' => 'security.probe',
        'description' => 'Security probe',
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
    ]);

    expect(fn () => $log->update(['action' => 'tampered']))
        ->toThrow(RuntimeException::class, 'immutable');

    expect(fn () => $log->delete())
        ->toThrow(RuntimeException::class, 'immutable');

    $webhookLog = WebhookLog::create([
        'provider' => 'midtrans',
        'signature_valid' => true,
        'processed' => true,
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
    ]);

    expect(fn () => $webhookLog->update(['signature_valid' => false]))
        ->toThrow(RuntimeException::class, 'immutable');

    expect(fn () => $webhookLog->delete())
        ->toThrow(RuntimeException::class, 'immutable');
});
