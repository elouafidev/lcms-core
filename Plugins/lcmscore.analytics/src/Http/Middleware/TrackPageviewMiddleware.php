<?php

declare(strict_types=1);

namespace Plugins\Analytics\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Plugins\Analytics\Models\PageView;
use Plugins\Analytics\Services\UserAgentParser;
use Symfony\Component\HttpFoundation\Response;

class TrackPageviewMiddleware
{
    /**
     * Intercept web requests and track anonymous GDPR-compliant pageviews.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Run tracking only on successful 200 OK GET responses
        if ($this->shouldTrack($request, $response)) {
            try {
                PageView::record($request, $response->getStatusCode());
            } catch (\Throwable) {
                // Silently ignore tracking exceptions to never interrupt public user experience
            }
        }

        return $response;
    }

    /**
     * Determine if incoming request should be tracked in audience metrics.
     */
    protected function shouldTrack(Request $request, Response $response): bool
    {
        // 1. Only track GET requests and 200 OK responses
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return false;
        }

        // 2. Only track if table exists
        if (! Schema::hasTable('plugin_analytics_pageviews')) {
            return false;
        }

        $path = '/'.ltrim($request->path(), '/');

        // 3. Exclude admin backend, internal assets, health checks, and live AJAX endpoints
        if (
            str_starts_with($path, '/admin') ||
            str_starts_with($path, '/_') ||
            str_starts_with($path, '/up') ||
            str_starts_with($path, '/search/live')
        ) {
            return false;
        }

        // 4. Exclude logged-in CMS administrators
        if (Auth::check()) {
            return false;
        }

        // 5. Exclude search engine bots and automated crawlers
        if (UserAgentParser::isBot($request->userAgent())) {
            return false;
        }

        return true;
    }
}
