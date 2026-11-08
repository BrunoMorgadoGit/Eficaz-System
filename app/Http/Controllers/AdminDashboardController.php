<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresAdminAccess;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    use EnsuresAdminAccess;

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $stats = [
            'products' => Product::query()->where('is_active', true)->count(),
            'resellers' => Reseller::query()->count(),
            'orders' => Order::query()->count(),
            'completed_orders' => Order::query()->where('status', Order::STATUS_CONCLUIDO)->count(),
            'completed_revenue' => Order::query()->where('status', Order::STATUS_CONCLUIDO)->sum('total'),
        ];

        $recentOrders = Order::query()
            ->with(['reseller.user', 'items'])
            ->latest()
            ->limit(8)
            ->get();

        $firstMonth = now()->startOfMonth()->subMonths(5);
        $ordersByMonth = collect(range(0, 5))
            ->mapWithKeys(fn (int $offset): array => [$firstMonth->copy()->addMonths($offset)->format('Y-m') => 0]);

        Order::query()
            ->where('created_at', '>=', $firstMonth)
            ->get(['created_at'])
            ->each(function (Order $order) use ($ordersByMonth): void {
                $month = $order->created_at->format('Y-m');

                if ($ordersByMonth->has($month)) {
                    $ordersByMonth->put($month, $ordersByMonth->get($month) + 1);
                }
            });

        $trend = [
            'labels' => collect(range(0, 5))->map(
                fn (int $offset): string => $firstMonth->copy()->addMonths($offset)->translatedFormat('M'),
            )->all(),
            'values' => $ordersByMonth->values()->all(),
        ];
        $trendValues = $trend['values'];

        $skuVolumes = OrderItem::query()
            ->select(['product_id', 'sku', 'name'])
            ->selectRaw('SUM(order_items.quantity) AS units_ordered')
            ->whereHas('order', function ($query) use ($firstMonth): void {
                $query->where('created_at', '>=', $firstMonth);
            })
            ->groupBy('product_id', 'sku', 'name')
            ->orderByDesc('units_ordered')
            ->limit(8)
            ->get();

        $skuProductIds = $skuVolumes->pluck('product_id')->filter()->values();
        $currentStockByProductId = Product::query()
            ->whereIn('id', $skuProductIds)
            ->pluck('stock', 'id');
        $stockTrackingByProductId = StockMovement::query()
            ->whereIn('product_id', $skuProductIds)
            ->select('product_id')
            ->selectRaw('MIN(CASE WHEN movement_type = ? THEN created_at END) AS tracked_since', [StockMovement::TYPE_BASELINE])
            ->selectRaw('MAX(CASE WHEN movement_type = ? THEN stock_after END) AS opening_stock', [StockMovement::TYPE_BASELINE])
            ->selectRaw('SUM(CASE WHEN movement_type = ? THEN ABS(quantity_change) ELSE 0 END) AS units_issued_since_tracking', [StockMovement::TYPE_ORDER_ISSUE])
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $skuVolumes->each(function ($skuVolume) use ($currentStockByProductId, $stockTrackingByProductId): void {
            $productId = $skuVolume->product_id;
            $currentStock = $productId === null ? null : $currentStockByProductId->get($productId);
            $stockTracking = $productId === null ? null : $stockTrackingByProductId->get($productId);
            $openingStock = $stockTracking?->opening_stock;
            $averageStock = $openingStock === null || $currentStock === null
                ? 0
                : (((int) $openingStock + (int) $currentStock) / 2);

            $skuVolume->current_stock = $currentStock;
            $skuVolume->tracked_since = $stockTracking?->tracked_since;
            $skuVolume->stock_turnover = $averageStock > 0
                ? ((int) ($stockTracking?->units_issued_since_tracking ?? 0)) / $averageStock
                : null;
        });

        $activeResellers = Order::query()
            ->select('reseller_id')
            ->selectRaw('COUNT(*) AS completed_orders')
            ->selectRaw('SUM(total) AS completed_revenue')
            ->with('reseller')
            ->where('status', Order::STATUS_CONCLUIDO)
            ->where('created_at', '>=', $firstMonth)
            ->groupBy('reseller_id')
            ->orderByDesc('completed_revenue')
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'trend', 'trendValues', 'skuVolumes', 'activeResellers'));
    }
}
