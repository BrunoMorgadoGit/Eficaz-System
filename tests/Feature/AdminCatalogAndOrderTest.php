<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCatalogAndOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_a_product(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk();
        $this->get(route('admin.products.create'))->assertOk();

        $this->post(route('admin.products.store'), [
            'sku' => ' sensor x1 ',
            'name' => ' Sensor de teste ',
            'description' => ' Descrição do produto ',
            'price' => '20,50',
            'stock' => 5,
            'is_active' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('sku', 'SENSOR-X1')->sole();
        $this->assertSame('Sensor de teste', $product->name);
        $this->assertSame('Descrição do produto', $product->description);
        $this->assertSame('20.50', $product->price);

        $this->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('SENSOR-X1');

        $this->patch(route('admin.products.update', $product), [
            'sku' => 'sensor x1',
            'name' => 'Sensor atualizado',
            'description' => '',
            'price' => '22.00',
            'stock' => 0,
            'is_active' => '0',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sku' => 'SENSOR-X1',
            'name' => 'Sensor atualizado',
            'description' => null,
            'price' => '22.00',
            'stock' => 0,
            'is_active' => false,
        ]);
    }

    public function test_product_validation_rejects_duplicate_skus_and_invalid_prices(): void
    {
        $admin = User::factory()->admin()->create();
        Product::query()->create([
            'sku' => 'DUPLICADO',
            'name' => 'Produto existente',
            'price' => '10.00',
            'stock' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'sku' => 'duplicado',
                'name' => 'Produto repetido',
                'price' => '10.999',
                'stock' => -1,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors(['sku', 'price', 'stock']);

        $this->assertSame(1, Product::query()->count());
    }

    public function test_admin_dashboard_and_reseller_list_include_persisted_data(): void
    {
        $admin = User::factory()->admin()->create();
        [, $reseller] = $this->makeReseller();
        $product = $this->makeProduct('DASH-1', true);
        $this->makeProduct('DASH-2', false);
        $this->makeOrder($reseller, Order::STATUS_CONCLUIDO, $product);

        $dashboard = $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
        $dashboard->assertViewHas('stats', fn (array $stats): bool => $stats['products'] === 1
            && $stats['resellers'] === 1
            && $stats['orders'] === 1
            && $stats['completed_orders'] === 1
            && (float) $stats['completed_revenue'] === 190.0
        );
        $dashboard->assertViewHas('trendValues', fn (array $values): bool => array_sum($values) === 1);
        $dashboard->assertViewHas('skuVolumes', fn ($rows): bool => $rows->sole()->sku === 'DASH-1'
            && (int) $rows->sole()->units_ordered === 2
            && (int) $rows->sole()->current_stock === 5
        );
        $dashboard->assertViewHas('activeResellers', fn ($rows): bool => $rows->sole()->reseller->is($reseller)
            && (int) $rows->sole()->completed_orders === 1
        );

        $this->get(route('admin.resellers.index'))
            ->assertOk()
            ->assertSee('Revendedor de teste')
            ->assertSee('revendedor@example.test')
            ->assertSee('Não informado')
            ->assertDontSee('R$ 0,00');
    }

    public function test_admin_can_update_reseller_group_and_credit_limit(): void
    {
        $admin = User::factory()->admin()->create();
        [, $reseller] = $this->makeReseller();

        $this->actingAs($admin)
            ->get(route('admin.resellers.edit', $reseller))
            ->assertOk()
            ->assertSee('Condições do revendedor');

        $this->patch(route('admin.resellers.update', $reseller), [
            'company_name' => '  Revendedor Ajustado  ',
            'commercial_profile' => ' gold ',
            'credit_limit' => '12500.50',
        ])->assertRedirect(route('admin.resellers.index'));

        $this->assertDatabaseHas('resellers', [
            'id' => $reseller->id,
            'company_name' => 'Revendedor Ajustado',
            'commercial_profile' => Reseller::PROFILE_GOLD,
            'credit_limit' => '12500.50',
        ]);

        $this->patch(route('admin.resellers.update', $reseller), [
            'company_name' => 'Revendedor Ajustado',
            'commercial_profile' => Reseller::PROFILE_GOLD,
        ])->assertSessionHasErrors('credit_limit');

        $this->patch(route('admin.resellers.update', $reseller), [
            'company_name' => 'Revendedor Ajustado',
            'commercial_profile' => Reseller::PROFILE_GOLD,
            'credit_limit' => '-1.00',
        ])->assertSessionHasErrors('credit_limit');

        $this->assertSame('12500.50', $reseller->fresh()->credit_limit);
    }

    public function test_admin_cannot_reduce_credit_below_open_order_commitments(): void
    {
        $admin = User::factory()->admin()->create();
        [, $reseller] = $this->makeReseller('credito@example.test');
        $this->makeOrder($reseller, Order::STATUS_PENDENTE);

        $this->actingAs($admin)
            ->patch(route('admin.resellers.update', $reseller), [
                'company_name' => $reseller->company_name,
                'commercial_profile' => $reseller->commercial_profile,
                'credit_limit' => '100.00',
            ])
            ->assertSessionHasErrors('credit_limit');

        $this->assertNull($reseller->fresh()->credit_limit);
    }

    public function test_admin_can_update_order_status_and_invalid_values_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        [, $reseller] = $this->makeReseller();
        $order = $this->makeOrder($reseller, Order::STATUS_PENDENTE);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Revendedor de teste');

        $this->patch(route('admin.orders.status.update', $order), ['status' => ' aprovado '])
            ->assertRedirect(route('admin.orders.index'));
        $this->assertSame(Order::STATUS_APROVADO, $order->fresh()->status);

        $this->patch(route('admin.orders.status.update', $order), ['status' => 'CANCELADO'])
            ->assertSessionHasErrors('status');
        $this->assertSame(Order::STATUS_APROVADO, $order->fresh()->status);

        $this->patch(route('admin.orders.status.update', $order), ['status' => Order::STATUS_PENDENTE])
            ->assertSessionHasErrors('status');
        $this->assertSame(Order::STATUS_APROVADO, $order->fresh()->status);
    }

    public function test_resellers_cannot_access_administration_catalog_or_orders(): void
    {
        [$resellerUser, $reseller] = $this->makeReseller();
        $order = $this->makeOrder($reseller, Order::STATUS_PENDENTE);

        $this->actingAs($resellerUser)
            ->get(route('admin.products.index'))
            ->assertForbidden();
        $this->get(route('admin.orders.index'))->assertForbidden();
        $this->get(route('admin.resellers.edit', $reseller))->assertForbidden();
        $this->patch(route('admin.resellers.update', $reseller), [
            'company_name' => 'Alteração não autorizada',
            'commercial_profile' => Reseller::PROFILE_PREMIUM,
            'credit_limit' => '50000.00',
        ])->assertForbidden();
        $this->patch(route('admin.orders.status.update', $order), ['status' => 'APROVADO'])
            ->assertForbidden();
    }

    public function test_reseller_order_filters_and_selection_are_limited_to_their_orders(): void
    {
        [$resellerUser, $reseller] = $this->makeReseller('reseller@example.test');
        [, $otherReseller] = $this->makeReseller('other@example.test');
        $this->makeOrder($reseller, Order::STATUS_PENDENTE);
        $approvedOrder = $this->makeOrder($reseller, Order::STATUS_APROVADO);
        $otherOrder = $this->makeOrder($otherReseller, Order::STATUS_APROVADO);

        $filtered = $this->actingAs($resellerUser)
            ->get(route('reseller.orders.index', [
                'status' => ' aprovado ',
                'selected' => $approvedOrder->id,
            ]))
            ->assertOk();
        $filtered->assertViewHas('activeStatus', 'APROVADO');
        $filtered->assertViewHas('statusCounts', fn (array $counts): bool => $counts['TODOS'] === 2 && $counts['APROVADO'] === 1 && $counts['PENDENTE'] === 1
        );
        $filtered->assertViewHas('orders', fn ($orders): bool => $orders->count() === 1 && $orders->getCollection()->sole()->id === $approvedOrder->id
        );
        $filtered->assertViewHas('selectedOrder', fn ($selected): bool => $selected?->id === $approvedOrder->id);

        $invalidFilter = $this->get(route('reseller.orders.index', [
            'status' => 'CANCELADO',
            'selected' => $otherOrder->id,
        ]))->assertOk();
        $invalidFilter->assertViewHas('activeStatus', 'TODOS');
        $invalidFilter->assertViewHas('selectedOrder', null);
        $this->get(route('reseller.orders.show', $otherOrder))->assertForbidden();
        $this->get(route('reseller.quotes.show', $otherOrder->quote))->assertForbidden();
    }

    public function test_reseller_dashboard_counts_only_its_quotes_orders_and_cart(): void
    {
        [$resellerUser, $reseller] = $this->makeReseller('reseller@example.test');
        [, $otherReseller] = $this->makeReseller('other@example.test');
        $product = $this->makeProduct('RESELLER-DASH', true);
        $ownOrder = $this->makeOrder($reseller, Order::STATUS_PENDENTE, $product);
        $this->makeOrder($otherReseller, Order::STATUS_CONCLUIDO);
        Quote::query()->create([
            'reseller_id' => $reseller->id,
            'subtotal' => '50.00',
            'discount' => '0.00',
            'total' => '50.00',
        ]);
        $cart = Cart::query()->create(['reseller_id' => $reseller->id]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'unit_price' => '100.00',
            'quantity' => 3,
        ]);

        $response = $this->actingAs($resellerUser)->get(route('reseller.dashboard'))->assertOk();
        $response->assertViewHas('stats', fn (array $stats): bool => $stats['cart_items'] === 3
            && $stats['quotes_count'] === 2
            && $stats['open_quotes_count'] === 1
            && $stats['orders_count'] === 1
            && (float) $stats['total_orders'] === 190.0
            && (float) $stats['committed_credit'] === 190.0
        );
        $this->assertSame($reseller->id, $ownOrder->reseller_id);
    }

    /** @return array{0: User, 1: Reseller} */
    private function makeReseller(string $email = 'revendedor@example.test'): array
    {
        $user = User::factory()->reseller()->create(['email' => $email]);
        $reseller = Reseller::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Revendedor de teste',
            'commercial_profile' => Reseller::PROFILE_STANDARD,
            'credit_limit' => null,
            'is_active' => true,
        ]);

        return [$user, $reseller];
    }

    private function makeProduct(string $sku, bool $isActive): Product
    {
        return Product::query()->create([
            'sku' => $sku,
            'name' => 'Produto '.$sku,
            'price' => '100.00',
            'stock' => 5,
            'is_active' => $isActive,
        ]);
    }

    private function makeOrder(Reseller $reseller, string $status, ?Product $product = null): Order
    {
        $quote = Quote::query()->create([
            'reseller_id' => $reseller->id,
            'subtotal' => '200.00',
            'discount' => '10.00',
            'total' => '190.00',
        ]);

        $order = Order::query()->create([
            'reseller_id' => $reseller->id,
            'quote_id' => $quote->id,
            'status' => $status,
            'subtotal' => '200.00',
            'discount' => '10.00',
            'total' => '190.00',
        ]);

        if ($product) {
            $order->items()->create([
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'unit_price' => '100.00',
                'quantity' => 2,
                'line_total' => '200.00',
            ]);
        }

        return $order;
    }
}
