<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentReseller;
use App\Http\Requests\SearchProductRequest;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class ResellerProductController extends Controller
{
    use ResolvesCurrentReseller;

    public function index(SearchProductRequest $request): View
    {
        $this->currentReseller($request);
        $searchedSku = $request->validated('sku');

        $products = Product::query()
            ->where('is_active', true)
            ->when($searchedSku, fn ($query) => $query->where('sku', $searchedSku))
            ->orderBy('sku')
            ->paginate(12)
            ->withQueryString();

        $searchFeedback = $searchedSku && $products->isEmpty()
            ? "Nenhum produto disponível foi encontrado para o SKU {$searchedSku}."
            : null;

        return view('reseller.products.index', compact('products', 'searchedSku', 'searchFeedback'));
    }
}
