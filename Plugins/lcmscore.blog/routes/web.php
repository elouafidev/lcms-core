<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\Blog\Http\Controllers\AdminCategoryController;
use Plugins\Blog\Http\Controllers\AdminPostController;
use Plugins\Blog\Http\Controllers\AdminTagController;
use Plugins\Blog\Http\Controllers\PublicBlogController;

// Public Routes
Route::prefix('blog')->group(function (): void {
    Route::get('/feed.xml', [PublicBlogController::class, 'feed'])->name('blog.feed');
    Route::get('/category/{slug}', [PublicBlogController::class, 'category'])->name('blog.category');
    Route::get('/tag/{slug}', [PublicBlogController::class, 'tag'])->name('blog.tag');
    Route::get('/', [PublicBlogController::class, 'index'])->name('blog.index');
    Route::get('/{slug}', [PublicBlogController::class, 'show'])->name('blog.show');
});

// Admin Management Routes
Route::prefix('admin/blog')->middleware(['web', 'auth'])->group(function (): void {
    // Posts
    Route::get('/posts', [AdminPostController::class, 'index'])->name('admin.blog.posts.index');
    Route::get('/posts/create', [AdminPostController::class, 'create'])->name('admin.blog.posts.create');
    Route::post('/posts', [AdminPostController::class, 'store'])->name('admin.blog.posts.store');
    Route::get('/posts/{id}/edit', [AdminPostController::class, 'edit'])->name('admin.blog.posts.edit');
    Route::put('/posts/{id}', [AdminPostController::class, 'update'])->name('admin.blog.posts.update');
    Route::delete('/posts/{id}', [AdminPostController::class, 'destroy'])->name('admin.blog.posts.destroy');

    // Categories
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('admin.blog.categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('admin.blog.categories.store');
    Route::put('/categories/{id}', [AdminCategoryController::class, 'update'])->name('admin.blog.categories.update');
    Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy'])->name('admin.blog.categories.destroy');

    // Tags
    Route::get('/tags', [AdminTagController::class, 'index'])->name('admin.blog.tags.index');
    Route::post('/tags', [AdminTagController::class, 'store'])->name('admin.blog.tags.store');
    Route::delete('/tags/{id}', [AdminTagController::class, 'destroy'])->name('admin.blog.tags.destroy');
});
