<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Reseller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private readonly PricingService $pricing) {}

    /**
     * Converts exactly one owned quotation into one order.
     *
     * Stock is validated under a database lock, but deliberately is not
     * decremented: inventory integration is outside this academic MVP.
     */
    public function createFromQuote(Reseller $reseller, Quote $quote): Order
    {
        return DB::transaction(function () use ($reseller, $quote): Order {
            $lockedQuote = Quote::query()->lockForUpdate()->findOrFail($quote->getKey());

            if ((int) $lockedQuote->reseller_id !== (int) $reseller->getKey()) {
                throw new AuthorizationException('Este orçamento não pertence ao seu perfil de revendedor.');
            }

            if (Order::query()->where('quote_id', $lockedQuote->getKey())->lockForUpdate()->exists()) {
                $this->invalidQuote('Este orçamento já foi convertido em pedido.');
            }

            $quoteItems = QuoteItem::query()
                ->where('quote_id', $lockedQuote->getKey())
                ->lockForUpdate()
                ->get();

            if ($quoteItems->isEmpty()) {
                $this->invalidQuote('O orçamento não possui itens para converter em pedido.');
            }

            if ($quoteItems->contains(fn (QuoteItem $item): bool => ! $item->product_id)) {
                $this->invalidQuote('O orçamento contém um produto inválido e não pode ser convertido.');
            }

            $products = Product::query()
                ->whereIn('id', $quoteItems->pluck('product_id')->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quoteItems as $quoteItem) {
                $product = $products->get($quoteItem->product_id);

                if (! $product || ! $product->is_active) {
                    $this->invalidQuote("O SKU {$quoteItem->sku} não está disponível para pedido.");
                }

                if ((int) $quoteItem->quantity < 1 || (int) $quoteItem->quantity > (int) $product->stock) {
                    $this->invalidQuote("O estoque disponível para o SKU {$quoteItem->sku} não atende este orçamento.");
                }

                if (! $this->pricing->amountsMatch(
                    $quoteItem->line_total,
                    $this->pricing->lineTotal($quoteItem->unit_price, (int) $quoteItem->quantity),
                )) {
                    $this->invalidQuote('Os valores dos itens do orçamento estão inconsistentes.');
                }
            }

            $this->assertFrozenTotalsAreConsistent($lockedQuote, $quoteItems);

            $order = Order::query()->create([
                'reseller_id' => $reseller->getKey(),
                'quote_id' => $lockedQuote->getKey(),
                'status' => 'PENDENTE',
                'subtotal' => $lockedQuote->subtotal,
                'discount' => $lockedQuote->discount,
                'total' => $lockedQuote->total,
            ]);

            foreach ($quoteItems as $quoteItem) {
                OrderItem::query()->create([
                    'order_id' => $order->getKey(),
                    'product_id' => $quoteItem->product_id,
                    'sku' => $quoteItem->sku,
                    'name' => $quoteItem->name,
                    'description' => $quoteItem->description,
                    'unit_price' => $quoteItem->unit_price,
                    'quantity' => $quoteItem->quantity,
                    'line_total' => $quoteItem->line_total,
                ]);
            }

            return $order->load(['items', 'quote', 'reseller']);
        }, 3);
    }

    /** @param Collection<int, QuoteItem> $quoteItems */
    private function assertFrozenTotalsAreConsistent(Quote $quote, $quoteItems): void
    {
        $itemSubtotal = $this->pricing->sumLineTotals($quoteItems);

        if (! $this->pricing->amountsMatch($quote->subtotal, $itemSubtotal)) {
            $this->invalidQuote('O subtotal do orçamento está inconsistente.');
        }

        $subtotalCents = $this->pricing->toCents($quote->subtotal);
        $discountCents = $this->pricing->toCents($quote->discount);
        $totalCents = $this->pricing->toCents($quote->total);

        if ($subtotalCents < 0 || $discountCents < 0 || $discountCents > $subtotalCents || $totalCents !== $subtotalCents - $discountCents) {
            $this->invalidQuote('Os totais congelados do orçamento estão inconsistentes.');
        }
    }

    private function invalidQuote(string $message): never
    {
        throw ValidationException::withMessages(['quote' => $message]);
    }
}
