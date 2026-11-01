<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\Menus\Http\Controllers\AdminMenuController;

// Admin menus management routes
Route::middleware(['web', 'auth'])->prefix('admin/menus')->group(function (): void {
    Route::get('/', [AdminMenuController::class, 'index'])->name('admin.menus.index');
    Route::post('/', [AdminMenuController::class, 'store'])->name('admin.menus.store');
    Route::get('/{id}', [AdminMenuController::class, 'show'])->name('admin.menus.show');
    Route::post('/{id}/items', [AdminMenuController::class, 'storeItem'])->name('admin.menus.items.store');
    Route::delete('/{id}', [AdminMenuController::class, 'destroy'])->name('admin.menus.destroy');
    Route::delete('/items/{id}', [AdminMenuController::class, 'destroyItem'])->name('admin.menus.items.destroy');
});
