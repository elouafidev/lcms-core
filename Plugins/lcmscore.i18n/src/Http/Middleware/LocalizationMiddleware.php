<?php

declare(strict_types=1);

namespace Plugins\I18n\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Plugins\I18n\Services\LanguageManager;
use Symfony\Component\HttpFoundation\Response;

class LocalizationMiddleware
{
    public function __construct(
        private LanguageManager $languageManager
    ) {}

    /**
     * Handle an incoming request and apply active locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check explicit query parameter ?lang=code
        $queryLang = $request->query('lang');
        if (is_string($queryLang) && trim($queryLang) !== '') {
            $this->languageManager->setLocale(strtolower(trim($queryLang)));
        } elseif ($request->hasSession() && $request->session()->has('cms_locale')) {
            // 2. Check session locale
            $sessionLocale = (string) $request->session()->get('cms_locale');
            if ($sessionLocale !== '') {
                app()->setLocale($sessionLocale);
            }
        }

        return $next($request);
    }
}
