<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\TwoFactorAuthenticatedController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

// Two-Factor Authentication Challenge
Route::get('/login/2fa', [TwoFactorAuthenticatedController::class, 'create'])->name('2fa.challenge');
Route::post('/login/2fa', [TwoFactorAuthenticatedController::class, 'store'])->name('2fa.verify');

Route::get('/admin/locale/{lang}', function (string $lang) {
    if (in_array($lang, ['fr', 'en'], true)) {
        session(['admin_locale' => $lang]);
    }

    return redirect()->back();
})->name('admin.locale');

Route::get('/locale/{lang}', function (string $lang) {
    if (in_array($lang, ['fr', 'en'], true)) {
        session(['admin_locale' => $lang]);
    }

    return redirect()->back();
})->name('locale.switch');

Route::get('/home', [HomeController::class, 'index'])->name('home');
