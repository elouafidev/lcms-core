<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\DemoHello\Http\Controllers\HelloController;

Route::get('/demo/hello', HelloController::class)->name('demo.hello');
