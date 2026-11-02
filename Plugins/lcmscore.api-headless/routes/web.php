<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\ApiHeadless\Http\Controllers\AdminApiTokenController;

// Admin API Tokens Management Routes
Route::prefix('admin/api-tokens')->middleware(['web', 'auth'])->group(function (): void {
    Route::get('/', [AdminApiTokenController::class, 'index'])->name('admin.api-tokens.index');
    Route::post('/', [AdminApiTokenController::class, 'store'])->name('admin.api-tokens.store');
    Route::delete('/{id}', [AdminApiTokenController::class, 'destroy'])->name('admin.api-tokens.destroy');
});
