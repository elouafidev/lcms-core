<?php

declare(strict_types=1);

namespace Plugins\Redirects\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Plugins\Redirects\Models\NotFoundLog;
use Plugins\Redirects\Models\RedirectRule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RedirectMiddleware
{
    /**
     * Intercept incoming requests for matching redirects or 404 tracking.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim($request->path(), '/');

        // Bypass backend admin routes, debugging assets, and non-GET methods
        if (str_starts_with($path, '/admin') || str_starts_with($path, '/_') || ! $request->isMethod('GET')) {
            return $next($request);
        }

        // 1. Check for active redirection rule
        if (Schema::hasTable('plugin_redirects')) {
            $rule = RedirectRule::findMatching($path);

            if ($rule !== null) {
                $rule->recordHit();

                return redirect($rule->target_url, $rule->status_code);
            }
        }

        try {
            /** @var Response $response */
            $response = $next($request);

            // 2. Track 404 errors if page returned 404 response
            if ($response->getStatusCode() === 404 && Schema::hasTable('plugin_redirects_404_logs')) {
                NotFoundLog::record(
                    rawPath: $path,
                    referrer: $request->headers->get('referer'),
                    userAgent: $request->userAgent()
                );
            }

            return $response;
        } catch (NotFoundHttpException $e) {
            // Track 404 when router throws NotFoundHttpException
            if (Schema::hasTable('plugin_redirects_404_logs')) {
                NotFoundLog::record(
                    rawPath: $path,
                    referrer: $request->headers->get('referer'),
                    userAgent: $request->userAgent()
                );
            }

            throw $e;
        }
    }
}
