<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AdminResellerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ResellerDashboardController;
use App\Http\Controllers\ResellerProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    if (! $request->user()) {
        return redirect()->route('login');
    }

    return $request->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('reseller.dashboard');
})->name('home');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/perfil', [AuthController::class, 'profile'])->name('profile');
});

Route::middleware(['auth', 'role:REVENDEDOR'])
    ->as('reseller.')
    ->group(function (): void {
        Route::get('/dashboard', [ResellerDashboardController::class, 'index'])->name('dashboard');
        Route::get('/produtos', [ResellerProductController::class, 'index'])->name('products.index');

        Route::get('/carrinho', [CartController::class, 'index'])->name('cart.index');
        Route::post('/carrinho/itens', [CartController::class, 'store'])->name('cart.items.store');
        Route::patch('/carrinho/itens/{item}', [CartController::class, 'update'])->name('cart.items.update');
        Route::delete('/carrinho/itens/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');

        Route::post('/orcamentos', [QuoteController::class, 'store'])->name('quotes.store');
        Route::get('/orcamentos/{quote}', [QuoteController::class, 'show'])->name('quotes.show');

        Route::post('/orcamentos/{quote}/pedido', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/pedidos', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');
    });

Route::prefix('admin')
    ->middleware(['auth', 'role:ADMIN'])
    ->as('admin.')
    ->group(function (): void {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/produtos', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('/produtos/novo', [AdminProductController::class, 'create'])->name('products.create');
        Route::post('/produtos', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('/produtos/{product}/editar', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::patch('/produtos/{product}', [AdminProductController::class, 'update'])->name('products.update');

        Route::get('/revendedores', [AdminResellerController::class, 'index'])->name('resellers.index');
        Route::get('/pedidos', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::patch('/pedidos/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status.update');
    });
