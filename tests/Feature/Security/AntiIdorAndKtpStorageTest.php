<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('prevents tenant A from accessing tenant B reservation or invoice (BE-008, SEC-008, TST-017)', function () {
    $tenantA = User::factory()->tenant()->create();
    $tenantB = User::factory()->tenant()->create();
    $reservationB = Reservation::factory()->for($tenantB)->create();
    $invoiceB = Invoice::factory()->for($tenantB)->create();

    $this->actingAs($tenantA)->get("/reservasi/{$reservationB->code}/selesai")->assertForbidden();
    $this->actingAs($tenantA)->get("/invoices/{$invoiceB->id}/pdf")->assertForbidden();
});

it('stores KTP photo with UUID in private disk and streams only to owner or admin (BE-005, SEC-010, TST-017)', function () {
    Storage::fake('local');
    $user = User::factory()->tenant()->create();
    $otherUser = User::factory()->tenant()->create();

    $file = UploadedFile::fake()->image('ktp.jpg', 800, 600)->size(1500);

    $this->actingAs($user)->post('/akun/profil', [
        'nik' => '3374123456780001',
        'ktp_photo' => $file,
        'gender' => 'male',
        'birth_date' => '2000-01-01',
        'origin_address' => 'Jl. Asri No. 12',
    ])->assertRedirect();

    $profile = $user->fresh()->tenantProfile;
    expect($profile)->not->toBeNull()
        ->and($profile->ktp_photo_path)->toStartWith('ktp/')
        ->and($profile->ktp_photo_path)->toEndWith('.webp');

    // Tenant B cannot stream Tenant A's KTP
    $this->actingAs($otherUser)->get("/private/ktp/{$profile->id}")->assertForbidden();

    // Owner and Admin can stream with no-store cache control
    $resOwner = $this->actingAs($user)->get("/private/ktp/{$profile->id}");
    $resOwner->assertOk();
    expect($resOwner->headers->get('Cache-Control'))->toContain('no-store')
        ->and($resOwner->headers->get('Cache-Control'))->toContain('private');

    $this->actingAs(User::factory()->admin()->create())
        ->get("/private/ktp/{$profile->id}")->assertOk();
});
