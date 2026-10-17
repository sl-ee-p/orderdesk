<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Conversion between integer cents (storage) and decimal strings (UI).
 */
class Money
{
    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, '.', ',');
    }

    /**
     * Parse a user-entered amount such as "12", "12.5" or "1,234.50" into cents.
     * Returns null when the input is not a valid non-negative amount with at most two decimals.
     */
    public static function toCents(string $amount): ?int
    {
        $amount = str_replace(',', '', trim($amount));

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $amount), 2, '');

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
