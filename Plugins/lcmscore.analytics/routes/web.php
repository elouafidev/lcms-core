<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\Analytics\Http\Controllers\AdminAnalyticsController;

// Admin Analytics Routes
Route::prefix('admin/analytics')->middleware(['web', 'auth'])->group(function (): void {
    Route::get('/', [AdminAnalyticsController::class, 'index'])->name('admin.analytics.index');
    Route::post('/clear', [AdminAnalyticsController::class, 'clear'])->name('admin.analytics.clear');
});
