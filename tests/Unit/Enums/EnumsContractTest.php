<?php

declare(strict_types=1);

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\LeaseStatus;
use App\Enums\NotificationChannel;
use App\Enums\PaymentMethod;
use App\Enums\PaymentScheme;
use App\Enums\PaymentStatus;
use App\Enums\PenaltyAction;
use App\Enums\ProrataOption;
use App\Enums\RentType;
use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use App\Enums\RoomClass;
use App\Enums\RoomStatus;
use App\Enums\UserRole;

test('it verifies all 16 backed enums have correct values and methods', function () {
    // UserRole
    expect(UserRole::Admin->value)->toBe('admin')
        ->and(UserRole::Tenant->value)->toBe('penyewa')
        ->and(UserRole::Prospect->value)->toBe('calon_penyewa')
        ->and(UserRole::Admin->label())->toBe('Admin');

    // RoomClass
    expect(RoomClass::Standard->value)->toBe('standard')
        ->and(RoomClass::Vip->value)->toBe('vip')
        ->and(RoomClass::Deluxe->value)->toBe('deluxe')
        ->and(RoomClass::Standard->label())->toBe('Standard');

    // RoomStatus
    expect(RoomStatus::Available->value)->toBe('available')
        ->and(RoomStatus::Booked->value)->toBe('booked')
        ->and(RoomStatus::Occupied->value)->toBe('occupied')
        ->and(RoomStatus::Maintenance->value)->toBe('maintenance')
        ->and(RoomStatus::Available->label())->toBe('Tersedia')
        ->and(RoomStatus::Available->color())->toBe('green');

    // RentType
    expect(RentType::Daily->value)->toBe('daily')
        ->and(RentType::Weekly->value)->toBe('weekly')
        ->and(RentType::Monthly->value)->toBe('monthly')
        ->and(RentType::Monthly->label())->toBe('Bulanan');

    // ProrataOption
    expect(ProrataOption::Combined->value)->toBe('combined')
        ->and(ProrataOption::FullFirst->value)->toBe('full_first');

    // PaymentScheme
    expect(PaymentScheme::Dp->value)->toBe('dp')
        ->and(PaymentScheme::Full->value)->toBe('full');

    // ReservationSource
    expect(ReservationSource::Online->value)->toBe('online')
        ->and(ReservationSource::Admin->value)->toBe('admin');

    // ReservationStatus
    expect(ReservationStatus::PendingPayment->value)->toBe('pending_payment')
        ->and(ReservationStatus::DpPaid->value)->toBe('dp_paid')
        ->and(ReservationStatus::Paid->value)->toBe('paid')
        ->and(ReservationStatus::CheckedIn->value)->toBe('checked_in')
        ->and(ReservationStatus::Completed->value)->toBe('completed')
        ->and(ReservationStatus::Cancelled->value)->toBe('cancelled')
        ->and(ReservationStatus::Expired->value)->toBe('expired');

    // LeaseStatus
    expect(LeaseStatus::Active->value)->toBe('active')
        ->and(LeaseStatus::Ending->value)->toBe('ending')
        ->and(LeaseStatus::Ended->value)->toBe('ended');

    // InvoiceType
    expect(InvoiceType::Reservation->value)->toBe('reservation')
        ->and(InvoiceType::Settlement->value)->toBe('settlement')
        ->and(InvoiceType::Monthly->value)->toBe('monthly')
        ->and(InvoiceType::Weekly->value)->toBe('weekly')
        ->and(InvoiceType::DailyExtension->value)->toBe('daily_extension');

    // InvoiceStatus
    expect(InvoiceStatus::Unpaid->value)->toBe('unpaid')
        ->and(InvoiceStatus::Partial->value)->toBe('partial')
        ->and(InvoiceStatus::Paid->value)->toBe('paid')
        ->and(InvoiceStatus::Overdue->value)->toBe('overdue')
        ->and(InvoiceStatus::Void->value)->toBe('void')
        ->and(InvoiceStatus::Paid->isSettled())->toBeTrue()
        ->and(InvoiceStatus::Unpaid->isSettled())->toBeFalse();

    // InvoiceItemType
    expect(InvoiceItemType::Rent->value)->toBe('rent')
        ->and(InvoiceItemType::Prorata->value)->toBe('prorata')
        ->and(InvoiceItemType::Penalty->value)->toBe('penalty')
        ->and(InvoiceItemType::Discount->value)->toBe('discount')
        ->and(InvoiceItemType::Other->value)->toBe('other');

    // PaymentMethod
    expect(PaymentMethod::Midtrans->value)->toBe('midtrans')
        ->and(PaymentMethod::Cash->value)->toBe('cash');

    // PaymentStatus
    expect(PaymentStatus::Pending->value)->toBe('pending')
        ->and(PaymentStatus::Paid->value)->toBe('paid')
        ->and(PaymentStatus::Failed->value)->toBe('failed')
        ->and(PaymentStatus::NeedsReview->value)->toBe('needs_review');

    // PenaltyAction
    expect(PenaltyAction::Reminder->value)->toBe('reminder')
        ->and(PenaltyAction::Warning->value)->toBe('warning')
        ->and(PenaltyAction::Penalty->value)->toBe('penalty')
        ->and(PenaltyAction::Waived->value)->toBe('waived');

    // NotificationChannel
    expect(NotificationChannel::WhatsApp->value)->toBe('whatsapp')
        ->and(NotificationChannel::Email->value)->toBe('email')
        ->and(NotificationChannel::Database->value)->toBe('database');
});
