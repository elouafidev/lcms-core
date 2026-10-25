<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession()) {
            $locale = $request->session()->get('admin_locale', config('app.locale', 'fr'));
            if (is_string($locale) && in_array($locale, ['fr', 'en'], true)) {
                app()->setLocale($locale);
            }
        }

        return $next($request);
    }
}
