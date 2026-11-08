<?php

declare(strict_types=1);

namespace App\Support;

class PhoneNormalizer
{
    /**
     * Normalize an Indonesian phone number into standard 628xxxxxxxx format.
     * Returns null if empty or cannot be matched to a valid Indonesian mobile number.
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $cleaned = trim($phone);
        if ($cleaned === '') {
            return null;
        }

        // Strip non-digits
        $digits = preg_replace('/\D+/', '', $cleaned) ?? '';
        if ($digits === '') {
            return null;
        }

        // Convert leading 08 -> 628
        if (str_starts_with($digits, '08')) {
            $digits = '628' . substr($digits, 2);
        } elseif (str_starts_with($digits, '8')) {
            // Convert leading 8 -> 628
            $digits = '628' . substr($digits, 1);
        }

        // Validate: must start with 628 and have 10-14 digits in total
        if (! preg_match('/^628\d{8,11}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    /**
     * Check if a phone number is a valid Indonesian mobile number.
     */
    public static function isValid(?string $phone): bool
    {
        return self::normalize($phone) !== null;
    }
}
