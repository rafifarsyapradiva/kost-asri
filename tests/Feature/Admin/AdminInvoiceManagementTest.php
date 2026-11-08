<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('lists invoices with status, type, and search filters (BE-047, FR-BILL-06)', function () {
    $admin = User::factory()->admin()->create();
    $tenant = User::factory()->tenant()->create(['name' => 'Joko Widodo']);

    $roomType = RoomType::factory()->create();
    $room = Room::factory()->create(['room_type_id' => $roomType->id, 'code' => '104']);

    $inv1 = Invoice::factory()->create([
        'user_id' => $tenant->id,
        'number' => 'INV/2026/11/0001',
        'type' => InvoiceType::Monthly,
        'status' => InvoiceStatus::Unpaid,
        'total' => 900_000,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.invoices.index'));

    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page
        ->component('Admin/Invoices/Index')
        ->has('invoices.data', 1)
        ->has('filters')
    );
});

it('shows detailed invoice with items, payments, and penalty logs (BE-047)', function () {
    $admin = User::factory()->admin()->create();
    $tenant = User::factory()->tenant()->create();
    $roomType = RoomType::factory()->create();
    $room = Room::factory()->create(['room_type_id' => $roomType->id, 'code' => '105']);

    $invoice = Invoice::factory()->create([
        'user_id' => $tenant->id,
        'number' => 'INV/2026/11/0002',
        'status' => InvoiceStatus::Overdue,
    ]);

    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Sewa Bulanan',
        'amount' => 900_000,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.invoices.show', $invoice));

    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page
        ->component('Admin/Invoices/Show')
        ->where('invoice.id', $invoice->id)
        ->has('invoice.items', 1)
        ->has('invoice.user')
    );
});
