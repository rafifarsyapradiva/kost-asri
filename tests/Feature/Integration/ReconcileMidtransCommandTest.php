<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\User;

it('reconciles pending payments older than 15 minutes using status checker and records cron_last_run (API-008, FR-CRON-06, TST-032)', function () {
    $tenant = User::factory()->create(['role' => 'penyewa']);
    $roomType = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);
    $room = Room::factory()->create(['room_type_id' => $roomType->id, 'code' => '103']);
    $lease = Lease::factory()->create(['user_id' => $tenant->id, 'room_id' => $room->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $tenant->id,
        'lease_id' => $lease->id,
        'number' => 'INV/ASRI/202611/0003',
        'type' => InvoiceType::Monthly,
        'status' => InvoiceStatus::Unpaid,
        'total' => 900_000,
        'paid_amount' => 0,
    ]);

    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $tenant->id,
        'method' => 'midtrans',
        'order_id' => 'INVASRI2026110003-1762590002',
        'amount' => 900_000,
        'status' => PaymentStatus::Pending,
        'created_at' => now()->subMinutes(20),
    ]);

    $this->artisan('payment:reconcile-midtrans')
        ->assertSuccessful();

    expect(Setting::get('cron_last_run.payment:reconcile-midtrans'))->not->toBeNull();
});

