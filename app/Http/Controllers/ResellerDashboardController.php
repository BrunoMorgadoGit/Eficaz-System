<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentReseller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Quote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ResellerDashboardController extends Controller
{
    use ResolvesCurrentReseller;

    public function index(Request $request): View
    {
        $reseller = $this->currentReseller($request);
        $reseller->loadMissing('user');
        $cart = Cart::query()->where('reseller_id', $reseller->getKey())->first();
        $monthStart = now()->startOfMonth();
        $nextMonthStart = $monthStart->copy()->addMonth();
        $previousMonthStart = $monthStart->copy()->subMonth();

        $stats = [
            'cart_items' => $cart
                ? CartItem::query()->where('cart_id', $cart->getKey())->sum('quantity')
                : 0,
            'quotes_count' => Quote::query()->where('reseller_id', $reseller->getKey())->count(),
            'open_quotes_count' => Quote::query()
                ->where('reseller_id', $reseller->getKey())
                ->whereDoesntHave('order')
                ->count(),
            'orders_count' => Order::query()->where('reseller_id', $reseller->getKey())->count(),
            'orders_this_month' => Order::query()
                ->where('reseller_id', $reseller->getKey())
                ->where('created_at', '>=', $monthStart)
                ->where('created_at', '<', $nextMonthStart)
                ->count(),
            'orders_previous_month' => Order::query()
                ->where('reseller_id', $reseller->getKey())
                ->where('created_at', '>=', $previousMonthStart)
                ->where('created_at', '<', $monthStart)
                ->count(),
            'total_spent' => Order::query()->where('reseller_id', $reseller->getKey())->sum('total'),
        ];

        $recentOrders = Order::query()
            ->where('reseller_id', $reseller->getKey())
            ->with('items')
            ->latest()
            ->limit(5)
            ->get();

        $recentQuotes = Quote::query()
            ->where('reseller_id', $reseller->getKey())
            ->with('order')
            ->withCount('items')
            ->latest()
            ->limit(5)
            ->get();

        return view('reseller.dashboard', compact('reseller', 'stats', 'recentOrders', 'recentQuotes'));
    }
}
