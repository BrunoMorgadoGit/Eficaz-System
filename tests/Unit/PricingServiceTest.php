<?php

namespace Tests\Unit;

use App\Models\Reseller;
use App\Services\PricingService;
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
}
