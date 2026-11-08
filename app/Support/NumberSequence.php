<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class NumberSequence
{
    /**
     * Atomically generate the next sequential document number (BR-30).
     */
    public static function next(string $docType, ?string $period = null): string
    {
        $docType = strtoupper(trim($docType));

        if ($period === null) {
            $period = match ($docType) {
                'RSV' => Carbon::now()->format('Ymd'),
                default => Carbon::now()->format('Ym'),
            };
        }

        $nextNumber = DB::transaction(function () use ($docType, $period): int {
            $record = DB::table('number_sequences')
                ->where('doc_type', $docType)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                DB::table('number_sequences')->insert([
                    'doc_type' => $docType,
                    'period' => $period,
                    'last_number' => 1,
                    'updated_at' => now(),
                ]);

                return 1;
            }

            $incremented = (int) $record->last_number + 1;

            DB::table('number_sequences')
                ->where('id', $record->id)
                ->update([
                    'last_number' => $incremented,
                    'updated_at' => now(),
                ]);

            return $incremented;
        });

        return self::format($docType, $period, $nextNumber);
    }

    public static function nextReservationNumber(?Carbon $date = null): string
    {
        return self::next('RSV', $date?->format('Ymd'));
    }

    public static function nextInvoiceNumber(?Carbon $date = null): string
    {
        return self::next('INV', $date?->format('Ym'));
    }

    public static function nextReceiptNumber(?Carbon $date = null): string
    {
        return self::next('KWT', $date?->format('Ym'));
    }

    private static function format(string $docType, string $period, int $number): string
    {
        $padded = sprintf('%04d', $number);

        return match ($docType) {
            'RSV' => "RSV-{$period}-{$padded}",
            'INV' => sprintf(
                'INV/ASRI/%s/%s/%s',
                substr($period, 0, 4),
                substr($period, 4, 2),
                $padded
            ),
            'KWT' => sprintf(
                'KWT/ASRI/%s/%s/%s',
                substr($period, 0, 4),
                substr($period, 4, 2),
                $padded
            ),
            default => "{$docType}-{$period}-{$padded}",
        };
    }
}
