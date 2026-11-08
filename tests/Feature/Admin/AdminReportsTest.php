<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('renders reports page with revenue, arrears, and occupancy data (BE-048, FR-DASH-02)', function () {
    $admin = User::factory()->admin()->create();
    $tenant = User::factory()->tenant()->create();
    $roomType = RoomType::factory()->create();
    $room = Room::factory()->create(['room_type_id' => $roomType->id, 'code' => '106']);

    $invoice = Invoice::factory()->create([
        'user_id' => $tenant->id,
        'number' => 'INV/2026/11/0010',
        'type' => InvoiceType::Monthly,
        'status' => InvoiceStatus::Paid,
        'total' => 900_000,
        'paid_amount' => 900_000,
        'paid_at' => now(),
    ]);

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $tenant->id,
        'amount' => 900_000,
        'method' => PaymentMethod::Cash,
        'status' => PaymentStatus::Paid,
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.index'));

    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page
        ->component('Admin/Reports/Index')
        ->has('revenue')
        ->has('arrears')
        ->has('occupancy')
        ->has('filters')
    );
});

it('exports financial report data to CSV (BE-048)', function () {
    $admin = User::factory()->admin()->create();
    $tenant = User::factory()->tenant()->create(['name' => 'Budi Santoso']);
    $roomType = RoomType::factory()->create();
    $room = Room::factory()->create(['room_type_id' => $roomType->id, 'code' => '107']);

    $invoice = Invoice::factory()->create([
        'user_id' => $tenant->id,
        'number' => 'INV/2026/11/0011',
        'type' => InvoiceType::Monthly,
        'status' => InvoiceStatus::Paid,
        'total' => 900_000,
        'paid_amount' => 900_000,
        'paid_at' => now(),
    ]);

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $tenant->id,
        'amount' => 900_000,
        'method' => PaymentMethod::Cash,
        'status' => PaymentStatus::Paid,
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export-csv', ['type' => 'revenue']));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toContain('Budi Santoso');
});
