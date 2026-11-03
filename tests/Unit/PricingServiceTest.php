<?php

namespace Tests\Unit;

use App\Models\Reseller;
use App\Services\PricingService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PricingServiceTest extends TestCase
{
    public function test_it_applies_the_discount_for_each_commercial_profile(): void
    {
        $pricing = new PricingService;
        $items = [
            (object) ['unit_price' => '100.00', 'quantity' => 2],
            (object) ['unit_price' => '25.50', 'quantity' => 1],
        ];

        $standard = new Reseller(['commercial_profile' => Reseller::PROFILE_STANDARD]);
        $gold = new Reseller(['commercial_profile' => Reseller::PROFILE_GOLD]);
        $premium = new Reseller(['commercial_profile' => Reseller::PROFILE_PREMIUM]);

        $this->assertSame([
            'subtotal' => '225.50',
            'discount' => '0.00',
            'total' => '225.50',
            'discount_rate' => 0,
        ], $pricing->calculate($items, $standard));

        $this->assertSame('11.28', $pricing->calculate($items, $gold)['discount']);
        $this->assertSame('214.22', $pricing->calculate($items, $gold)['total']);
        $this->assertSame('22.55', $pricing->calculate($items, $premium)['discount']);
        $this->assertSame('202.95', $pricing->calculate($items, $premium)['total']);
    }

    public function test_it_normalizes_the_commercial_profile_before_calculating_the_discount(): void
    {
        $pricing = new PricingService;
        $reseller = new Reseller(['commercial_profile' => ' gold ']);

        $this->assertSame(5, $pricing->discountRateFor($reseller));
    }

    public function test_it_rounds_discount_to_the_nearest_cent(): void
    {
        $pricing = new PricingService;
        $reseller = new Reseller(['commercial_profile' => Reseller::PROFILE_GOLD]);

        $this->assertSame([
            'subtotal' => '10.10',
            'discount' => '0.51',
            'total' => '9.59',
            'discount_rate' => 5,
        ], $pricing->calculate([
            ['unit_price' => '10.10', 'quantity' => 1],
        ], $reseller));
    }

    public function test_it_calculates_line_totals_and_sums_them_in_cents(): void
    {
        $pricing = new PricingService;

        $this->assertSame('59.97', $pricing->lineTotal('19.99', 3));
        $this->assertSame('60.01', $pricing->sumLineTotals([
            ['line_total' => '59.97'],
            ['line_total' => '0.04'],
        ]));
    }

    public function test_it_compares_amounts_at_cent_precision(): void
    {
        $pricing = new PricingService;

        $this->assertTrue($pricing->amountsMatch('10.5', '10.50'));
        $this->assertFalse($pricing->amountsMatch('10.50', '10.51'));
    }

    #[DataProvider('supportedMoneyAmounts')]
    public function test_it_converts_supported_money_values_to_cents(mixed $amount, int $expectedCents): void
    {
        $this->assertSame($expectedCents, (new PricingService)->toCents($amount));
    }

    public static function supportedMoneyAmounts(): array
    {
        return [
            'integer amount' => [12, 1200],
            'decimal string' => ['12.34', 1234],
            'comma decimal string' => ['12,34', 1234],
            'float rounded half up' => [0.125, 13],
            'negative decimal string' => ['-0.50', -50],
        ];
    }

    #[DataProvider('invalidMoneyAmounts')]
    public function test_it_rejects_malformed_money_values(mixed $amount): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PricingService)->toCents($amount);
    }

    public static function invalidMoneyAmounts(): array
    {
        return [
            'empty string' => [''],
            'three decimal places' => ['1.234'],
            'currency symbol' => ['R$ 1,00'],
            'multiple separators' => ['1,2,3'],
            'explicit plus sign' => ['+1.00'],
            'null amount' => [null],
        ];
    }

    public function test_it_rejects_an_unknown_commercial_profile(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PricingService)->discountRateFor(new Reseller(['commercial_profile' => 'SILVER']));
    }

    #[DataProvider('nonPositiveQuantities')]
    public function test_it_rejects_non_positive_quantities_in_calculations(int $quantity): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PricingService)->calculate(
            [['unit_price' => '10.00', 'quantity' => $quantity]],
            new Reseller(['commercial_profile' => Reseller::PROFILE_STANDARD]),
        );
    }

    #[DataProvider('nonPositiveQuantities')]
    public function test_it_rejects_non_positive_quantities_for_a_line_total(int $quantity): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PricingService)->lineTotal('10.00', $quantity);
    }

    public static function nonPositiveQuantities(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
        ];
    }
}
