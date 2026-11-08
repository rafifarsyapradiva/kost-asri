<?php

declare(strict_types=1);

use App\Support\PhoneNormalizer;

it('normalizes various Indonesian phone number formats to standard 628xxx (TST-005, FR-AUTH-01)', function (string $input, ?string $expected) {
    expect(PhoneNormalizer::normalize($input))->toBe($expected);
})->with([
    ['081234567890', '6281234567890'],
    ['+62 812-3456-7890', '6281234567890'],
    ['6281234567890', '6281234567890'],
    ['81234567890', '6281234567890'],
    ['+6281234567890', '6281234567890'],
    [' 0812 3456 7890 ', '6281234567890'],
    ['', null],
    ['invalid-phone', null],
]);
