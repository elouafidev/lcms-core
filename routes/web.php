<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/login', function () {
//     return response('Login Page', 200);
// })->name('login');

Auth::routes();

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

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
