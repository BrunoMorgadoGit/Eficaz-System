<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Reseller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(private readonly PricingService $pricing) {}

    public function cartFor(Reseller $reseller): Cart
    {
        return Cart::query()->firstOrCreate(['reseller_id' => $reseller->getKey()]);
    }

    public function addItem(Reseller $reseller, Product $product, int $quantity): CartItem
    {
        return DB::transaction(function () use ($reseller, $product, $quantity): CartItem {
            $cart = Cart::query()
                ->where('reseller_id', $reseller->getKey())
                ->lockForUpdate()
                ->first() ?? Cart::query()->create(['reseller_id' => $reseller->getKey()]);

            $item = CartItem::query()
                ->where('cart_id', $cart->getKey())
                ->where('product_id', $product->getKey())
                ->lockForUpdate()
                ->first();

            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->getKey());
            $newQuantity = $quantity + ($item?->quantity ?? 0);
            $this->assertCanBeAdded($lockedProduct, $newQuantity);

            if ($item) {
                $item->fill([
                    'quantity' => $newQuantity,
                    'unit_price' => $lockedProduct->price,
                ])->save();
            } else {
                $item = CartItem::query()->create([
                    'cart_id' => $cart->getKey(),
                    'product_id' => $lockedProduct->getKey(),
                    'quantity' => $newQuantity,
                    'unit_price' => $lockedProduct->price,
                ]);
            }

            return $item->load('product');
        }, 3);
    }

    public function updateItem(Reseller $reseller, CartItem $item, int $quantity): CartItem
    {
        return DB::transaction(function () use ($reseller, $item, $quantity): CartItem {
            $cart = $this->lockedCartForItem($item, $reseller);
            $lockedItem = CartItem::query()
                ->whereKey($item->getKey())
                ->where('cart_id', $cart->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $product = Product::query()->lockForUpdate()->findOrFail($lockedItem->product_id);
            $this->assertCanBeAdded($product, $quantity);

            $lockedItem->fill([
                'quantity' => $quantity,
                'unit_price' => $product->price,
            ])->save();

            return $lockedItem->load('product');
        }, 3);
    }

    public function removeItem(Reseller $reseller, CartItem $item): void
    {
        DB::transaction(function () use ($reseller, $item): void {
            $cart = $this->lockedCartForItem($item, $reseller);
            $lockedItem = CartItem::query()
                ->whereKey($item->getKey())
                ->where('cart_id', $cart->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedItem->delete();
        }, 3);
    }

    /**
     * @return array{items: Collection<int, CartItem>, subtotal: string, discount: string, total: string, discount_rate: int, item_count: int}
     */
    public function summary(Cart $cart, Reseller $reseller): array
    {
        $cart->loadMissing('items.product');
        $items = $cart->items;
        $totals = $this->pricing->calculate($items, $reseller);

        return [
            'items' => $items,
            ...$totals,
            'item_count' => $items->sum('quantity'),
        ];
    }

    private function lockedCartForItem(CartItem $item, Reseller $reseller): Cart
    {
        $cart = Cart::query()
            ->whereKey($item->cart_id)
            ->where('reseller_id', $reseller->getKey())
            ->lockForUpdate()
            ->first();

        if (! $cart) {
            throw new AuthorizationException('Este item não pertence ao seu carrinho.');
        }

        return $cart;
    }

    private function assertCanBeAdded(Product $product, int $quantity): void
    {
        if (! $product->is_active) {
            throw ValidationException::withMessages([
                'product_id' => 'Este produto está inativo e não pode ser adicionado ao carrinho.',
            ]);
        }

        if ((int) $product->stock < 1) {
            throw ValidationException::withMessages([
                'product_id' => 'Este produto está sem estoque.',
            ]);
        }

        if ($quantity < 1 || $quantity > (int) $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => 'A quantidade informada é maior que o estoque disponível.',
            ]);
        }
    }
}
