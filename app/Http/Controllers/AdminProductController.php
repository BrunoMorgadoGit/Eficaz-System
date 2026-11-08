<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresAdminAccess;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminProductController extends Controller
{
    use EnsuresAdminAccess;

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);
        $products = Product::query()->orderBy('sku')->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    public function create(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('admin.products.create');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->ensureAdmin($request);
        DB::transaction(fn (): Product => Product::query()->create($request->validated()));

        return redirect()->route('admin.products.index')->with('success', 'Produto cadastrado com sucesso.');
    }

    public function edit(Request $request, Product $product): View
    {
        $this->ensureAdmin($request);

        return view('admin.products.edit', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->ensureAdmin($request);
        DB::transaction(function () use ($request, $product): void {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->getKey());
            $lockedProduct->update($request->validated());
        }, 3);

        return redirect()->route('admin.products.index')->with('success', 'Produto atualizado com sucesso.');
    }
}
