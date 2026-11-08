<?php

declare(strict_types=1);

use App\Actions\Payment\ApplyPaymentAction;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentScheme;
use App\Enums\ReservationStatus;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('applies full payment to reservation invoice issuing receipt with KWT number (BE-035, SEC-016, BR-30)', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'status' => ReservationStatus::PendingPayment,
        'total_amount' => 900_000,
        'dp_amount' => 270_000,
        'payment_scheme' => PaymentScheme::Full,
    ]);

    $invoice = Invoice::factory()->create([
        'reservation_id' => $reservation->id,
        'user_id' => $user->id,
        'type' => InvoiceType::Reservation,
        'subtotal' => 900_000,
        'total' => 900_000,
        'minimum_pay_amount' => 900_000,
        'paid_amount' => 0,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $action = app(ApplyPaymentAction::class);
    $result = $action->execute(
        invoice: $invoice,
        amount: 900_000,
        method: PaymentMethod::Midtrans,
        orderId: 'INV-ASRI-202611-0001-123456789'
    );

    expect($result['invoice']->status)->toBe(InvoiceStatus::Paid)
        ->and($result['invoice']->paid_amount)->toBe(900_000)
        ->and($reservation->fresh()->status)->toBe(ReservationStatus::Paid)
        ->and($result['receipt']->number)->toStartWith('KWT/ASRI/')
        ->and($result['receipt']->amount)->toBe(900_000);
});

it('applies partial payment exclusively to reservation invoice updating status to DpPaid (BR-22)', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'status' => ReservationStatus::PendingPayment,
        'total_amount' => 1_380_000,
        'dp_amount' => 414_000,
        'payment_scheme' => PaymentScheme::Dp,
    ]);

    $invoice = Invoice::factory()->create([
        'reservation_id' => $reservation->id,
        'user_id' => $user->id,
        'type' => InvoiceType::Reservation,
        'subtotal' => 1_380_000,
        'total' => 1_380_000,
        'minimum_pay_amount' => 414_000,
        'paid_amount' => 0,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $action = app(ApplyPaymentAction::class);
    $result = $action->execute(
        invoice: $invoice,
        amount: 414_000,
        method: PaymentMethod::Cash
    );

    expect($result['invoice']->status)->toBe(InvoiceStatus::Partial)
        ->and($result['invoice']->paid_amount)->toBe(414_000)
        ->and($reservation->fresh()->status)->toBe(ReservationStatus::DpPaid);
});

it('rejects partial payment for recurring monthly or weekly invoices (BR-22)', function () {
    $user = User::factory()->create();
    $lease = Lease::factory()->create(['user_id' => $user->id]);

    $monthlyInvoice = Invoice::factory()->create([
        'lease_id' => $lease->id,
        'user_id' => $user->id,
        'type' => InvoiceType::Monthly,
        'subtotal' => 900_000,
        'total' => 900_000,
        'paid_amount' => 0,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $action = app(ApplyPaymentAction::class);

    $this->expectException(ValidationException::class);
    $action->execute(
        invoice: $monthlyInvoice,
        amount: 500_000, // partial payment attempt
        method: PaymentMethod::Cash
    );
});
