<?php

declare(strict_types=1);

namespace Plugins\ApiHeadless\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Plugins\ApiHeadless\Models\ApiToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiTokenIsValid
{
    /**
     * Verify presence and validity of bearer API access token.
     */
    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $rawToken = $request->bearerToken();
        if ($rawToken === null || trim($rawToken) === '') {
            $queryToken = $request->query('api_token');
            if (is_string($queryToken) && trim($queryToken) !== '') {
                $rawToken = trim($queryToken);
            }
        }

        if ($rawToken === null || trim($rawToken) === '') {
            return response()->json([
                'success' => false,
                'message' => 'Authentification requise. Veuillez fournir un jeton API (Header Authorization: Bearer <token> ou ?api_token=).',
            ], 401);
        }

        $tokenRecord = ApiToken::findToken($rawToken);

        if ($tokenRecord === null || $tokenRecord->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Jeton API invalide, révoqué ou expiré.',
            ], 401);
        }

        if ($ability !== null && ! $tokenRecord->can($ability)) {
            return response()->json([
                'success' => false,
                'message' => "Permission insuffisante pour cette ressource (capacité requise : {$ability}).",
            ], 403);
        }

        $tokenRecord->update(['last_used_at' => now()]);
        $request->attributes->set('api_token', $tokenRecord);

        return $next($request);
    }
}
