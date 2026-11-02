<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\Blocks\Http\Controllers\AdminBlockController;

// Admin Block Management Routes
Route::prefix('admin/blocks')->middleware(['web', 'auth'])->group(function (): void {
    Route::get('/', [AdminBlockController::class, 'index'])->name('admin.blocks.index');
    Route::get('/create', [AdminBlockController::class, 'create'])->name('admin.blocks.create');
    Route::post('/', [AdminBlockController::class, 'store'])->name('admin.blocks.store');
    Route::get('/{id}/edit', [AdminBlockController::class, 'edit'])->name('admin.blocks.edit');
    Route::put('/{id}', [AdminBlockController::class, 'update'])->name('admin.blocks.update');
    Route::delete('/{id}', [AdminBlockController::class, 'destroy'])->name('admin.blocks.destroy');
    Route::post('/{id}/toggle', [AdminBlockController::class, 'toggle'])->name('admin.blocks.toggle');
    Route::get('/{id}/preview', [AdminBlockController::class, 'preview'])->name('admin.blocks.preview');
});
