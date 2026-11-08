<?php

declare(strict_types=1);

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('renders settings page with operational parameters and masked credentials (BE-049, SEC-024, FR-DASH-04)', function () {
    $admin = User::factory()->admin()->create();

    config([
        'services.midtrans.server_key' => 'SB-Mid-server-REALSECRETKEY1234',
        'services.fonnte.token' => 'FONNTETOKEN9999',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.settings.index'));

    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page
        ->component('Admin/Settings/Index')
        ->has('settings')
        ->has(
            'credentials',
            fn (Assert $creds) => $creds
            ->where('midtrans_server_key', '****1234')
            ->where('fonnte_token', '****9999')
            ->etc()
        )
    );

    // Verify raw secret was NOT leaked
    expect($response->content())->not->toContain('REALSECRETKEY1234')
        ->and($response->content())->not->toContain('FONNTETOKEN9999');
});

it('updates operational system parameters and logs admin activity (BE-049, FR-DASH-05)', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.settings.update'), [
            'penalty_percent' => 10,
            'dp_percent' => 30,
            'due_date_monthly' => 10,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect((int) setting('penalty_percent'))->toBe(10)
        ->and((int) setting('dp_percent'))->toBe(30);

    // Check activity log recorded
    expect(ActivityLog::where('actor_id', $admin->id)->exists())->toBeTrue();
});

it('lists activity logs in reverse chronological order (BE-049, FR-DASH-05)', function () {
    $admin = User::factory()->admin()->create();

    ActivityLog::create([
        'actor_id' => $admin->id,
        'action' => 'setting_updated',
        'description' => 'Memperbarui parameter sistem',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.activity-logs.index'));

    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page
        ->component('Admin/ActivityLogs/Index')
        ->has('logs.data', 1)
    );
});
