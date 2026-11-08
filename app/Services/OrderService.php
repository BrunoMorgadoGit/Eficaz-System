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
    private readonly StockMovementService $stockMovements;

    public function __construct(
        private readonly PricingService $pricing,
        ?StockMovementService $stockMovements = null,
    ) {
        $this->stockMovements = $stockMovements ?? new StockMovementService;
    }

    /**
     * Converts exactly one owned quotation into one order.
     *
     * Stock and reseller credit are checked and updated under row locks so
     * two simultaneous order conversions cannot oversell or overcommit credit.
     */
    public function createFromQuote(Reseller $reseller, Quote $quote, ?int $performedBy = null): Order
    {
        return DB::transaction(function () use ($reseller, $quote, $performedBy): Order {
            $lockedReseller = Reseller::query()->lockForUpdate()->findOrFail($reseller->getKey());

            if (! $lockedReseller->is_active) {
                $this->invalidQuote('A conta de revendedor está inativa e não pode emitir pedidos.');
            }

            $lockedQuote = Quote::query()->lockForUpdate()->findOrFail($quote->getKey());

            if ((int) $lockedQuote->reseller_id !== (int) $lockedReseller->getKey()) {
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

            if (! is_string($lockedQuote->payment_terms) || trim($lockedQuote->payment_terms) === '') {
                $this->invalidQuote('A condição de pagamento deste orçamento não está configurada. Peça à administração para configurar o grupo comercial e gere um novo orçamento.');
            }

            $products = Product::query()
                ->whereIn('id', $quoteItems->pluck('product_id')->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $requestedByProduct = $quoteItems
                ->groupBy(fn (QuoteItem $item): int => (int) $item->product_id)
                ->map(fn ($items): int => (int) $items->sum('quantity'));

            foreach ($requestedByProduct as $productId => $quantity) {
                $product = $products->get($productId);

                if (! $product || ! $product->is_active) {
                    $quoteItem = $quoteItems->firstWhere('product_id', $productId);
                    $this->invalidQuote("O SKU {$quoteItem?->sku} não está disponível para pedido.");
                }

                if ($quantity < 1 || $quantity > (int) $product->stock) {
                    $quoteItem = $quoteItems->firstWhere('product_id', $productId);
                    $this->invalidQuote("O estoque disponível para o SKU {$quoteItem?->sku} não atende este orçamento.");
                }
            }

            foreach ($quoteItems as $quoteItem) {
                if (! $this->pricing->amountsMatch(
                    $quoteItem->line_total,
                    $this->pricing->lineTotal($quoteItem->unit_price, (int) $quoteItem->quantity),
                )) {
                    $this->invalidQuote('Os valores dos itens do orçamento estão inconsistentes.');
                }
            }

            $this->assertFrozenTotalsAreConsistent($lockedQuote, $quoteItems);

            if ($lockedReseller->credit_limit === null) {
                $this->invalidQuote('O limite de crédito ainda não foi definido para sua empresa. Solicite a configuração à administração.');
            }

            $committedCents = $this->pricing->toCents(Order::query()
                ->where('reseller_id', $lockedReseller->getKey())
                ->whereIn('status', [Order::STATUS_PENDENTE, Order::STATUS_APROVADO])
                ->sum('total'));
            $orderTotalCents = $this->pricing->toCents($lockedQuote->total);
            $creditLimitCents = $this->pricing->toCents($lockedReseller->credit_limit);

            if ($committedCents + $orderTotalCents > $creditLimitCents) {
                $availableCents = max(0, $creditLimitCents - $committedCents);
                $this->invalidQuote(sprintf(
                    'O pedido excede seu crédito disponível de R$ %s.',
                    number_format($availableCents / 100, 2, ',', '.'),
                ));
            }

            $order = Order::query()->create([
                'reseller_id' => $lockedReseller->getKey(),
                'quote_id' => $lockedQuote->getKey(),
                'status' => Order::STATUS_PENDENTE,
                'subtotal' => $lockedQuote->subtotal,
                'discount' => $lockedQuote->discount,
                'total' => $lockedQuote->total,
                'commercial_profile' => $lockedQuote->commercial_profile,
                'profile_discount_percentage' => $lockedQuote->profile_discount_percentage,
                'promotion_id' => $lockedQuote->promotion_id,
                'promotion_name' => $lockedQuote->promotion_name,
                'promotion_discount_percentage' => $lockedQuote->promotion_discount_percentage,
                'promotion_is_stackable' => $lockedQuote->promotion_is_stackable,
                'payment_terms' => $lockedQuote->payment_terms,
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

            foreach ($requestedByProduct as $productId => $quantity) {
                $product = $products->get($productId);
                $stockBefore = (int) $product->stock;
                $stockAfter = $stockBefore - $quantity;
                $product->updateQuietly(['stock' => $stockAfter]);
                $this->stockMovements->recordOrderIssue(
                    $product,
                    $quantity,
                    $stockBefore,
                    $stockAfter,
                    $order,
                    $performedBy,
                );
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
