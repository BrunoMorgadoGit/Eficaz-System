<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Reseller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteService
{
    private readonly CommercialPolicyService $commercialPolicies;

    public function __construct(
        private readonly PricingService $pricing,
        ?CommercialPolicyService $commercialPolicies = null,
    ) {
        $this->commercialPolicies = $commercialPolicies ?? new CommercialPolicyService;
    }

    /**
     * Creates a persisted, immutable price snapshot from the current cart.
     *
     * Clearing the cart is deliberately an argument rather than an implicit
     * side effect. The web flow opts in with true after a successful quote.
     */
    public function createFromCart(Reseller $reseller, bool $clearCart = true): Quote
    {
        return DB::transaction(function () use ($reseller, $clearCart): Quote {
            $cart = Cart::query()
                ->where('reseller_id', $reseller->getKey())
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                $this->invalidCart('Seu carrinho está vazio. Adicione um produto antes de gerar o orçamento.');
            }

            $cartItems = CartItem::query()
                ->where('cart_id', $cart->getKey())
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                $this->invalidCart('Seu carrinho está vazio. Adicione um produto antes de gerar o orçamento.');
            }

            $products = Product::query()
                ->whereIn('id', $cartItems->pluck('product_id')->filter()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $quoteLines = [];

            foreach ($cartItems as $cartItem) {
                $product = $products->get($cartItem->product_id);

                if (! $product) {
                    $this->invalidCart('Um produto do carrinho não está mais disponível. Atualize o carrinho.');
                }

                $this->assertAvailableStock($product, (int) $cartItem->quantity);

                $unitPrice = $cartItem->unit_price ?? $product->price;
                $quoteLines[] = [
                    'product_id' => $product->getKey(),
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'description' => $product->description,
                    'unit_price' => $unitPrice,
                    'quantity' => (int) $cartItem->quantity,
                    'line_total' => $this->pricing->lineTotal($unitPrice, (int) $cartItem->quantity),
                ];
            }

            $policy = $this->commercialPolicies->forReseller($reseller);
            $promotion = $this->commercialPolicies->activePromotionFor($reseller, $policy->discount_percentage);
            $totals = $this->pricing->calculate($quoteLines, $policy->discount_percentage, $promotion);
            $quote = Quote::query()->create([
                'reseller_id' => $reseller->getKey(),
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'total' => $totals['total'],
                'commercial_profile' => $policy->commercial_profile,
                'profile_discount_percentage' => $totals['profile_discount_percentage'],
                'promotion_id' => $totals['promotion_id'],
                'promotion_name' => $totals['promotion_name'],
                'promotion_discount_percentage' => $totals['promotion_discount_percentage'],
                'promotion_is_stackable' => $totals['promotion_is_stackable'],
                'payment_terms' => $policy->payment_terms,
            ]);

            foreach ($quoteLines as $line) {
                QuoteItem::query()->create([
                    'quote_id' => $quote->getKey(),
                    ...$line,
                ]);
            }

            if ($clearCart) {
                CartItem::query()->where('cart_id', $cart->getKey())->delete();
            }

            return $quote->load(['items', 'reseller']);
        }, 3);
    }

    private function assertAvailableStock(Product $product, int $quantity): void
    {
        if (! $product->is_active) {
            $this->invalidCart("O produto {$product->sku} está inativo.");
        }

        if ($quantity < 1 || $quantity > (int) $product->stock) {
            $this->invalidCart("O estoque disponível para o SKU {$product->sku} não atende a quantidade do carrinho.");
        }
    }

    private function invalidCart(string $message): never
    {
        throw ValidationException::withMessages(['cart' => $message]);
    }
}
