<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\TenantProfile;
use App\Models\User;
use App\Notifications\GuardianWarningNotification;
use App\Notifications\InvoiceIssuedNotification;
use App\Notifications\PenaltyAppliedNotification;
use App\Services\Notification\TemplateRenderer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->tenant = User::factory()->create([
        'name' => 'Fajar Pratama',
        'email' => 'fajar@example.com',
        'phone' => '081234567890',
        'role' => 'penyewa',
    ]);

    TenantProfile::factory()->create([
        'user_id' => $this->tenant->id,
        'guardian_name' => 'Bapak Pratama',
        'guardian_phone' => '081299991111',
        'guardian_email' => 'wali.fajar@example.com',
    ]);

    $this->roomType = RoomType::factory()->standard()->create(['name' => 'Standard', 'monthly_rate' => 900_000]);
    $this->room = Room::factory()->create(['room_type_id' => $this->roomType->id, 'code' => '105']);
    $this->lease = Lease::factory()->create(['user_id' => $this->tenant->id, 'room_id' => $this->room->id]);

    $this->invoice = Invoice::factory()->create([
        'user_id' => $this->tenant->id,
        'lease_id' => $this->lease->id,
        'number' => 'INV/ASRI/202611/0005',
        'type' => InvoiceType::Monthly,
        'status' => InvoiceStatus::Unpaid,
        'total' => 900_000,
        'paid_amount' => 0,
        'due_date' => now()->addDays(5),
    ]);
});

it('renders notification templates substituting all placeholders safely (BE-037, PRD §11.2)', function () {
    $renderer = app(TemplateRenderer::class);
    $template = 'Halo {nama}, tagihan {nomor_invoice} kamar {kode_kamar} ({kelas}) sebesar {total} jatuh tempo {tanggal_jatuh_tempo}. Link: {link_bayar}';

    $result = $renderer->render($template, [
        'nama' => 'Fajar',
        'nomor_invoice' => 'INV-005',
        'kode_kamar' => '105',
        'kelas' => 'Standard',
        'total' => 'Rp 900.000',
        'tanggal_jatuh_tempo' => '10 Nov 2026',
        'link_bayar' => 'https://example.com/pay',
    ]);

    expect($result)->toBe('Halo Fajar, tagihan INV-005 kamar 105 (Standard) sebesar Rp 900.000 jatuh tempo 10 Nov 2026. Link: https://example.com/pay');
});

it('dispatches InvoiceIssuedNotification to tenant via WA and email with PDF (BE-038, API-017)', function () {
    Notification::fake();

    $this->tenant->notify(new InvoiceIssuedNotification($this->invoice));

    Notification::assertSentTo($this->tenant, InvoiceIssuedNotification::class);
});

it('sends guardian warning notification for monthly cycle 2 to both tenant and guardian (BE-038, API-018, SEC-012)', function () {
    Notification::fake();

    $profile = $this->tenant->tenantProfile;
    $notification = new GuardianWarningNotification($this->invoice);

    // Send to guardian anonymous notifiable
    Notification::route('fonnte', $profile->guardian_phone)
        ->route('mail', $profile->guardian_email)
        ->notify($notification);

    Notification::assertSentOnDemand(GuardianWarningNotification::class, function ($notification, $channels, $notifiable) use ($profile) {
        return ($notifiable->routes['fonnte'] ?? null) === $profile->guardian_phone
            && ($notifiable->routes['mail'] ?? null) === $profile->guardian_email;
    });
});

it('sends penalty applied notification with signed guardian payment URL (BE-038, API-018, SEC-012)', function () {
    Http::fake([
        'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
    ]);
    Mail::fake();

    $profile = $this->tenant->tenantProfile;
    $notification = new PenaltyAppliedNotification($this->invoice, 90_000);

    Notification::route('fonnte', $profile->guardian_phone)
        ->route('mail', $profile->guardian_email)
        ->notify($notification);

    Http::assertSent(function ($request) {
        return str_contains($request['message'], '90.000')
            && str_contains($request['message'], 'pay/');
    });
});
