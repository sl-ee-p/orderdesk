<?php

namespace Tests\Unit;

use App\Services\TotalsCalculator;
use PHPUnit\Framework\TestCase;

class TotalsCalculatorTest extends TestCase
{
    public function test_it_sums_lines_and_applies_tax(): void
    {
        $totals = (new TotalsCalculator)->totals([
            ['quantity' => 2, 'unit_price_cents' => 1000],
            ['quantity' => 1, 'unit_price_cents' => 550],
        ], 2200);

        $this->assertSame(['subtotal_cents' => 2550, 'tax_cents' => 561, 'total_cents' => 3111], $totals);
    }

    public function test_tax_is_rounded_half_up_to_the_cent(): void
    {
        $calculator = new TotalsCalculator;

        $this->assertSame(1, $calculator->tax(50, 100));  // 0.5 cent -> 1
        $this->assertSame(0, $calculator->tax(49, 100));  // 0.49 cent -> 0
    }

    public function test_tax_on_large_subtotals_does_not_overflow(): void
    {
        $calculator = new TotalsCalculator;

        // 429,496,729,500,123.40 at 19.5% = 83,751,862,252,524.063, rounded to the cent.
        $this->assertSame(8375186225252406, $calculator->tax(42949672950012340, 1950));
        $this->assertSame(42949672950012340, $calculator->tax(42949672950012340, 10000));
    }

    public function test_no_lines_gives_zero_totals(): void
    {
        $this->assertSame(
            ['subtotal_cents' => 0, 'tax_cents' => 0, 'total_cents' => 0],
            (new TotalsCalculator)->totals([], 2200),
        );
    }
}
