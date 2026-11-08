<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresAdminAccess;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\Reseller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminOrderController extends Controller
{
    use EnsuresAdminAccess;

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);
        $orders = Order::query()
            ->with(['reseller.user', 'items'])
            ->latest()
            ->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, DatabaseManager $database): RedirectResponse
    {
        $this->ensureAdmin($request);
        $targetStatus = $request->validated('status');
        $rank = [
            Order::STATUS_PENDENTE => 0,
            Order::STATUS_APROVADO => 1,
            Order::STATUS_CONCLUIDO => 2,
        ];

        $database->transaction(function () use ($order, $targetStatus, $rank): void {
            Reseller::query()->lockForUpdate()->findOrFail($order->reseller_id);
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($rank[$targetStatus] < $rank[$lockedOrder->status]) {
                throw ValidationException::withMessages([
                    'status' => 'O pedido não pode voltar para um status anterior. O estoque já foi baixado na emissão.',
                ]);
            }

            $lockedOrder->update(['status' => $targetStatus]);
        }, 3);

        return redirect()->route('admin.orders.index')->with('success', 'Status do pedido atualizado.');
    }
}
