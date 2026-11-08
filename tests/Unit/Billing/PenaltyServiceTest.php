<?php

declare(strict_types=1);

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PenaltyAction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\User;
use App\Services\Billing\PenaltyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('applies penalty rules: cycle 1 reminder, cycle 2 warning, cycle 3+ 10% penalty on base rent (TST-012, BR-18, BR-19, BR-20)', function () {
    Event::fake();
    $service = app(PenaltyService::class);
    $user = User::factory()->create();
    $lease = Lease::factory()->create(['user_id' => $user->id, 'monthly_rate' => 900_000]);

    // --- SIKLUS 1: Overdue past Jan 10 ---
    $inv1 = Invoice::factory()->create([
        'lease_id' => $lease->id,
        'user_id' => $user->id,
        'type' => InvoiceType::Monthly,
        'cycle_index' => 1,
        'due_date' => '2027-01-10',
        'subtotal' => 900_000,
        'total' => 900_000,
        'status' => InvoiceStatus::Unpaid,
    ]);
    InvoiceItem::factory()->create([
        'invoice_id' => $inv1->id,
        'type' => InvoiceItemType::Rent,
        'amount' => 900_000,
    ]);

    $service->processMonthly($inv1, Carbon::parse('2027-01-11'));

    expect($inv1->fresh()->status)->toBe(InvoiceStatus::Overdue)
        ->and($inv1->fresh()->penalty_amount)->toBe(0)
        ->and($inv1->fresh()->total)->toBe(900_000)
        ->and($inv1->fresh()->penaltyLogs)->toHaveCount(1)
        ->and($inv1->fresh()->penaltyLogs[0]->action)->toBe(PenaltyAction::Reminder);

    // --- SIKLUS 2: Overdue past Feb 10 ---
    $inv2 = Invoice::factory()->create([
        'lease_id' => $lease->id,
        'user_id' => $user->id,
        'type' => InvoiceType::Monthly,
        'cycle_index' => 2,
        'due_date' => '2027-02-10',
        'subtotal' => 900_000,
        'total' => 900_000,
        'status' => InvoiceStatus::Unpaid,
    ]);
    InvoiceItem::factory()->create([
        'invoice_id' => $inv2->id,
        'type' => InvoiceItemType::Rent,
        'amount' => 900_000,
    ]);

    $service->processMonthly($inv2, Carbon::parse('2027-02-11'));

    expect($inv2->fresh()->status)->toBe(InvoiceStatus::Overdue)
        ->and($inv2->fresh()->penalty_amount)->toBe(0)
        ->and($inv2->fresh()->penaltyLogs)->toHaveCount(1)
        ->and($inv2->fresh()->penaltyLogs[0]->action)->toBe(PenaltyAction::Warning);

    // --- SIKLUS 3: Overdue past Mar 10 -> 10% penalty on base rent ---
    $inv3 = Invoice::factory()->create([
        'lease_id' => $lease->id,
        'user_id' => $user->id,
        'type' => InvoiceType::Monthly,
        'cycle_index' => 3,
        'due_date' => '2027-03-10',
        'subtotal' => 900_000,
        'total' => 900_000,
        'status' => InvoiceStatus::Unpaid,
    ]);
    InvoiceItem::factory()->create([
        'invoice_id' => $inv3->id,
        'type' => InvoiceItemType::Rent,
        'amount' => 900_000,
    ]);

    $service->processMonthly($inv3, Carbon::parse('2027-03-11'));

    expect($inv3->fresh()->status)->toBe(InvoiceStatus::Overdue)
        ->and($inv3->fresh()->penalty_amount)->toBe(90_000)
        ->and($inv3->fresh()->total)->toBe(990_000)
        ->and($inv3->fresh()->penaltyLogs)->toHaveCount(1)
        ->and($inv3->fresh()->penaltyLogs[0]->action)->toBe(PenaltyAction::Penalty)
        ->and($inv3->fresh()->penaltyLogs[0]->amount)->toBe(90_000);

    // Idempotency: second run does NOT add another penalty (TST-012, BR-18)
    $service->processMonthly($inv3, Carbon::parse('2027-03-11'));
    expect($inv3->fresh()->penalty_amount)->toBe(90_000)
        ->and($inv3->fresh()->total)->toBe(990_000);
});

it('calculates weekly 10% penalty when past H+1 (BR-11)', function () {
    Event::fake();
    $service = app(PenaltyService::class);
    $user = User::factory()->create();
    $lease = Lease::factory()->create(['user_id' => $user->id, 'weekly_rate' => 300_000]);

    $inv = Invoice::factory()->create([
        'lease_id' => $lease->id,
        'user_id' => $user->id,
        'type' => InvoiceType::Weekly,
        'cycle_index' => 2,
        'due_date' => '2026-11-11', // H+1
        'subtotal' => 300_000,
        'total' => 300_000,
        'status' => InvoiceStatus::Unpaid,
    ]);
    InvoiceItem::factory()->create([
        'invoice_id' => $inv->id,
        'type' => InvoiceItemType::Rent,
        'amount' => 300_000,
    ]);

    // On 12 Nov (past H+1)
    $service->processWeekly($inv, Carbon::parse('2026-11-12'));

    expect($inv->fresh()->status)->toBe(InvoiceStatus::Overdue)
        ->and($inv->fresh()->penalty_amount)->toBe(30_000)
        ->and($inv->fresh()->total)->toBe(330_000)
        ->and($inv->fresh()->penaltyLogs)->toHaveCount(1)
        ->and($inv->fresh()->penaltyLogs[0]->action)->toBe(PenaltyAction::Penalty);
});
