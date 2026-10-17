<?php

namespace App\Services;

/**
 * Pure money maths for quotes and orders. All amounts are integer cents and
 * tax rates are basis points (1900 = 19.00%), so no floats are ever involved.
 */
class TotalsCalculator
{
    public const BASIS_POINTS = 10000;

    public function lineTotal(int $quantity, int $unitPriceCents): int
    {
        return $quantity * $unitPriceCents;
    }

    /**
     * Tax is rounded half up to the nearest cent, once, on the whole subtotal.
     */
    public function tax(int $subtotalCents, int $taxRateBp): int
    {
        return intdiv($subtotalCents * $taxRateBp + intdiv(self::BASIS_POINTS, 2), self::BASIS_POINTS);
    }

    /**
     * @param  iterable<array{quantity: int, unit_price_cents: int}>  $lines
     * @return array{subtotal_cents: int, tax_cents: int, total_cents: int}
     */
    public function totals(iterable $lines, int $taxRateBp): array
    {
        $subtotal = 0;

        foreach ($lines as $line) {
            $subtotal += $this->lineTotal($line['quantity'], $line['unit_price_cents']);
        }

        $tax = $this->tax($subtotal, $taxRateBp);

        return [
            'subtotal_cents' => $subtotal,
            'tax_cents' => $tax,
            'total_cents' => $subtotal + $tax,
        ];
    }
}
