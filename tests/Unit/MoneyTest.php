<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{string, ?int}>
     */
    public static function amounts(): array
    {
        return [
            'whole' => ['12', 1200],
            'one decimal' => ['12.5', 1250],
            'two decimals' => ['12.05', 1205],
            'thousands separator' => ['1,234.50', 123450],
            'several thousands separators' => ['1,234,567', 123456700],
            'surrounding whitespace' => [' 7.10 ', 710],
            'decimal comma' => ['12,50', null],
            'misplaced thousands separator' => ['1,2,3', null],
            'fifteen digit whole part' => ['999999999999999.99', 99999999999999999],
            'too large to fit in an integer' => ['99999999999999999999', null],
            'too many decimals' => ['1.234', null],
            'negative' => ['-5', null],
            'text' => ['abc', null],
            'empty' => ['', null],
        ];
    }

    #[DataProvider('amounts')]
    public function test_it_parses_amounts_to_cents(string $input, ?int $expected): void
    {
        $this->assertSame($expected, Money::toCents($input));
    }

    public function test_it_formats_cents(): void
    {
        $this->assertSame('1,234.50', Money::format(123450));
        $this->assertSame('0.05', Money::format(5));
    }

    public function test_it_converts_percentages_both_ways(): void
    {
        $this->assertSame(1950, Money::percentToBasisPoints('19.5'));
        $this->assertSame('19.5', Money::basisPointsToPercent(1950));
        $this->assertSame('22', Money::basisPointsToPercent(2200));
        $this->assertSame('0', Money::basisPointsToPercent(0));
    }
}
