<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\User;
use App\Services\CartService;
use App\Services\PricingService;
use App\Services\QuoteService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CartQuoteServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_is_created_once_and_adding_a_product_updates_its_quantity_and_price(): void
    {
        $reseller = $this->makeReseller(Reseller::PROFILE_GOLD);
        $product = $this->makeProduct();
        $carts = new CartService(new PricingService);

        $cart = $carts->cartFor($reseller);
        $this->assertSame($cart->id, $carts->cartFor($reseller)->id);
        $this->assertSame(1, Cart::query()->where('reseller_id', $reseller->id)->count());

        $firstItem = $carts->addItem($reseller, $product, 2);
        $product->update(['price' => '20.00']);
        $updatedItem = $carts->addItem($reseller, $product, 2);

        $this->assertSame($firstItem->id, $updatedItem->id);
        $this->assertSame(4, $updatedItem->quantity);
        $this->assertSame('20.00', $updatedItem->unit_price);
        $this->assertSame(1, CartItem::query()->count());

        $summary = $carts->summary($cart, $reseller);
        $this->assertSame('80.00', $summary['subtotal']);
        $this->assertSame('4.00', $summary['discount']);
        $this->assertSame('76.00', $summary['total']);
        $this->assertSame(4, $summary['item_count']);
    }

    public function test_cart_quantity_update_refreshes_price_and_removal_deletes_the_item(): void
    {
        $reseller = $this->makeReseller();
        $product = $this->makeProduct();
        $carts = new CartService(new PricingService);
        $item = $carts->addItem($reseller, $product, 2);
        $product->update(['price' => '15.00']);

        $updated = $carts->updateItem($reseller, $item, 3);
        $this->assertSame(3, $updated->quantity);
        $this->assertSame('15.00', $updated->unit_price);

        $carts->removeItem($reseller, $updated);
        $this->assertSame(0, CartItem::query()->count());
    }

    public function test_cart_routes_update_remove_and_validate_item_quantities(): void
    {
        $reseller = $this->makeReseller();
        $product = $this->makeProduct();
        $item = (new CartService(new PricingService))->addItem($reseller, $product, 2);

        $this->actingAs($reseller->user)
            ->patch(route('reseller.cart.items.update', $item), ['quantity' => 3])
            ->assertRedirect(route('reseller.cart.index'));
        $this->assertSame(3, $item->fresh()->quantity);

        $this->patch(route('reseller.cart.items.update', $item), ['quantity' => 0])
            ->assertSessionHasErrors('quantity');
        $this->assertSame(3, $item->fresh()->quantity);

        $this->delete(route('reseller.cart.items.destroy', $item))
            ->assertRedirect(route('reseller.cart.index'));
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_cart_mutations_reject_products_that_are_inactive_or_over_stock(): void
    {
        $reseller = $this->makeReseller();
        $carts = new CartService(new PricingService);
        $inactiveProduct = $this->makeProduct(isActive: false);

        try {
            $carts->addItem($reseller, $inactiveProduct, 1);
            $this->fail('An inactive product must not be added to the cart.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('product_id', $exception->errors());
        }

        $activeProduct = $this->makeProduct(sku: 'STOCK-1', stock: 2);
        try {
            $carts->addItem($reseller, $activeProduct, 3);
            $this->fail('Cart quantity must not exceed available stock.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity', $exception->errors());
        }

        $this->assertSame(0, CartItem::query()->count());
    }

    public function test_cart_item_cannot_be_updated_or_removed_by_another_reseller(): void
    {
        $owner = $this->makeReseller();
        $otherReseller = $this->makeReseller('GOLD', 'other@example.test');
        $item = (new CartService(new PricingService))->addItem($owner, $this->makeProduct(), 1);
        $carts = new CartService(new PricingService);

        try {
            $carts->updateItem($otherReseller, $item, 2);
            $this->fail('A reseller must not update another reseller cart.');
        } catch (AuthorizationException) {
            $this->assertSame(1, $item->fresh()->quantity);
        }

        try {
            $carts->removeItem($otherReseller, $item);
            $this->fail('A reseller must not remove another reseller cart item.');
        } catch (AuthorizationException) {
            $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
        }
    }

    public function test_quote_uses_cart_price_snapshot_and_can_preserve_the_cart_when_requested(): void
    {
        $reseller = $this->makeReseller(Reseller::PROFILE_GOLD);
        $product = $this->makeProduct(price: '10.00');
        $cart = (new CartService(new PricingService))->cartFor($reseller);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'unit_price' => '9.00',
            'quantity' => 2,
        ]);

        $quote = (new QuoteService(new PricingService))->createFromCart($reseller, clearCart: false);

        $this->assertSame('18.00', $quote->subtotal);
        $this->assertSame('0.90', $quote->discount);
        $this->assertSame('17.10', $quote->total);
        $this->assertSame('9.00', $quote->items->sole()->unit_price);
        $this->assertSame('18.00', $quote->items->sole()->line_total);
        $this->assertSame(1, CartItem::query()->count());

        $product->update(['name' => 'Nome atualizado', 'price' => '25.00']);
        $this->assertSame('Produto de teste', $quote->items->sole()->name);
        $this->assertSame('9.00', $quote->items->sole()->unit_price);
    }

    public function test_quote_rejects_a_missing_or_empty_cart(): void
    {
        $reseller = $this->makeReseller();
        $quotes = new QuoteService(new PricingService);

        try {
            $quotes->createFromCart($reseller);
            $this->fail('A reseller without a cart cannot create a quote.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cart', $exception->errors());
        }

        (new CartService(new PricingService))->cartFor($reseller);
        try {
            $quotes->createFromCart($reseller);
            $this->fail('An empty cart cannot create a quote.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cart', $exception->errors());
        }
    }

    public function test_quote_rejects_products_that_become_inactive_or_insufficiently_stocked(): void
    {
        $reseller = $this->makeReseller();
        $product = $this->makeProduct(stock: 3);
        $carts = new CartService(new PricingService);
        $carts->addItem($reseller, $product, 2);
        $quotes = new QuoteService(new PricingService);

        $product->update(['is_active' => false]);
        try {
            $quotes->createFromCart($reseller);
            $this->fail('An inactive product must not be quoted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cart', $exception->errors());
        }

        $product->update(['is_active' => true, 'stock' => 1]);
        try {
            $quotes->createFromCart($reseller);
            $this->fail('A quote must not exceed current stock.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cart', $exception->errors());
        }

        $this->assertSame(0, $reseller->quotes()->count());
        $this->assertSame(1, CartItem::query()->count());
    }

    private function makeReseller(string $profile = Reseller::PROFILE_STANDARD, string $email = 'reseller@example.test'): Reseller
    {
        $user = User::factory()->reseller()->create(['email' => $email]);

        return Reseller::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Empresa de teste',
            'commercial_profile' => $profile,
            'credit_limit' => null,
            'is_active' => true,
        ]);
    }

    private function makeProduct(
        string $sku = 'TEST-1',
        bool $isActive = true,
        int $stock = 10,
        string $price = '10.00',
    ): Product {
        return Product::query()->create([
            'sku' => $sku,
            'name' => 'Produto de teste',
            'price' => $price,
            'stock' => $stock,
            'is_active' => $isActive,
        ]);
    }
}
