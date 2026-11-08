<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Pdf\PdfService;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentScheme;
use App\Enums\PaymentStatus;
use App\Enums\RentType;
use App\Enums\ReservationStatus;
use App\Enums\RoomClass;
use App\Enums\RoomStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->type = RoomType::create([
        'name' => 'Standard Room',
        'slug' => 'standard',
        'room_class' => RoomClass::Standard,
        'daily_rate' => 100000,
        'weekly_rate' => 600000,
        'monthly_rate' => 1500000,
        'description' => 'Standard Room Description',
    ]);

    $this->room = Room::create([
        'room_type_id' => $this->type->id,
        'code' => '101',
        'floor' => 1,
        'status' => RoomStatus::Available,
    ]);

    $this->tenant = User::factory()->create([
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'phone' => '081234567890',
        'role' => UserRole::Tenant,
    ]);

    $this->otherTenant = User::factory()->create([
        'name' => 'Siti Rahma',
        'email' => 'siti@example.com',
        'phone' => '081298765432',
        'role' => UserRole::Tenant,
    ]);

    $this->admin = User::factory()->create([
        'name' => 'Pengelola Kost',
        'email' => 'admin@example.com',
        'phone' => '081111111111',
        'role' => UserRole::Admin,
    ]);

    $this->reservation = Reservation::create([
        'code' => 'RSV-202611-0001',
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'rent_type' => RentType::Monthly,
        'payment_scheme' => PaymentScheme::Full,
        'preferred_method' => 'midtrans',
        'status' => ReservationStatus::PendingPayment,
        'check_in_date' => now()->addDays(2)->toDateString(),
        'check_out_date' => now()->addDays(32)->toDateString(),
        'duration_qty' => 1,
        'rate_snapshot' => 1500000,
        'total_amount' => 2000000,
        'dp_amount' => 2000000,
        'initial_billed_until' => now()->addDays(32)->toDateString(),
        'expires_at' => now()->addHours(2),
    ]);

    $this->invoice = Invoice::create([
        'number' => 'INV/ASRI/202611/0001',
        'user_id' => $this->tenant->id,
        'reservation_id' => $this->reservation->id,
        'type' => InvoiceType::Reservation,
        'status' => InvoiceStatus::Unpaid,
        'period_start' => now()->addDays(2)->toDateString(),
        'period_end' => now()->addDays(32)->toDateString(),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addHours(2),
        'subtotal' => 1500000,
        'total' => 2000000,
        'paid_amount' => 0,
        'minimum_pay_amount' => 2000000,
    ]);

    $this->payment = Payment::create([
        'invoice_id' => $this->invoice->id,
        'user_id' => $this->tenant->id,
        'order_id' => 'INV/ASRI/202611/0001-12345',
        'amount' => 2000000,
        'method' => PaymentMethod::Midtrans,
        'status' => PaymentStatus::Paid,
        'paid_at' => now(),
    ]);

    $this->receipt = Receipt::create([
        'invoice_id' => $this->invoice->id,
        'payment_id' => $this->payment->id,
        'user_id' => $this->tenant->id,
        'number' => 'KWT/ASRI/202611/0001',
        'amount' => 2000000,
        'issued_at' => now(),
    ]);
});

it('renders valid PDF binary with %PDF- header for Reservation Note (FR-PDF-01)', function () {
    $service = app(PdfService::class);
    $binary = $service->renderReservationNote($this->reservation);

    expect($binary)->toBeString()
        ->and(str_starts_with($binary, '%PDF-'))->toBeTrue()
        ->and(strlen($binary))->toBeGreaterThan(1000);
});

it('renders valid PDF binary for Invoice with breakdown and due date (FR-PDF-02)', function () {
    $service = app(PdfService::class);
    $binary = $service->renderInvoice($this->invoice);

    expect($binary)->toBeString()
        ->and(str_starts_with($binary, '%PDF-'))->toBeTrue()
        ->and(strlen($binary))->toBeGreaterThan(1000);
});

it('renders valid PDF binary for Receipt with terbilang and LUNAS stamp (FR-PDF-03)', function () {
    $service = app(PdfService::class);
    $binary = $service->renderReceipt($this->receipt);

    expect($binary)->toBeString()
        ->and(str_starts_with($binary, '%PDF-'))->toBeTrue()
        ->and(strlen($binary))->toBeGreaterThan(1000);
});

it('renders valid PDF binary for occupancy and income report (FR-DASH-02)', function () {
    $service = app(PdfService::class);
    $binary = $service->renderReport('monthly', [
        'period' => 'November 2026',
        'total_revenue' => 15000000,
        'occupancy_rate' => 93.75,
        'total_rooms' => 32,
        'occupied_rooms' => 30,
        'overdue_count' => 1,
        'items' => [
            ['room' => '101', 'tenant' => 'Budi', 'amount' => 1500000, 'status' => 'Lunas'],
        ],
    ]);

    expect($binary)->toBeString()
        ->and(str_starts_with($binary, '%PDF-'))->toBeTrue()
        ->and(strlen($binary))->toBeGreaterThan(1000);
});

it('allows tenant to download their own reservation note, invoice, and receipt pdfs', function () {
    $this->actingAs($this->tenant);

    $resResponse = $this->get(route('reservations.pdf', $this->reservation));
    $resResponse->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    $invResponse = $this->get(route('invoices.pdf', $this->invoice));
    $invResponse->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    $recResponse = $this->get(route('receipts.pdf', $this->receipt));
    $recResponse->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('protects PDF download routes with policies preventing IDOR (TST-017, NFR-01)', function () {
    $this->actingAs($this->otherTenant);

    $this->get(route('reservations.pdf', $this->reservation))
        ->assertForbidden();

    $this->get(route('invoices.pdf', $this->invoice))
        ->assertForbidden();

    $this->get(route('receipts.pdf', $this->receipt))
        ->assertForbidden();
});

it('allows admin to download any pdf and generate reports (FR-DASH-02)', function () {
    $this->actingAs($this->admin);

    $this->get(route('reservations.pdf', $this->reservation))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    $this->get(route('invoices.pdf', $this->invoice))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    $this->get(route('receipts.pdf', $this->receipt))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    $this->get(route('admin.reports.pdf', ['month' => '2026-11']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});
