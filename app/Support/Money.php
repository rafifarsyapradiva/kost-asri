<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    /**
     * Round nominal to nearest integer unit (BR-32, NFR-06).
     */
    public static function round(float|int $amount, int $unit = 1): int
    {
        if ($unit <= 1) {
            return (int) round((float) $amount);
        }

        return (int) (round((float) $amount / $unit) * $unit);
    }

    /**
     * Format nominal to standard Indonesian Rupiah format.
     */
    public static function format(int|float $amount): string
    {
        $isNegative = $amount < 0;
        $absolute = abs((float) $amount);
        $formatted = number_format($absolute, 0, ',', '.');

        return ($isNegative ? 'Rp -' : 'Rp ') . $formatted;
    }

    /**
     * Convert integer nominal to Indonesian words (terbilang).
     */
    public static function terbilang(int $amount): string
    {
        if ($amount === 0) {
            return 'nol rupiah';
        }

        $isNegative = $amount < 0;
        $words = self::spell(abs($amount));

        return ($isNegative ? 'minus ' : '') . trim($words) . ' rupiah';
    }

    private static function spell(int $number): string
    {
        $digits = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        if ($number < 12) {
            return $digits[$number];
        }

        if ($number < 20) {
            return self::spell($number - 10) . ' belas';
        }

        if ($number < 100) {
            $ten = (int) ($number / 10);
            $rest = $number % 10;
            return self::spell($ten) . ' puluh' . ($rest > 0 ? ' ' . self::spell($rest) : '');
        }

        if ($number < 200) {
            return 'seratus' . ($number - 100 > 0 ? ' ' . self::spell($number - 100) : '');
        }

        if ($number < 1000) {
            $hundred = (int) ($number / 100);
            $rest = $number % 100;
            return self::spell($hundred) . ' ratus' . ($rest > 0 ? ' ' . self::spell($rest) : '');
        }

        if ($number < 2000) {
            return 'seribu' . ($number - 1000 > 0 ? ' ' . self::spell($number - 1000) : '');
        }

        if ($number < 1_000_000) {
            $thousand = (int) ($number / 1000);
            $rest = $number % 1000;
            return self::spell($thousand) . ' ribu' . ($rest > 0 ? ' ' . self::spell($rest) : '');
        }

        if ($number < 1_000_000_000) {
            $million = (int) ($number / 1_000_000);
            $rest = $number % 1_000_000;
            return self::spell($million) . ' juta' . ($rest > 0 ? ' ' . self::spell($rest) : '');
        }

        $billion = (int) ($number / 1_000_000_000);
        $rest = $number % 1_000_000_000;
        return self::spell($billion) . ' miliar' . ($rest > 0 ? ' ' . self::spell($rest) : '');
    }
}
