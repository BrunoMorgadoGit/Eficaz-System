<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentReseller;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ResolvesCurrentReseller;

    public function __construct(private readonly CartService $carts) {}

    public function index(Request $request): View
    {
        $reseller = $this->currentReseller($request);
        $cart = $this->carts->cartFor($reseller);
        $summary = $this->carts->summary($cart, $reseller);

        return view('reseller.cart.index', compact('cart', 'summary'));
    }

    public function store(AddCartItemRequest $request): RedirectResponse
    {
        $reseller = $this->currentReseller($request);
        $product = Product::query()->findOrFail($request->validated('product_id'));

        $this->carts->addItem($reseller, $product, (int) $request->validated('quantity'));

        return redirect()->route('reseller.cart.index')->with('success', 'Produto adicionado ao carrinho.');
    }

    public function update(UpdateCartItemRequest $request, CartItem $item): RedirectResponse
    {
        $reseller = $this->currentReseller($request);

        $this->carts->updateItem($reseller, $item, (int) $request->validated('quantity'));

        return redirect()->route('reseller.cart.index')->with('success', 'Quantidade atualizada.');
    }

    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        $reseller = $this->currentReseller($request);

        $this->carts->removeItem($reseller, $item);

        return redirect()->route('reseller.cart.index')->with('success', 'Item removido do carrinho.');
    }
}
