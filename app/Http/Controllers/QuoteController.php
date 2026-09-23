<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentReseller;
use App\Models\Quote;
use App\Services\QuoteService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    use ResolvesCurrentReseller;

    public function __construct(private readonly QuoteService $quotes) {}

    public function store(Request $request): RedirectResponse
    {
        $quote = $this->quotes->createFromCart($this->currentReseller($request), clearCart: true);

        return redirect()
            ->route('reseller.quotes.show', $quote)
            ->with('success', 'Orçamento gerado. O carrinho foi limpo após salvar os itens e valores.');
    }

    public function show(Request $request, Quote $quote): View
    {
        $reseller = $this->currentReseller($request);

        if ((int) $quote->reseller_id !== (int) $reseller->getKey()) {
            throw new AuthorizationException('Este orçamento não pertence ao seu perfil de revendedor.');
        }

        $quote->load(['items', 'reseller', 'order']);

        return view('reseller.quotes.show', compact('quote'));
    }
}
