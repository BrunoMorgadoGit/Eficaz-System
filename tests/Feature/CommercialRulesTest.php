<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CommercialPolicy;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Reseller;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PricingService;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CommercialRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_configure_group_discounts_and_payment_terms(): void
    {
        $admin = User::factory()->admin()->create();
        $gold = CommercialPolicy::query()->where('commercial_profile', Reseller::PROFILE_GOLD)->firstOrFail();

        $this->assertSame(0, CommercialPolicy::query()->where('commercial_profile', Reseller::PROFILE_STANDARD)->value('discount_percentage'));
        $this->assertSame(5, $gold->discount_percentage);
        $this->assertSame(10, CommercialPolicy::query()->where('commercial_profile', Reseller::PROFILE_PREMIUM)->value('discount_percentage'));

        $this->actingAs($admin)
            ->get(route('admin.commercial-policies.index'))
            ->assertOk()
            ->assertSee('Condições comerciais');

        $this->patch(route('admin.commercial-policies.update', $gold), [
            'discount_percentage' => 7,
        ])->assertSessionHasErrors('payment_terms');

        $this->patch(route('admin.commercial-policies.update', $gold), [
            'discount_percentage' => 7,
            'payment_terms' => '  30/60 dias  ',
        ])->assertRedirect(route('admin.commercial-policies.index'));

        $this->assertSame(7, $gold->fresh()->discount_percentage);
        $this->assertSame('30/60 dias', $gold->fresh()->payment_terms);
    }

    public function test_reseller_cannot_manage_commercial_rules_or_promotions(): void
    {
        $user = User::factory()->reseller()->create();
        $reseller = Reseller::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Parceiro teste',
            'commercial_profile' => Reseller::PROFILE_GOLD,
            'credit_limit' => '1000.00',
            'is_active' => true,
        ]);
        $gold = CommercialPolicy::query()->where('commercial_profile', Reseller::PROFILE_GOLD)->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.commercial-policies.index'))
            ->assertForbidden();
        $this->get(route('admin.promotions.index'))->assertForbidden();
        $this->patch(route('admin.commercial-policies.update', $gold), [
            'discount_percentage' => 99,
            'payment_terms' => 'Alteração não autorizada',
        ])->assertForbidden();

        $this->assertSame(5, $gold->fresh()->discount_percentage);
        $this->assertSame('Parceiro teste', $reseller->company_name);
    }

    public function test_admin_can_create_update_and_remove_a_group_promotion(): void
    {
        $admin = User::factory()->admin()->create();
        $this->assertSame(0, Promotion::query()->count());

        $payload = [
            'name' => '  Reposição de outono  ',
            'commercial_profile' => Reseller::PROFILE_GOLD,
            'discount_percentage' => 8,
            'starts_on' => today()->toDateString(),
            'ends_on' => today()->addDays(7)->toDateString(),
            'is_active' => '1',
            'is_stackable' => '0',
        ];

        $this->actingAs($admin)
            ->get(route('admin.promotions.index'))
            ->assertOk()
            ->assertSee('Nenhuma promoção cadastrada');
        $this->post(route('admin.promotions.store'), $payload)
            ->assertRedirect(route('admin.promotions.index'));

        $promotion = Promotion::query()->sole();
        $this->assertSame('Reposição de outono', $promotion->name);
        $this->assertTrue($promotion->is_active);
        $this->assertFalse($promotion->is_stackable);

        $this->patch(route('admin.promotions.update', $promotion), [...$payload, 'is_active' => '0', 'discount_percentage' => 9])
            ->assertRedirect(route('admin.promotions.index'));
        $this->assertSame(9, $promotion->fresh()->discount_percentage);
        $this->assertFalse($promotion->fresh()->is_active);

        $this->delete(route('admin.promotions.destroy', $promotion))
            ->assertRedirect(route('admin.promotions.index'));
        $this->assertSame(0, Promotion::query()->count());
    }

    public function test_quote_and_order_keep_group_promotion_and_payment_snapshots(): void
    {
        $policy = CommercialPolicy::query()->where('commercial_profile', Reseller::PROFILE_GOLD)->firstOrFail();
        $policy->update(['payment_terms' => '30/60 dias']);
        $user = User::factory()->reseller()->create();
        $reseller = Reseller::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Parceiro Gold',
            'commercial_profile' => Reseller::PROFILE_GOLD,
            'credit_limit' => '1000.00',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'sku' => 'PROMO-1',
            'name' => 'Produto promocional',
            'price' => '100.00',
            'stock' => 5,
            'is_active' => true,
        ]);
        $cart = Cart::query()->create(['reseller_id' => $reseller->id]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'unit_price' => '100.00',
            'quantity' => 2,
        ]);
        $promotion = Promotion::query()->create([
            'name' => 'Bônus Gold',
            'commercial_profile' => Reseller::PROFILE_GOLD,
            'discount_percentage' => 10,
            'starts_on' => today(),
            'ends_on' => today(),
            'is_active' => true,
            'is_stackable' => true,
        ]);

        $quote = (new QuoteService(new PricingService))->createFromCart($reseller);

        $this->assertSame('30.00', $quote->discount);
        $this->assertSame('170.00', $quote->total);
        $this->assertSame('Bônus Gold', $quote->promotion_name);
        $this->assertSame(5, $quote->profile_discount_percentage);
        $this->assertSame('30/60 dias', $quote->payment_terms);

        $policy->update(['discount_percentage' => 20, 'payment_terms' => 'Condição nova']);
        $promotion->update(['name' => 'Promoção atualizada', 'is_active' => false]);
        $order = (new OrderService(new PricingService))->createFromQuote($reseller, $quote);

        $this->assertSame('170.00', $order->total);
        $this->assertSame('Bônus Gold', $order->promotion_name);
        $this->assertSame('30/60 dias', $order->payment_terms);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_PENDENTE]);
    }

    public function test_promotion_must_be_in_date_and_the_greatest_active_offer_is_selected(): void
    {
        $policy = CommercialPolicy::query()->where('commercial_profile', Reseller::PROFILE_GOLD)->firstOrFail();
        $policy->update(['payment_terms' => '30 dias']);
        $user = User::factory()->reseller()->create();
        $reseller = Reseller::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Parceiro Gold',
            'commercial_profile' => Reseller::PROFILE_GOLD,
            'credit_limit' => '1000.00',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'sku' => 'DATE-1',
            'name' => 'Produto teste',
            'price' => '100.00',
            'stock' => 5,
            'is_active' => true,
        ]);
        $cart = Cart::query()->create(['reseller_id' => $reseller->id]);
        CartItem::query()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'unit_price' => '100.00', 'quantity' => 1]);

        Promotion::query()->create([
            'name' => 'Promoção futura',
            'commercial_profile' => Reseller::PROFILE_GOLD,
            'discount_percentage' => 90,
            'starts_on' => today()->addDay(),
            'ends_on' => today()->addDays(2),
            'is_active' => true,
            'is_stackable' => false,
        ]);
        Promotion::query()->create([
            'name' => 'Promoção menor',
            'commercial_profile' => Reseller::PROFILE_GOLD,
            'discount_percentage' => 8,
            'starts_on' => today(),
            'ends_on' => today(),
            'is_active' => true,
            'is_stackable' => false,
        ]);
        Promotion::query()->create([
            'name' => 'Promoção maior',
            'commercial_profile' => Reseller::PROFILE_GOLD,
            'discount_percentage' => 12,
            'starts_on' => today(),
            'ends_on' => today(),
            'is_active' => true,
            'is_stackable' => false,
        ]);
        Promotion::query()->create([
            'name' => 'Promoção acumulada',
            'commercial_profile' => Reseller::PROFILE_GOLD,
            'discount_percentage' => 8,
            'starts_on' => today(),
            'ends_on' => today(),
            'is_active' => true,
            'is_stackable' => true,
        ]);

        $quote = (new QuoteService(new PricingService))->createFromCart($reseller);

        $this->assertSame('Promoção acumulada', $quote->promotion_name);
        $this->assertSame('13.00', $quote->discount);
        $this->assertSame('87.00', $quote->total);
    }

    public function test_missing_payment_terms_are_visible_and_block_order_issue(): void
    {
        CommercialPolicy::query()
            ->where('commercial_profile', Reseller::PROFILE_STANDARD)
            ->update(['payment_terms' => null]);
        $user = User::factory()->reseller()->create();
        $reseller = Reseller::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Parceiro sem condição',
            'commercial_profile' => Reseller::PROFILE_STANDARD,
            'credit_limit' => '1000.00',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'sku' => 'NO-TERMS-1',
            'name' => 'Produto de teste',
            'price' => '100.00',
            'stock' => 2,
            'is_active' => true,
        ]);
        $cart = Cart::query()->create(['reseller_id' => $reseller->id]);
        CartItem::query()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'unit_price' => '100.00', 'quantity' => 1]);

        $quote = (new QuoteService(new PricingService))->createFromCart($reseller);

        $this->actingAs($user)
            ->get(route('reseller.quotes.show', $quote))
            ->assertOk()
            ->assertSee('A emissão está bloqueada')
            ->assertDontSee('Converter em pedido');

        try {
            (new OrderService(new PricingService))->createFromQuote($reseller, $quote);
            $this->fail('A quote without payment terms cannot be issued as an order.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quote', $exception->errors());
            $this->assertSame(2, $product->fresh()->stock);
            $this->assertSame(0, Order::query()->count());
        }
    }
}
