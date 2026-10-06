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
        $allowedStatuses = ['TODOS', 'PENDENTE', 'APROVADO', 'CONCLUIDO'];
        $requestedStatus = strtoupper(trim((string) $request->query('status', 'TODOS')));
        $activeStatus = in_array($requestedStatus, $allowedStatuses, true) ? $requestedStatus : 'TODOS';

        $countsByStatus = Order::query()
            ->where('reseller_id', $reseller->getKey())
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statusCounts = [
            'TODOS' => (int) $countsByStatus->sum(),
            'PENDENTE' => (int) $countsByStatus->get(Order::STATUS_PENDENTE, 0),
            'APROVADO' => (int) $countsByStatus->get(Order::STATUS_APROVADO, 0),
            'CONCLUIDO' => (int) $countsByStatus->get(Order::STATUS_CONCLUIDO, 0),
        ];

        $ordersQuery = Order::query()
            ->where('reseller_id', $reseller->getKey())
            ->when($activeStatus !== 'TODOS', fn ($query) => $query->where('status', $activeStatus));

        $orders = (clone $ordersQuery)
            ->withCount('items')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $selectedOrderId = filter_var($request->query('selected'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $selectedOrder = $selectedOrderId
            ? (clone $ordersQuery)->with('items')->find($selectedOrderId)
            : null;

        return view('reseller.orders.index', compact('orders', 'activeStatus', 'statusCounts', 'selectedOrder'));
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
