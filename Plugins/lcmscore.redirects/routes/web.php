<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\Redirects\Http\Controllers\AdminRedirectController;

// Admin Redirects & 404 Management Routes
Route::prefix('admin/redirects')->middleware(['web', 'auth'])->group(function (): void {
    Route::get('/', [AdminRedirectController::class, 'index'])->name('admin.redirects.index');
    Route::get('/create', [AdminRedirectController::class, 'create'])->name('admin.redirects.create');
    Route::post('/', [AdminRedirectController::class, 'store'])->name('admin.redirects.store');
    Route::get('/{id}/edit', [AdminRedirectController::class, 'edit'])->name('admin.redirects.edit');
    Route::put('/{id}', [AdminRedirectController::class, 'update'])->name('admin.redirects.update');
    Route::delete('/{id}', [AdminRedirectController::class, 'destroy'])->name('admin.redirects.destroy');
    Route::post('/{id}/toggle', [AdminRedirectController::class, 'toggle'])->name('admin.redirects.toggle');

    // 404 Actions
    Route::post('/404/clear', [AdminRedirectController::class, 'clear404Logs'])->name('admin.redirects.404.clear');
    Route::delete('/404/{id}', [AdminRedirectController::class, 'destroy404'])->name('admin.redirects.404.destroy');
    Route::post('/404/{id}/convert', [AdminRedirectController::class, 'convert404'])->name('admin.redirects.404.convert');
});
