<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentReseller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Quote;
use App\Services\CommercialPolicyService;
use App\Services\PricingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ResellerDashboardController extends Controller
{
    use ResolvesCurrentReseller;

    public function __construct(
        private readonly CommercialPolicyService $commercialPolicies,
        private readonly PricingService $pricing,
    ) {}

    public function index(Request $request): View
    {
        $reseller = $this->currentReseller($request);
        $reseller->loadMissing('user');
        $commercialPolicy = $this->commercialPolicies->forReseller($reseller);
        $activePromotion = $this->commercialPolicies->activePromotionFor($reseller, $commercialPolicy->discount_percentage);
        $cart = Cart::query()->where('reseller_id', $reseller->getKey())->first();
        $monthStart = now()->startOfMonth();
        $nextMonthStart = $monthStart->copy()->addMonth();
        $previousMonthStart = $monthStart->copy()->subMonth();

        $stats = [
            'cart_items' => $cart
                ? (int) CartItem::query()->where('cart_id', $cart->getKey())->sum('quantity')
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
            'total_orders' => Order::query()->where('reseller_id', $reseller->getKey())->sum('total'),
            'committed_credit' => Order::query()
                ->where('reseller_id', $reseller->getKey())
                ->whereIn('status', [Order::STATUS_PENDENTE, Order::STATUS_APROVADO])
                ->sum('total'),
        ];

        $creditLimitCents = $reseller->credit_limit === null ? null : $this->pricing->toCents($reseller->credit_limit);
        $committedCreditCents = $this->pricing->toCents($stats['committed_credit']);
        $stats['available_credit'] = $creditLimitCents === null
            ? null
            : $this->pricing->fromCents(max(0, $creditLimitCents - $committedCreditCents));
        $stats['committed_credit'] = $this->pricing->fromCents($committedCreditCents);
        $stats['total_orders'] = $this->pricing->fromCents($this->pricing->toCents($stats['total_orders']));

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

        return view('reseller.dashboard', compact('reseller', 'stats', 'recentOrders', 'recentQuotes', 'commercialPolicy', 'activePromotion'));
    }
}
