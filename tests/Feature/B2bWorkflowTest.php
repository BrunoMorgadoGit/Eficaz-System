<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class B2bWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_gold_reseller_can_turn_a_cart_into_exactly_one_order(): void
    {
        [$user, $reseller] = $this->makeReseller(Reseller::PROFILE_GOLD);
        $product = Product::query()->create([
            'sku' => 'SKU-100',
            'name' => 'Controlador de teste',
            'description' => 'Produto de teste.',
            'price' => '100.00',
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('reseller.cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 2,
            ])
            ->assertRedirect(route('reseller.cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->post(route('reseller.quotes.store'))
            ->assertRedirect();

        $quote = Quote::query()->where('reseller_id', $reseller->id)->sole();

        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'subtotal' => '200.00',
            'discount' => '10.00',
            'total' => '190.00',
        ]);
        $this->assertDatabaseMissing('cart_items', [
            'product_id' => $product->id,
        ]);

        $this->post(route('reseller.orders.store', $quote))
            ->assertRedirect();

        $order = Order::query()->where('quote_id', $quote->id)->sole();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PENDENTE,
            'subtotal' => '200.00',
            'discount' => '10.00',
            'total' => '190.00',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'sku' => 'SKU-100',
            'quantity' => 2,
            'line_total' => '200.00',
        ]);

        $this->post(route('reseller.orders.store', $quote))
            ->assertSessionHasErrors('quote');

        $this->assertSame(1, Order::query()->where('quote_id', $quote->id)->count());
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_a_reseller_cannot_add_a_product_without_stock(): void
    {
        [$user] = $this->makeReseller();
        $product = Product::query()->create([
            'sku' => 'SKU-SEM-ESTOQUE',
            'name' => 'Produto indisponível',
            'description' => null,
            'price' => '10.00',
            'stock' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('reseller.cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('product_id');

        $this->assertSame(0, CartItem::query()->count());
    }

    public function test_roles_are_separated_at_the_route_level(): void
    {
        $admin = User::factory()->admin()->create();
        [$resellerUser] = $this->makeReseller();

        $this->actingAs($admin)
            ->get(route('reseller.dashboard'))
            ->assertForbidden();

        $this->actingAs($resellerUser)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    /** @return array{0: User, 1: Reseller} */
    private function makeReseller(string $profile = Reseller::PROFILE_STANDARD): array
    {
        $user = User::factory()->reseller()->create();
        $reseller = Reseller::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Distribuidora de Teste Ltda.',
            'commercial_profile' => $profile,
            'credit_limit' => '10000.00',
            'is_active' => true,
        ]);

        return [$user, $reseller];
    }
}
