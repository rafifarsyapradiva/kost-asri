<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\LeaseStatus;
use App\Enums\RentType;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('renders tenant dashboard with active lease, room info, and active unpaid invoice (BE-050, FR-DASH-03)', function () {
    $tenant = User::factory()->tenant()->create();
    $roomType = RoomType::factory()->create(['name' => 'Deluxe Suite']);
    $room = Room::factory()->create(['room_type_id' => $roomType->id, 'code' => '211']);

    $reservation = Reservation::factory()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
    ]);

    $lease = Lease::factory()->create([
        'reservation_id' => $reservation->id,
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => LeaseStatus::Active,
        'paid_until' => now()->addDays(20)->toDateString(),
    ]);

    $invoice = Invoice::factory()->create([
        'lease_id' => $lease->id,
        'user_id' => $tenant->id,
        'number' => 'INV/2026/11/0099',
        'type' => InvoiceType::Monthly,
        'status' => InvoiceStatus::Unpaid,
        'total' => 1_200_000,
    ]);

    $response = $this->actingAs($tenant)
        ->get(route('tenant.dashboard'));

    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page
        ->component('Tenant/Dashboard')
        ->where('lease.id', $lease->id)
        ->where('active_invoice.id', $invoice->id)
        ->has('lease.room')
    );
});

it('lists tenant personal invoices and forbids cross-tenant invoice view (BE-050, NFR-01)', function () {
    $tenant1 = User::factory()->tenant()->create();
    $tenant2 = User::factory()->tenant()->create();

    $inv1 = Invoice::factory()->create([
        'user_id' => $tenant1->id,
        'number' => 'INV/T1/0001',
        'status' => InvoiceStatus::Paid,
    ]);

    $inv2 = Invoice::factory()->create([
        'user_id' => $tenant2->id,
        'number' => 'INV/T2/0001',
        'status' => InvoiceStatus::Unpaid,
    ]);

    // Tenant 1 lists own invoices
    $response = $this->actingAs($tenant1)
        ->get(route('tenant.invoices.index'));

    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page
        ->component('Tenant/Invoices/Index')
        ->has('invoices.data', 1)
        ->where('invoices.data.0.id', $inv1->id)
    );

    // Tenant 1 cannot access Tenant 2's invoice
    $this->actingAs($tenant1)
        ->get(route('tenant.invoices.show', $inv2))
        ->assertForbidden();
});

it('allows daily stay extension and creates extension invoice (BE-051, FR-RSV-09, BR-07)', function () {
    $tenant = User::factory()->tenant()->create();
    $roomType = RoomType::factory()->create(['daily_rate' => 100_000]);
    $room = Room::factory()->create(['room_type_id' => $roomType->id, 'code' => '113']);

    $reservation = Reservation::factory()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Daily,
        'check_in_date' => '2026-11-08',
        'check_out_date' => '2026-11-10',
    ]);

    $lease = Lease::factory()->create([
        'reservation_id' => $reservation->id,
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Daily,
        'status' => LeaseStatus::Active,
        'start_date' => '2026-11-08',
        'paid_until' => '2026-11-10',
        'planned_checkout_date' => '2026-11-10',
    ]);

    $response = $this->actingAs($tenant)
        ->post(route('tenant.extension.daily'), [
            'extra_nights' => 2,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Extension invoice created
    expect(Invoice::where('user_id', $tenant->id)->where('type', InvoiceType::DailyExtension)->exists())->toBeTrue();
});
