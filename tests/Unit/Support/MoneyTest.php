<?php

declare(strict_types=1);

use App\Support\Money;

test('it rounds amounts to the nearest specified integer unit (BR-32)', function () {
    expect(Money::round(900_000.4))->toBe(900_000)
        ->and(Money::round(900_000.6))->toBe(900_001)
        ->and(Money::round(493_548.38))->toBe(493_548)
        ->and(Money::round(493_548.51))->toBe(493_549);
});

test('it formats nominal to Indonesian Rupiah standard format', function () {
    expect(Money::format(900_000))->toBe('Rp 900.000')
        ->and(Money::format(1_380_000))->toBe('Rp 1.380.000')
        ->and(Money::format(0))->toBe('Rp 0')
        ->and(Money::format(-50_000))->toBe('Rp -50.000');
});

test('it converts nominal into accurate Indonesian terbilang words', function () {
    expect(Money::terbilang(0))->toBe('nol rupiah')
        ->and(Money::terbilang(60_000))->toBe('enam puluh ribu rupiah')
        ->and(Money::terbilang(85_000))->toBe('delapan puluh lima ribu rupiah')
        ->and(Money::terbilang(120_000))->toBe('seratus dua puluh ribu rupiah')
        ->and(Money::terbilang(900_000))->toBe('sembilan ratus ribu rupiah')
        ->and(Money::terbilang(1_380_000))->toBe('satu juta tiga ratus delapan puluh ribu rupiah')
        ->and(Money::terbilang(11_000_000))->toBe('sebelas juta rupiah')
        ->and(Money::terbilang(1_000_000_000))->toBe('satu miliar rupiah');
});
