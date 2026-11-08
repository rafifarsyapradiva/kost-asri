<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

it('returns in-app notifications and marks all as read (BE-052, FR-NTF-05)', function () {
    $user = User::factory()->tenant()->create();

    // Create a database notification
    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\InvoiceIssuedNotification',
        'data' => [
            'title' => 'Tagihan Baru Terbit',
            'message' => 'Tagihan bulan November 2026 telah terbit.',
            'action_url' => '/penyewa/invoices',
        ],
        'read_at' => null,
    ]);

    expect($user->unreadNotifications()->count())->toBe(1);

    // GET /notifications
    $response = $this->actingAs($user)->getJson('/notifications');
    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'type', 'data', 'read_at'],
        ],
        'unread_count',
    ]);
    expect($response->json('unread_count'))->toBe(1);

    // POST /notifications/read-all
    $readAllResponse = $this->actingAs($user)->postJson('/notifications/read-all');
    $readAllResponse->assertOk();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('shares auth, role, unreadNotificationsCount and flash to Inertia props (BE-053)', function () {
    $user = User::factory()->admin()->create(['name' => 'Pak Budi SuperAdmin']);

    $response = $this->actingAs($user)
        ->withSession(['success' => 'Operasi sukses dilakukan!'])
        ->get('/admin/dashboard');

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
        ->where('auth.user.id', $user->id)
        ->where('auth.user.name', 'Pak Budi SuperAdmin')
        ->where('auth.role', 'admin')
        ->where('flash.success', 'Operasi sukses dilakukan!')
        ->has('unreadNotificationsCount')
    );
});
