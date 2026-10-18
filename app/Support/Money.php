<?php

namespace App\Support;

/**
 * Conversion between integer cents (storage) and decimal strings (UI).
 */
class Money
{
    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, '.', ',');
    }

    /** Longest whole part accepted, so the result always fits in an integer. */
    private const MAX_WHOLE_DIGITS = 15;

    /**
     * Parse a user-entered amount such as "12", "12.5" or "1,234.50" into cents.
     * Commas are only accepted as thousands separators, so "12,50" is rejected instead of becoming 1250.00.
     * Returns null when the input is not a valid non-negative amount with at most two decimals.
     */
    public static function toCents(string $amount): ?int
    {
        $amount = trim($amount);

        if (! preg_match('/^(\d{1,3}(,\d{3})+|\d+)(\.\d{1,2})?$/', $amount)) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', str_replace(',', '', $amount)), 2, '');

        if (strlen(ltrim($whole, '0')) > self::MAX_WHOLE_DIGITS) {
            return null;
        }

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function basisPointsToPercent(int $basisPoints): string
    {
        return rtrim(rtrim(number_format($basisPoints / 100, 2, '.', ''), '0'), '.');
    }

    /** A percentage has the same shape as an amount: 19.5% -> 1950 basis points. */
    public static function percentToBasisPoints(string $percent): ?int
    {
        return self::toCents($percent);
    }
}
