<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\Pages\Http\Controllers\AdminPageController;
use Plugins\Pages\Http\Controllers\PageController;

// Public front route for published pages
Route::middleware('web')->get('/page/{slug}', [PageController::class, 'show'])->name('pages.show');

// Admin pages management routes
Route::middleware(['web', 'auth'])->prefix('admin/pages')->group(function (): void {
    Route::get('/', [AdminPageController::class, 'index'])->name('admin.pages.index');
    Route::post('/', [AdminPageController::class, 'store'])->name('admin.pages.store');
    Route::get('/{id}', [AdminPageController::class, 'show'])->name('admin.pages.show');
    Route::delete('/{id}', [AdminPageController::class, 'destroy'])->name('admin.pages.destroy');
});
