<?php

declare(strict_types=1);

use App\Models\Facility;
use App\Models\Faq;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\PenaltyLog;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\TenantProfile;
use App\Models\Testimonial;
use App\Models\User;

test('it creates valid persisted records using model factories (DB-033)', function () {
    $admin = User::factory()->admin()->create();
    expect($admin->role->value)->toBe('admin');

    $tenant = User::factory()->tenant()->create();
    $profile = TenantProfile::factory()->for($tenant)->create();
    expect($profile->user_id)->toBe($tenant->id)
        ->and($profile->nik)->not()->toBeNull();

    $standardType = RoomType::factory()->standard()->create();
    expect($standardType->room_class->value)->toBe('standard');

    $room = Room::factory()->for($standardType)->available()->create();
    expect($room->status->value)->toBe('available');

    $reservation = Reservation::factory()->for($tenant)->for($room)->monthly()->pending()->create();
    expect($reservation->status->value)->toBe('pending_payment');

    $lease = Lease::factory()->for($reservation)->for($tenant)->for($room)->active()->create();
    expect($lease->status->value)->toBe('active');

    $invoice = Invoice::factory()->for($lease)->for($tenant)->monthly()->unpaid()->create();
    expect($invoice->status->value)->toBe('unpaid');

    $item = InvoiceItem::factory()->for($invoice)->rent()->create();
    expect($item->type->value)->toBe('rent');

    $payment = Payment::factory()->for($invoice)->for($tenant)->midtrans()->paid()->create();
    expect($payment->status->value)->toBe('paid');

    $receipt = Receipt::factory()->for($payment)->for($invoice)->for($tenant)->create();
    expect($receipt->amount)->toBe($payment->amount);

    $penaltyLog = PenaltyLog::factory()->for($invoice)->for($lease)->create();
    expect($penaltyLog->invoice_id)->toBe($invoice->id);

    $facility = Facility::factory()->create();
    expect($facility->name)->not()->toBeEmpty();

    $faq = Faq::factory()->create();
    expect($faq->question)->not()->toBeEmpty();

    $testimonial = Testimonial::factory()->for($tenant)->create();
    expect($testimonial->rating)->toBeGreaterThanOrEqual(1);
});
