<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresAdminAccess;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reseller;
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
            'revenue' => Order::query()->sum('total'),
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

        return view('admin.dashboard', compact('stats', 'recentOrders', 'trend', 'trendValues'));
    }
}
