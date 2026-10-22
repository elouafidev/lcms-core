<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\Seo\Http\Controllers\AdminSeoController;

// Admin SEO management routes
Route::middleware(['web', 'auth'])->prefix('admin/seo')->group(function (): void {
    Route::get('/', [AdminSeoController::class, 'index'])->name('admin.seo.index');
    Route::post('/', [AdminSeoController::class, 'update'])->name('admin.seo.update');
});
