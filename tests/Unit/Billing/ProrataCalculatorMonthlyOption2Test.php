<?php

declare(strict_types=1);

use App\Enums\InvoiceItemType;
use App\Enums\ProrataOption;
use App\Enums\RentType;
use App\Models\RoomType;
use App\Services\Billing\PriceCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('calculates monthly Option 2 full first quote for Nov 15 entry (TST-009, BR-15, PRD §10.4)', function () {
    $standard = RoomType::factory()->standard()->create([
        'monthly_rate' => 900_000,
    ]);

    $checkIn = Carbon::parse('2026-11-15');
    // Option 2: 1 full month initially, covering 15 Nov s/d 14 Des 2026.
    // Total = 900.000; min DP 30% = 270.000; billed_until = 2026-12-14

    $quote = app(PriceCalculator::class)->quote(
        $standard,
        RentType::Monthly,
        $checkIn,
        1,
        ProrataOption::FullFirst
    );

    expect($quote->total())->toBe(900_000)
        ->and($quote->minDp(30))->toBe(270_000)
        ->and($quote->billedUntil()->toDateString())->toBe('2026-12-14')
        ->and($quote->items())->toHaveCount(1)
        ->and($quote->items()[0]->type)->toBe(InvoiceItemType::Rent)
        ->and($quote->items()[0]->amount)->toBe(900_000);
});

it('handles Option 2 edge case entry on 31 Jan for non-leap and leap year (TST-009, PRD §10.6)', function () {
    $standard = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);

    // Non-leap year: 2027
    $checkIn2027 = Carbon::parse('2027-01-31');
    $quote2027 = app(PriceCalculator::class)->quote(
        $standard,
        RentType::Monthly,
        $checkIn2027,
        1,
        ProrataOption::FullFirst
    );
    // 31 Jan + 1 month without overflow -> 28 Feb (end of Feb)
    expect($quote2027->billedUntil()->toDateString())->toBe('2027-02-28');

    // Leap year: 2028
    $checkIn2028 = Carbon::parse('2028-01-31');
    $quote2028 = app(PriceCalculator::class)->quote(
        $standard,
        RentType::Monthly,
        $checkIn2028,
        1,
        ProrataOption::FullFirst
    );
    expect($quote2028->billedUntil()->toDateString())->toBe('2028-02-29');
});
