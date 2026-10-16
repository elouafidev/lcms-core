<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\MediaLibrary\Http\Controllers\MediaController;

Route::middleware(['web', 'auth'])->prefix('admin/media')->group(function (): void {
    Route::get('/', [MediaController::class, 'index'])->name('admin.media.index');
    Route::post('/upload', [MediaController::class, 'upload'])->name('admin.media.upload');
    Route::get('/{id}', [MediaController::class, 'show'])->name('admin.media.show');
    Route::delete('/{id}', [MediaController::class, 'destroy'])->name('admin.media.destroy');
});
