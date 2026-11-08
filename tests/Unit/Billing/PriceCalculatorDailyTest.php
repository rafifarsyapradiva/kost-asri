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

it('calculates daily rent quote for VIP room (TST-006, BR-05, PRD §10.1)', function () {
    $vip = RoomType::factory()->vip()->create([
        'daily_rate' => 85_000,
    ]);

    $checkIn = Carbon::parse('2026-11-12');
    $nights = 3;

    $quote = app(PriceCalculator::class)->quote($vip, RentType::Daily, $checkIn, $nights);

    expect($quote->total())->toBe(255_000)
        ->and($quote->minDp(30))->toBe(76_500)
        ->and($quote->billedUntil()->toDateString())->toBe('2026-11-14')
        ->and($quote->items())->toHaveCount(1)
        ->and($quote->items()[0]->type)->toBe(InvoiceItemType::Rent)
        ->and($quote->items()[0]->amount)->toBe(255_000);
});

it('enforces minimum 1 night for daily rent quote (BR-05)', function () {
    $vip = RoomType::factory()->vip()->create(['daily_rate' => 85_000]);

    app(PriceCalculator::class)->quote($vip, RentType::Daily, Carbon::parse('2026-11-12'), 0);
})->throws(\InvalidArgumentException::class, 'Jumlah malam sewa harian minimal 1 malam.');
