<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Reseller;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PricingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_conversion_preserves_quote_snapshot_and_does_not_decrement_stock(): void
    {
        ['reseller' => $reseller, 'product' => $product, 'quote' => $quote] = $this->makeQuote();
        $product->update(['name' => 'Nome atual do catálogo', 'price' => '99.00']);

        $order = (new OrderService(new PricingService))->createFromQuote($reseller, $quote);

        $this->assertSame(Order::STATUS_PENDENTE, $order->status);
        $this->assertSame('20.00', $order->subtotal);
        $this->assertSame('20.00', $order->total);
        $this->assertSame('Produto do orçamento', $order->items->sole()->name);
        $this->assertSame('10.00', $order->items->sole()->unit_price);
        $this->assertSame('20.00', $order->items->sole()->line_total);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_order_conversion_rejects_a_quote_owned_by_another_reseller(): void
    {
        ['quote' => $quote] = $this->makeQuote();
        $otherReseller = $this->makeReseller('outro@example.test');

        $this->expectException(AuthorizationException::class);

        (new OrderService(new PricingService))->createFromQuote($otherReseller, $quote);
    }

    public function test_order_conversion_rejects_a_quote_without_items(): void
    {
        $reseller = $this->makeReseller();
        $quote = Quote::query()->create([
            'reseller_id' => $reseller->id,
            'subtotal' => '0.00',
            'discount' => '0.00',
            'total' => '0.00',
        ]);

        $this->expectException(ValidationException::class);

        (new OrderService(new PricingService))->createFromQuote($reseller, $quote);
    }

    public function test_order_conversion_rejects_a_deleted_or_inactive_product(): void
    {
        ['quote' => $deletedProductQuote, 'product' => $deletedProduct] = $this->makeQuote('deleted@example.test');
        $deletedProduct->delete();
        $this->assertNull($deletedProductQuote->items()->sole()->product_id);

        try {
            (new OrderService(new PricingService))->createFromQuote(
                $deletedProductQuote->reseller,
                $deletedProductQuote,
            );
            $this->fail('An item whose product was deleted cannot be ordered.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quote', $exception->errors());
        }

        ['quote' => $inactiveQuote, 'product' => $inactiveProduct, 'reseller' => $reseller] = $this->makeQuote('inactive@example.test');
        $inactiveProduct->update(['is_active' => false]);

        try {
            (new OrderService(new PricingService))->createFromQuote($reseller, $inactiveQuote);
            $this->fail('An inactive product cannot be ordered.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quote', $exception->errors());
        }
    }

    public function test_order_conversion_rejects_insufficient_stock_and_non_positive_quantities(): void
    {
        ['quote' => $quote, 'product' => $product, 'reseller' => $reseller] = $this->makeQuote();
        $product->update(['stock' => 1]);

        try {
            (new OrderService(new PricingService))->createFromQuote($reseller, $quote);
            $this->fail('An order cannot exceed current stock.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quote', $exception->errors());
        }

        $product->update(['stock' => 5]);
        $quote->items()->sole()->update(['quantity' => 0]);

        try {
            (new OrderService(new PricingService))->createFromQuote($reseller, $quote);
            $this->fail('An order item must have a positive quantity.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quote', $exception->errors());
        }
    }

    public function test_order_conversion_rejects_inconsistent_line_and_quote_subtotals(): void
    {
        ['quote' => $lineTotalQuote, 'reseller' => $reseller] = $this->makeQuote('line@example.test');
        $lineTotalQuote->items()->sole()->update(['line_total' => '19.00']);

        try {
            (new OrderService(new PricingService))->createFromQuote($reseller, $lineTotalQuote);
            $this->fail('A line total that does not match unit price and quantity must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quote', $exception->errors());
        }

        ['quote' => $subtotalQuote, 'reseller' => $subtotalReseller] = $this->makeQuote('subtotal@example.test');
        $subtotalQuote->update(['subtotal' => '21.00']);

        try {
            (new OrderService(new PricingService))->createFromQuote($subtotalReseller, $subtotalQuote);
            $this->fail('A subtotal that differs from the item sum must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quote', $exception->errors());
        }
    }

    public function test_order_conversion_rejects_frozen_totals_that_do_not_balance(): void
    {
        ['quote' => $quote, 'reseller' => $reseller] = $this->makeQuote();
        $quote->update(['discount' => '5.00', 'total' => '16.00']);

        try {
            (new OrderService(new PricingService))->createFromQuote($reseller, $quote);
            $this->fail('A quote total must equal subtotal minus discount.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quote', $exception->errors());
        }

        $quote->update(['discount' => '21.00', 'total' => '0.00']);
        try {
            (new OrderService(new PricingService))->createFromQuote($reseller, $quote);
            $this->fail('A discount cannot exceed the subtotal.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quote', $exception->errors());
        }
    }

    private function makeQuote(string $email = 'reseller@example.test'): array
    {
        $reseller = $this->makeReseller($email);
        $sku = 'SNAP-'.strtoupper(strtok($email, '@'));
        $product = Product::query()->create([
            'sku' => $sku,
            'name' => 'Produto atual do catálogo',
            'description' => 'Descrição atual',
            'price' => '10.00',
            'stock' => 5,
            'is_active' => true,
        ]);
        $quote = Quote::query()->create([
            'reseller_id' => $reseller->id,
            'subtotal' => '20.00',
            'discount' => '0.00',
            'total' => '20.00',
        ]);
        QuoteItem::query()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'sku' => $sku,
            'name' => 'Produto do orçamento',
            'description' => 'Descrição congelada',
            'unit_price' => '10.00',
            'quantity' => 2,
            'line_total' => '20.00',
        ]);

        return compact('product', 'quote', 'reseller');
    }

    private function makeReseller(string $email = 'reseller@example.test'): Reseller
    {
        $user = User::factory()->reseller()->create(['email' => $email]);

        return Reseller::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Empresa de teste',
            'commercial_profile' => Reseller::PROFILE_STANDARD,
            'credit_limit' => null,
            'is_active' => true,
        ]);
    }
}
