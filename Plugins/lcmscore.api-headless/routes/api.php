<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\ApiHeadless\Http\Controllers\ApiContentController;
use Plugins\ApiHeadless\Http\Middleware\EnsureApiTokenIsValid;

// REST API v1 Routes
Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    // Public Metadata
    Route::get('/info', [ApiContentController::class, 'info'])->name('api.v1.info');

    // Content Read Endpoints
    Route::get('/pages', [ApiContentController::class, 'pages'])->name('api.v1.pages.index');
    Route::get('/pages/{slug}', [ApiContentController::class, 'page'])->name('api.v1.pages.show');

    Route::get('/posts', [ApiContentController::class, 'posts'])->name('api.v1.posts.index');
    Route::get('/posts/{slug}', [ApiContentController::class, 'post'])->name('api.v1.posts.show');

    Route::get('/menus/{slug}', [ApiContentController::class, 'menu'])->name('api.v1.menus.show');
    Route::get('/blocks/{slug}', [ApiContentController::class, 'block'])->name('api.v1.blocks.show');

    // Protected Route requiring valid Bearer API Token
    Route::middleware([EnsureApiTokenIsValid::class])->group(function (): void {
        Route::get('/auth/verify', function () {
            return response()->json([
                'success' => true,
                'message' => 'Jeton API valide et actif.',
                'token' => request()->attributes->get('api_token'),
            ]);
        })->name('api.v1.auth.verify');
    });
});
