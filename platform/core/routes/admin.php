<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Platform\Core\Http\Controllers\Admin\PluginController;

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [PluginController::class, 'index'])->name('dashboard');
    Route::get('/plugins', [PluginController::class, 'index'])->name('plugins.index');
    Route::post('/plugins/{id}/enable', [PluginController::class, 'enable'])->name('plugins.enable');
    Route::post('/plugins/{id}/disable', [PluginController::class, 'disable'])->name('plugins.disable');
    Route::post('/plugins/{id}/install', [PluginController::class, 'install'])->name('plugins.install');
    Route::post('/plugins/{id}/uninstall', [PluginController::class, 'uninstall'])->name('plugins.uninstall');
});
