<?php

declare(strict_types=1);

use App\Core\CoreServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Illuminate\Http\Exceptions\PostTooLargeException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'La taille des données transmises dépasse la limite autorisée par le serveur (post_max_size).',
                ], 413);
            }

            $postMax = ini_get('post_max_size') ?: '8M';

            return redirect()->back()->with('error', "Le fichier ou les données transmises dépassent la limite autorisée par le serveur PHP (post_max_size = {$postMax}).");
        });
    })
    ->withProviders([
        CoreServiceProvider::class,
    ])->create();
