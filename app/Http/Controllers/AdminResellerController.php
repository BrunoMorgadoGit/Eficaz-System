<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresAdminAccess;
use App\Http\Requests\UpdateResellerCommercialRequest;
use App\Models\Order;
use App\Models\Reseller;
use App\Services\PricingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminResellerController extends Controller
{
    use EnsuresAdminAccess;

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);
        $resellers = Reseller::query()
            ->with('user')
            ->orderBy('company_name')
            ->paginate(15);

        return view('admin.resellers.index', compact('resellers'));
    }

    public function edit(Request $request, Reseller $reseller): View
    {
        $this->ensureAdmin($request);
        $reseller->load('user');

        return view('admin.resellers.edit', compact('reseller'));
    }

    public function update(UpdateResellerCommercialRequest $request, Reseller $reseller, PricingService $pricing): RedirectResponse
    {
        $this->ensureAdmin($request);
        $attributes = $request->validated();

        DB::transaction(function () use ($reseller, $attributes, $pricing): void {
            $lockedReseller = Reseller::query()->lockForUpdate()->findOrFail($reseller->getKey());
            $committedCents = $pricing->toCents(Order::query()
                ->where('reseller_id', $lockedReseller->getKey())
                ->whereIn('status', [Order::STATUS_PENDENTE, Order::STATUS_APROVADO])
                ->sum('total'));
            $newLimit = $attributes['credit_limit'];
            $newLimitCents = $newLimit === null ? null : $pricing->toCents($newLimit);

            if ($committedCents > 0 && ($newLimitCents === null || $newLimitCents < $committedCents)) {
                throw ValidationException::withMessages([
                    'credit_limit' => 'O limite não pode ficar abaixo do crédito comprometido em pedidos pendentes ou aprovados.',
                ]);
            }

            $lockedReseller->update($attributes);
        }, 3);

        return redirect()->route('admin.resellers.index')->with('success', 'Perfil comercial e limite de crédito atualizados.');
    }
}
