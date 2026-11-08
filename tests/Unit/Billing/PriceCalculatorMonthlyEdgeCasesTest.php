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

it('calculates monthly quote for entry exactly on the 1st without prorata (TST-010, BR-13, PRD §10.6)', function () {
    $standard = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);

    $checkIn = Carbon::parse('2026-11-01');

    // Entry on 1st: exactly 1 full month, billed until end of month (30 Nov 2026), option ignored
    $quote = app(PriceCalculator::class)->quote($standard, RentType::Monthly, $checkIn);

    expect($quote->total())->toBe(900_000)
        ->and($quote->minDp(30))->toBe(270_000)
        ->and($quote->billedUntil()->toDateString())->toBe('2026-11-30')
        ->and($quote->items())->toHaveCount(1)
        ->and($quote->items()[0]->type)->toBe(InvoiceItemType::Rent)
        ->and($quote->items()[0]->amount)->toBe(900_000);
});

it('requires prorata option when monthly entry is after the 1st of month', function () {
    $standard = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);

    $checkIn = Carbon::parse('2026-11-15');

    app(PriceCalculator::class)->quote($standard, RentType::Monthly, $checkIn, 1, null);
})->throws(\InvalidArgumentException::class, 'ProrataOption wajib dipilih untuk sewa bulanan yang masuk setelah tanggal 1.');
