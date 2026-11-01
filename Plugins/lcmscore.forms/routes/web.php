<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\Forms\Http\Controllers\AdminFormController;
use Plugins\Forms\Http\Controllers\AdminSubmissionController;
use Plugins\Forms\Http\Controllers\PublicFormSubmitController;

// Public form submission endpoint
Route::middleware('web')->post('/forms/{slug}/submit', [PublicFormSubmitController::class, 'submit'])->name('forms.submit');

// Admin forms & submissions management
Route::middleware(['web', 'auth'])->prefix('admin/forms')->group(function (): void {
    Route::get('/', [AdminFormController::class, 'index'])->name('admin.forms.index');
    Route::get('/create', [AdminFormController::class, 'create'])->name('admin.forms.create');
    Route::post('/', [AdminFormController::class, 'store'])->name('admin.forms.store');
    Route::get('/{id}/edit', [AdminFormController::class, 'edit'])->name('admin.forms.edit');
    Route::put('/{id}', [AdminFormController::class, 'update'])->name('admin.forms.update');
    Route::delete('/{id}', [AdminFormController::class, 'destroy'])->name('admin.forms.destroy');

    // Submissions
    Route::get('/{id}/submissions', [AdminSubmissionController::class, 'index'])->name('admin.forms.submissions.index');
    Route::get('/{id}/submissions/export', [AdminSubmissionController::class, 'exportCsv'])->name('admin.forms.submissions.export');
    Route::get('/submissions/{id}', [AdminSubmissionController::class, 'show'])->name('admin.forms.submissions.show');
    Route::post('/submissions/{id}/read', [AdminSubmissionController::class, 'markAsRead'])->name('admin.forms.submissions.read');
    Route::delete('/submissions/{id}', [AdminSubmissionController::class, 'destroy'])->name('admin.forms.submissions.destroy');
});
