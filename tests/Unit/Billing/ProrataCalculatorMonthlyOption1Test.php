<?php

declare(strict_types=1);

use App\Enums\InvoiceItemType;
use App\Enums\ProrataOption;
use App\Enums\RentType;
use App\Models\RoomType;
use App\Services\Billing\PriceCalculator;
use App\Services\Billing\ProrataCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('calculates monthly Option 1 combined prorata for Nov 15 entry (TST-008, BR-13, BR-14, PRD §10.3)', function () {
    $standard = RoomType::factory()->standard()->create([
        'monthly_rate' => 900_000,
    ]);

    $checkIn = Carbon::parse('2026-11-15'); // November has 30 days. Used days: 30 - 15 + 1 = 16 days.
    // Prorata 15–30 Nov: 900.000 / 30 * 16 = 480.000
    // Des full = 900.000
    // Total = 1.380.000; min DP 30% = 414.000; billed_until = 2026-12-31

    $quote = app(PriceCalculator::class)->quote(
        $standard,
        RentType::Monthly,
        $checkIn,
        1,
        ProrataOption::Combined
    );

    expect($quote->total())->toBe(1_380_000)
        ->and($quote->minDp(30))->toBe(414_000)
        ->and($quote->billedUntil()->toDateString())->toBe('2026-12-31')
        ->and($quote->items())->toHaveCount(2)
        ->and($quote->items()[0]->type)->toBe(InvoiceItemType::Prorata)
        ->and($quote->items()[0]->amount)->toBe(480_000)
        ->and($quote->items()[1]->type)->toBe(InvoiceItemType::Rent)
        ->and($quote->items()[1]->amount)->toBe(900_000);
});

it('calculates prorata accurately across 28, 29 leap, 30, and 31 days months (TST-008, BR-13)', function (
    int $rate,
    int $daysInMonth,
    int $days,
    int $expected
) {
    $calc = app(ProrataCalculator::class);
    expect($calc->calculate($rate, $daysInMonth, $days))->toBe($expected);
})->with([
    '30 days month (Nov 15-30: 16 days of 900k)' => [900_000, 30, 16, 480_000],
    '31 days month (Dec 15-31: 17 days of 900k)' => [900_000, 31, 17, 493_548], // round(900k / 31 * 17) = 493548.387 -> 493548
    '28 days month (Feb 2027: 10 days of 900k)' => [900_000, 28, 10, 321_429], // round(900k / 28 * 10) = 321428.57 -> 321429
    '29 days leap month (Feb 2028: 10 days of 900k)' => [900_000, 29, 10, 310_345], // round(900k / 29 * 10) = 310344.8 -> 310345
]);
