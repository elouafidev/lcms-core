<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Platform\Core\Http\Controllers\Admin\AdminThemeController;
use Platform\Core\Http\Controllers\Admin\CoreUpdateController;
use Platform\Core\Http\Controllers\Admin\GeneralSettingsController;
use Platform\Core\Http\Controllers\Admin\PluginController;
use Platform\Core\Http\Controllers\Admin\RoleController;
use Platform\Core\Http\Controllers\Admin\SiteSettingsController;
use Platform\Core\Http\Controllers\Admin\UserController;

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [PluginController::class, 'index'])->name('dashboard');
    Route::get('/plugins', [PluginController::class, 'index'])->name('plugins.index');
    Route::post('/plugins/upload', [PluginController::class, 'upload'])->name('plugins.upload');
    Route::get('/plugins/{id}', [PluginController::class, 'show'])->name('plugins.show');
    Route::post('/plugins/{id}/enable', [PluginController::class, 'enable'])->name('plugins.enable');
    Route::post('/plugins/{id}/disable', [PluginController::class, 'disable'])->name('plugins.disable');
    Route::post('/plugins/{id}/install', [PluginController::class, 'install'])->name('plugins.install');
    Route::post('/plugins/{id}/uninstall', [PluginController::class, 'uninstall'])->name('plugins.uninstall');
    Route::post('/plugins/{id}/delete', [PluginController::class, 'delete'])->name('plugins.delete');

    // Themes management routes
    Route::get('/themes', [AdminThemeController::class, 'index'])->name('themes.index');
    Route::post('/themes/upload', [AdminThemeController::class, 'upload'])->name('themes.upload');
    Route::post('/themes/{id}/activate', [AdminThemeController::class, 'activate'])->name('themes.activate');
    Route::post('/themes/deactivate', [AdminThemeController::class, 'deactivate'])->name('themes.deactivate');
    Route::post('/themes/{id}/delete', [AdminThemeController::class, 'delete'])->name('themes.delete');

    // 1. General Settings (Profile & 2FA)
    Route::prefix('settings/general')->name('settings.general')->group(function (): void {
        Route::get('/', [GeneralSettingsController::class, 'index']);
        Route::put('/profile', [GeneralSettingsController::class, 'updateProfile'])->name('.profile');
        Route::put('/password', [GeneralSettingsController::class, 'updatePassword'])->name('.password');
        Route::post('/2fa/setup', [GeneralSettingsController::class, 'setup2fa'])->name('.2fa.setup');
        Route::post('/2fa/confirm', [GeneralSettingsController::class, 'confirm2fa'])->name('.2fa.confirm');
        Route::post('/2fa/disable', [GeneralSettingsController::class, 'disable2fa'])->name('.2fa.disable');
        Route::post('/sessions/logout-others', [GeneralSettingsController::class, 'logoutOtherDevices'])->name('.sessions.logout-others');
    });

    // 2. Site Settings & SMTP
    Route::prefix('settings/site')->name('settings.site')->group(function (): void {
        Route::get('/', [SiteSettingsController::class, 'index']);
        Route::post('/general', [SiteSettingsController::class, 'updateGeneral'])->name('.general');
        Route::post('/smtp', [SiteSettingsController::class, 'updateSmtp'])->name('.smtp');
        Route::post('/smtp/test', [SiteSettingsController::class, 'testSmtp'])->name('.smtp.test');
    });

    // 3. Roles & Permissions Management (RBAC)
    Route::resource('roles', RoleController::class)->except(['show']);

    // 4. Accounts / Users Management
    Route::resource('users', UserController::class)->except(['show']);
    Route::post('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');
    Route::post('/users/{user}/revoke-sessions', [UserController::class, 'revokeSessions'])->name('users.revoke-sessions');
    Route::post('/users/{user}/reset-2fa', [UserController::class, 'reset2fa'])->name('users.reset-2fa');

    // 5. Core ZIP Update Management
    Route::prefix('settings/update')->name('settings.update')->group(function (): void {
        Route::get('/', [CoreUpdateController::class, 'index']);
        Route::post('/upload', [CoreUpdateController::class, 'upload'])->name('.upload');
    });
});
