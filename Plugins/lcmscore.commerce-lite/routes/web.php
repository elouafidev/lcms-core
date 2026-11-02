<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\CommerceLite\Http\Controllers\AdminOrderController;
use Plugins\CommerceLite\Http\Controllers\AdminProductController;
use Plugins\CommerceLite\Http\Controllers\PublicCartController;
use Plugins\CommerceLite\Http\Controllers\PublicCheckoutController;
use Plugins\CommerceLite\Http\Controllers\PublicShopController;

// Public Commerce Routes
Route::middleware(['web'])->group(function (): void {
    // Catalogue
    Route::get('/shop', [PublicShopController::class, 'index'])->name('commerce.shop');
    Route::get('/shop/{slug}', [PublicShopController::class, 'show'])->name('commerce.product');

    // Panier (Session Cart)
    Route::get('/cart', [PublicCartController::class, 'index'])->name('commerce.cart');
    Route::post('/cart/add', [PublicCartController::class, 'add'])->name('commerce.cart.add');
    Route::post('/cart/update', [PublicCartController::class, 'update'])->name('commerce.cart.update');
    Route::post('/cart/remove/{id}', [PublicCartController::class, 'remove'])->name('commerce.cart.remove');
    Route::post('/cart/clear', [PublicCartController::class, 'clear'])->name('commerce.cart.clear');

    // Tunnel de commande (Checkout)
    Route::get('/checkout', [PublicCheckoutController::class, 'index'])->name('commerce.checkout');
    Route::post('/checkout', [PublicCheckoutController::class, 'process'])->name('commerce.checkout.process');
    Route::get('/checkout/success/{order_number}', [PublicCheckoutController::class, 'success'])->name('commerce.checkout.success');
});

// Admin Commerce Routes
Route::prefix('admin/commerce')->middleware(['web', 'auth'])->group(function (): void {
    // Gestion des Commandes
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.commerce.orders.index');
    Route::get('/orders/{id}', [AdminOrderController::class, 'show'])->name('admin.commerce.orders.show');
    Route::post('/orders/{id}/status', [AdminOrderController::class, 'updateStatus'])->name('admin.commerce.orders.status');
    Route::delete('/orders/{id}', [AdminOrderController::class, 'destroy'])->name('admin.commerce.orders.destroy');

    // Gestion du Catalogue Produits
    Route::get('/products', [AdminProductController::class, 'index'])->name('admin.commerce.products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('admin.commerce.products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('admin.commerce.products.store');
    Route::get('/products/{id}/edit', [AdminProductController::class, 'edit'])->name('admin.commerce.products.edit');
    Route::put('/products/{id}', [AdminProductController::class, 'update'])->name('admin.commerce.products.update');
    Route::post('/products/{id}/toggle', [AdminProductController::class, 'toggle'])->name('admin.commerce.products.toggle');
    Route::delete('/products/{id}', [AdminProductController::class, 'destroy'])->name('admin.commerce.products.destroy');
});
