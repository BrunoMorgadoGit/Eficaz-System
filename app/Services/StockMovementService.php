<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;

class StockMovementService
{
    public function recordOpening(Product $product, ?int $performedBy = null): StockMovement
    {
        $stock = (int) $product->stock;

        return $this->record(
            $product,
            StockMovement::TYPE_BASELINE,
            0,
            $stock,
            $stock,
            performedBy: $performedBy,
        );
    }

    public function recordAdjustment(
        Product $product,
        int $stockBefore,
        int $stockAfter,
        ?int $performedBy = null,
    ): ?StockMovement {
        $quantityChange = $stockAfter - $stockBefore;

        if ($quantityChange === 0) {
            return null;
        }

        return $this->record(
            $product,
            StockMovement::TYPE_ADJUSTMENT,
            $quantityChange,
            $stockBefore,
            $stockAfter,
            performedBy: $performedBy,
        );
    }

    public function recordOrderIssue(
        Product $product,
        int $quantity,
        int $stockBefore,
        int $stockAfter,
        Order $order,
        ?int $performedBy = null,
    ): StockMovement {
        return $this->record(
            $product,
            StockMovement::TYPE_ORDER_ISSUE,
            -$quantity,
            $stockBefore,
            $stockAfter,
            order: $order,
            performedBy: $performedBy,
        );
    }

    private function record(
        Product $product,
        string $movementType,
        int $quantityChange,
        int $stockBefore,
        int $stockAfter,
        ?Order $order = null,
        ?int $performedBy = null,
    ): StockMovement {
        return StockMovement::query()->create([
            'product_id' => $product->getKey(),
            'sku' => $product->sku,
            'movement_type' => $movementType,
            'quantity_change' => $quantityChange,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'order_id' => $order?->getKey(),
            'performed_by' => $performedBy,
        ]);
    }
}
