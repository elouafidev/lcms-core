<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\Search\Http\Controllers\AdminSearchController;
use Plugins\Search\Http\Controllers\PublicSearchController;

// Public Search Routes
Route::middleware(['web'])->group(function (): void {
    Route::get('/search', [PublicSearchController::class, 'index'])->name('search.index');
    Route::get('/search/live', [PublicSearchController::class, 'live'])->name('search.live');
});

// Admin Search Analytics Routes
Route::prefix('admin/search')->middleware(['web', 'auth'])->group(function (): void {
    Route::get('/', [AdminSearchController::class, 'index'])->name('admin.search.index');
    Route::post('/clear', [AdminSearchController::class, 'clearLogs'])->name('admin.search.clear');
});
