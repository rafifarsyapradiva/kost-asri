<?php

declare(strict_types=1);

use App\Enums\InvoiceItemType;
use App\Enums\RentType;
use App\Models\RoomType;
use App\Services\Billing\PriceCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('calculates weekly rent quote with rolling 7 days period (TST-007, BR-08, PRD §10.2)', function () {
    $standard = RoomType::factory()->standard()->create([
        'weekly_rate' => 300_000,
    ]);

    $checkIn = Carbon::parse('2026-11-04'); // Wednesday
    $weeks = 1;

    $quote = app(PriceCalculator::class)->quote($standard, RentType::Weekly, $checkIn, $weeks);

    // 1 week: start_1 = 4 Nov, end_1 = 4 + 6 = 10 Nov (billed_until)
    expect($quote->total())->toBe(300_000)
        ->and($quote->minDp(30))->toBe(90_000)
        ->and($quote->billedUntil()->toDateString())->toBe('2026-11-10')
        ->and($quote->items())->toHaveCount(1)
        ->and($quote->items()[0]->type)->toBe(InvoiceItemType::Rent)
        ->and($quote->items()[0]->amount)->toBe(300_000);
});

it('calculates multi-week rent quotes correctly (BR-08)', function () {
    $standard = RoomType::factory()->standard()->create([
        'weekly_rate' => 300_000,
    ]);

    $checkIn = Carbon::parse('2026-11-04');
    $weeks = 2;

    $quote = app(PriceCalculator::class)->quote($standard, RentType::Weekly, $checkIn, $weeks);

    // 2 weeks: end_2 = start_1 + 7*2 - 1 = 4 + 13 = 17 Nov
    expect($quote->total())->toBe(600_000)
        ->and($quote->minDp(30))->toBe(180_000)
        ->and($quote->billedUntil()->toDateString())->toBe('2026-11-17');
});

it('enforces minimum 1 week for weekly rent quote (BR-08)', function () {
    $standard = RoomType::factory()->standard()->create(['weekly_rate' => 300_000]);

    app(PriceCalculator::class)->quote($standard, RentType::Weekly, Carbon::parse('2026-11-04'), 0);
})->throws(\InvalidArgumentException::class, 'Jumlah minggu sewa mingguan minimal 1 minggu.');
