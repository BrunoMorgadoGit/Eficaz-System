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
        $cart = Cart::query()->where('reseller_id', $reseller->getKey())->first();

        $stats = [
            'cart_items' => $cart
                ? CartItem::query()->where('cart_id', $cart->getKey())->sum('quantity')
                : 0,
            'quotes_count' => Quote::query()->where('reseller_id', $reseller->getKey())->count(),
            'orders_count' => Order::query()->where('reseller_id', $reseller->getKey())->count(),
            'total_spent' => Order::query()->where('reseller_id', $reseller->getKey())->sum('total'),
        ];

        $recentOrders = Order::query()
            ->where('reseller_id', $reseller->getKey())
            ->with('items')
            ->latest()
            ->limit(5)
            ->get();

        return view('reseller.dashboard', compact('stats', 'recentOrders'));
    }
}
