<?php

declare(strict_types=1);

use App\Enums\ProrataOption;
use App\Enums\RentType;
use App\Models\RoomType;
use App\Services\Billing\PriceCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('calculates minimum DP and validates custom DP amount (TST-011, BR-02, BR-16, FR-RSV-03)', function () {
    $standard = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);

    $quote = app(PriceCalculator::class)->quote(
        $standard,
        RentType::Monthly,
        Carbon::parse('2026-11-15'),
        1,
        ProrataOption::Combined
    );

    // Total = 1.380.000. 30% = 414.000
    expect($quote->total())->toBe(1_380_000)
        ->and($quote->minDp(30))->toBe(414_000)
        ->and($quote->validateDp(414_000, 30))->toBeTrue()
        ->and($quote->validateDp(500_000, 30))->toBeTrue()
        ->and($quote->validateDp(1_380_000, 30))->toBeTrue()
        ->and($quote->validateDp(413_999, 30))->toBeFalse()
        ->and($quote->validateDp(1_380_001, 30))->toBeFalse();
});

it('calculates minimum DP using ceil rounding (BR-02)', function () {
    $type = RoomType::factory()->create(['daily_rate' => 85_333]);

    $quote = app(PriceCalculator::class)->quote($type, RentType::Daily, Carbon::parse('2026-11-12'), 1);

    // 85.333 * 0.30 = 25599.9 -> ceil gives 25600
    expect($quote->total())->toBe(85_333)
        ->and($quote->minDp(30))->toBe(25_600);
});
