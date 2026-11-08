<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\StockMovementService;
use Illuminate\Support\Facades\Auth;

class ProductObserver
{
    public function created(Product $product): void
    {
        app(StockMovementService::class)->recordOpening($product, Auth::id());
    }

    public function updated(Product $product): void
    {
        if (! $product->wasChanged('stock')) {
            return;
        }

        app(StockMovementService::class)->recordAdjustment(
            $product,
            (int) $product->getOriginal('stock'),
            (int) $product->stock,
            Auth::id(),
        );
    }
}
