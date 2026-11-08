<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Reseller;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_product_create_and_stock_adjustment_are_recorded(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'sku' => ' qa-move ',
                'name' => 'Produto auditado',
                'description' => null,
                'price' => '15.00',
                'stock' => 8,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('sku', 'QA-MOVE')->sole();
        $baseline = StockMovement::query()->where('product_id', $product->id)->sole();

        $this->assertSame(StockMovement::TYPE_BASELINE, $baseline->movement_type);
        $this->assertSame(0, $baseline->quantity_change);
        $this->assertSame(8, $baseline->stock_before);
        $this->assertSame(8, $baseline->stock_after);
        $this->assertSame($admin->id, $baseline->performed_by);

        $this->patch(route('admin.products.update', $product), [
            'sku' => 'QA-MOVE',
            'name' => 'Produto auditado',
            'description' => null,
            'price' => '15.00',
            'stock' => 5,
            'is_active' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $adjustment = StockMovement::query()
            ->where('product_id', $product->id)
            ->where('movement_type', StockMovement::TYPE_ADJUSTMENT)
            ->sole();

        $this->assertSame(-3, $adjustment->quantity_change);
        $this->assertSame(8, $adjustment->stock_before);
        $this->assertSame(5, $adjustment->stock_after);
        $this->assertSame($admin->id, $adjustment->performed_by);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_order_issue_is_atomic_and_dashboard_reports_stock_turnover(): void
    {
        $resellerUser = User::factory()->reseller()->create();
        $reseller = Reseller::query()->create([
            'user_id' => $resellerUser->id,
            'company_name' => 'Parceiro do Giro',
            'commercial_profile' => Reseller::PROFILE_STANDARD,
            'credit_limit' => '1000.00',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'sku' => 'TURN-1',
            'name' => 'Produto com giro',
            'price' => '10.00',
            'stock' => 10,
            'is_active' => true,
        ]);
        $quote = Quote::query()->create([
            'reseller_id' => $reseller->id,
            'subtotal' => '40.00',
            'discount' => '0.00',
            'total' => '40.00',
            'commercial_profile' => Reseller::PROFILE_STANDARD,
            'profile_discount_percentage' => 0,
            'payment_terms' => '30 dias',
        ]);
        QuoteItem::query()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'unit_price' => '10.00',
            'quantity' => 4,
            'line_total' => '40.00',
        ]);

        $order = (new OrderService(new PricingService))->createFromQuote($reseller, $quote, $resellerUser->id);
        $issue = StockMovement::query()
            ->where('movement_type', StockMovement::TYPE_ORDER_ISSUE)
            ->sole();

        $this->assertSame(-4, $issue->quantity_change);
        $this->assertSame(10, $issue->stock_before);
        $this->assertSame(6, $issue->stock_after);
        $this->assertSame($order->id, $issue->order_id);
        $this->assertSame($resellerUser->id, $issue->performed_by);
        $this->assertSame(6, $product->fresh()->stock);

        $admin = User::factory()->admin()->create();
        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $response->assertViewHas('skuVolumes', fn ($volumes): bool => $volumes->contains(
            fn ($volume): bool => $volume->sku === 'TURN-1'
                && (int) $volume->units_ordered === 4
                && (int) $volume->current_stock === 6
                && (float) $volume->stock_turnover === 0.5
                && $volume->tracked_since !== null,
        ));
        $response->assertSee('Giro estimado');
        $response->assertSee('0,50x');

        $invalidQuote = Quote::query()->create([
            'reseller_id' => $reseller->id,
            'subtotal' => '70.00',
            'discount' => '0.00',
            'total' => '70.00',
            'commercial_profile' => Reseller::PROFILE_STANDARD,
            'profile_discount_percentage' => 0,
            'payment_terms' => '30 dias',
        ]);
        QuoteItem::query()->create([
            'quote_id' => $invalidQuote->id,
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'unit_price' => '10.00',
            'quantity' => 7,
            'line_total' => '70.00',
        ]);

        $movementCount = StockMovement::query()->count();

        try {
            (new OrderService(new PricingService))->createFromQuote($reseller, $invalidQuote);
            $this->fail('An order above current stock must be rejected.');
        } catch (ValidationException) {
            $this->assertSame(6, $product->fresh()->stock);
            $this->assertSame($movementCount, StockMovement::query()->count());
            $this->assertSame(1, Order::query()->count());
        }
    }
}
