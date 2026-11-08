<?php

declare(strict_types=1);

use App\Support\NumberSequence;

test('it generates consecutive atomic numbers with proper format and period reset (BR-30)', function () {
    $rsv1 = NumberSequence::next('RSV', '20261108');
    $rsv2 = NumberSequence::next('RSV', '20261108');
    $rsvOtherDate = NumberSequence::next('RSV', '20261109');

    expect($rsv1)->toBe('RSV-20261108-0001')
        ->and($rsv2)->toBe('RSV-20261108-0002')
        ->and($rsvOtherDate)->toBe('RSV-20261109-0001');

    $inv1 = NumberSequence::next('INV', '202611');
    $inv2 = NumberSequence::next('INV', '202611');

    expect($inv1)->toBe('INV/ASRI/2026/11/0001')
        ->and($inv2)->toBe('INV/ASRI/2026/11/0002');

    $kwt1 = NumberSequence::next('KWT', '202611');
    expect($kwt1)->toBe('KWT/ASRI/2026/11/0001');
});
