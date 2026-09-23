<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentReseller;
use App\Models\Order;
use App\Models\Quote;
use App\Services\OrderService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ResolvesCurrentReseller;

    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): View
    {
        $reseller = $this->currentReseller($request);
        $orders = Order::query()
            ->where('reseller_id', $reseller->getKey())
            ->with('items')
            ->latest()
            ->paginate(12);

        return view('reseller.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        $reseller = $this->currentReseller($request);

        if ((int) $order->reseller_id !== (int) $reseller->getKey()) {
            throw new AuthorizationException('Este pedido não pertence ao seu perfil de revendedor.');
        }

        $order->load(['items', 'quote', 'reseller']);

        return view('reseller.orders.show', compact('order'));
    }

    public function store(Request $request, Quote $quote): RedirectResponse
    {
        $order = $this->orders->createFromQuote($this->currentReseller($request), $quote);

        return redirect()
            ->route('reseller.orders.show', $order)
            ->with('success', 'Pedido criado com sucesso. O estoque não foi baixado nesta demonstração do MVP.');
    }
}
