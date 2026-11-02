<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\Newsletter\Http\Controllers\AdminNewsletterController;
use Plugins\Newsletter\Http\Controllers\PublicNewsletterController;

// Public Newsletter Routes
Route::middleware(['web'])->group(function (): void {
    Route::post('/newsletter/subscribe', [PublicNewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
    Route::get('/newsletter/confirm/{token}', [PublicNewsletterController::class, 'confirm'])->name('newsletter.confirm');
    Route::get('/newsletter/unsubscribe/{token}', [PublicNewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
});

// Admin Newsletter Management Routes
Route::prefix('admin/newsletter')->middleware(['web', 'auth'])->group(function (): void {
    Route::get('/', [AdminNewsletterController::class, 'index'])->name('admin.newsletter.index');
    Route::post('/subscribers', [AdminNewsletterController::class, 'store'])->name('admin.newsletter.store');
    Route::post('/subscribers/{id}/toggle', [AdminNewsletterController::class, 'toggle'])->name('admin.newsletter.toggle');
    Route::post('/subscribers/{id}/resend', [AdminNewsletterController::class, 'resendConfirmation'])->name('admin.newsletter.resend');
    Route::delete('/subscribers/{id}', [AdminNewsletterController::class, 'destroy'])->name('admin.newsletter.destroy');
    Route::get('/export', [AdminNewsletterController::class, 'exportCsv'])->name('admin.newsletter.export');
});
