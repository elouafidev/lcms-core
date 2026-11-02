<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\I18n\Http\Controllers\AdminLanguageController;
use Plugins\I18n\Http\Controllers\AdminTranslationController;
use Plugins\I18n\Http\Controllers\PublicLanguageSwitchController;

// Public Language Switch Route
Route::get('/lang/{code}', [PublicLanguageSwitchController::class, 'switch'])->name('i18n.switch');

// Admin Management Routes
Route::prefix('admin/i18n')->middleware(['web', 'auth'])->group(function (): void {
    // Languages
    Route::get('/languages', [AdminLanguageController::class, 'index'])->name('admin.i18n.languages.index');
    Route::post('/languages', [AdminLanguageController::class, 'store'])->name('admin.i18n.languages.store');
    Route::put('/languages/{id}', [AdminLanguageController::class, 'update'])->name('admin.i18n.languages.update');
    Route::delete('/languages/{id}', [AdminLanguageController::class, 'destroy'])->name('admin.i18n.languages.destroy');
    Route::post('/languages/{id}/default', [AdminLanguageController::class, 'makeDefault'])->name('admin.i18n.languages.default');
    Route::post('/languages/{id}/toggle', [AdminLanguageController::class, 'toggle'])->name('admin.i18n.languages.toggle');

    // Translations
    Route::get('/translations', [AdminTranslationController::class, 'index'])->name('admin.i18n.translations.index');
    Route::post('/translations', [AdminTranslationController::class, 'store'])->name('admin.i18n.translations.store');
    Route::delete('/translations/{id}', [AdminTranslationController::class, 'destroy'])->name('admin.i18n.translations.destroy');
});
