<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\LeaseStatus;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('prunes KTP photo for alumni tenant checked out over 1 year ago without arrears (SEC-013)', function () {
    Storage::fake('private');

    $alumni = User::factory()->create(['role' => 'calon_penyewa']);
    $ktpFilePath = 'ktp/test-alumni-ktp.jpg';
    Storage::disk('private')->put($ktpFilePath, 'fake-ktp-content');

    $profile = TenantProfile::factory()->create([
        'user_id' => $alumni->id,
        'ktp_photo_path' => $ktpFilePath,
    ]);

    // Ended lease 400 days ago
    Lease::factory()->create([
        'user_id' => $alumni->id,
        'status' => LeaseStatus::Ended,
        'checked_out_at' => now()->subDays(400),
    ]);

    $this->artisan('privacy:prune-expired-ktp')
        ->expectsOutputToContain('1 foto KTP alumni berhasil dihapus')
        ->assertSuccessful();

    Storage::disk('private')->assertMissing($ktpFilePath);
    expect($profile->fresh()->ktp_photo_path)->toBeNull();
});

it('does not prune KTP photo for alumni checked out less than 1 year ago (SEC-013)', function () {
    Storage::fake('private');

    $recentAlumni = User::factory()->create(['role' => 'calon_penyewa']);
    $ktpFilePath = 'ktp/recent-ktp.jpg';
    Storage::disk('private')->put($ktpFilePath, 'fake-ktp-content');

    $profile = TenantProfile::factory()->create([
        'user_id' => $recentAlumni->id,
        'ktp_photo_path' => $ktpFilePath,
    ]);

    // Ended lease 200 days ago (< 365 days)
    Lease::factory()->create([
        'user_id' => $recentAlumni->id,
        'status' => LeaseStatus::Ended,
        'checked_out_at' => now()->subDays(200),
    ]);

    $this->artisan('privacy:prune-expired-ktp')
        ->expectsOutputToContain('0 foto KTP alumni berhasil dihapus')
        ->assertSuccessful();

    Storage::disk('private')->assertExists($ktpFilePath);
    expect($profile->fresh()->ktp_photo_path)->toBe($ktpFilePath);
});

it('does not prune KTP photo if alumni still has unpaid invoices (SEC-013)', function () {
    Storage::fake('private');

    $debtorAlumni = User::factory()->create(['role' => 'calon_penyewa']);
    $ktpFilePath = 'ktp/debtor-ktp.jpg';
    Storage::disk('private')->put($ktpFilePath, 'fake-ktp-content');

    $profile = TenantProfile::factory()->create([
        'user_id' => $debtorAlumni->id,
        'ktp_photo_path' => $ktpFilePath,
    ]);

    $lease = Lease::factory()->create([
        'user_id' => $debtorAlumni->id,
        'status' => LeaseStatus::Ended,
        'checked_out_at' => now()->subDays(400),
    ]);

    // Unpaid invoice
    Invoice::factory()->create([
        'user_id' => $debtorAlumni->id,
        'lease_id' => $lease->id,
        'status' => InvoiceStatus::Unpaid,
        'total' => 500_000,
    ]);

    $this->artisan('privacy:prune-expired-ktp')
        ->expectsOutputToContain('0 foto KTP alumni berhasil dihapus')
        ->assertSuccessful();

    Storage::disk('private')->assertExists($ktpFilePath);
    expect($profile->fresh()->ktp_photo_path)->toBe($ktpFilePath);
});
