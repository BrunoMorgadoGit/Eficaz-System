<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresAdminAccess;
use App\Http\Requests\SavePromotionRequest;
use App\Models\Promotion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminPromotionController extends Controller
{
    use EnsuresAdminAccess;

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);
        $promotions = Promotion::query()
            ->orderByDesc('is_active')
            ->orderByDesc('starts_on')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.promotions.index', compact('promotions'));
    }

    public function store(SavePromotionRequest $request): RedirectResponse
    {
        $this->ensureAdmin($request);
        Promotion::query()->create($request->validated());

        return redirect()->route('admin.promotions.index')->with('success', 'Promoção cadastrada. Ela só entra em orçamentos dentro do período configurado.');
    }

    public function update(SavePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $this->ensureAdmin($request);
        $promotion->update($request->validated());

        return redirect()->route('admin.promotions.index')->with('success', 'Promoção atualizada. Orçamentos já emitidos mantêm o snapshot original.');
    }

    public function destroy(Request $request, Promotion $promotion): RedirectResponse
    {
        $this->ensureAdmin($request);
        $promotion->delete();

        return redirect()->route('admin.promotions.index')->with('success', 'Promoção removida. Orçamentos e pedidos existentes preservam os dados comerciais registrados.');
    }
}
