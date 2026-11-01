<?php

declare(strict_types=1);

namespace App\Core\RBAC\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Non authentifié.');
        }

        if (! $user->hasPermission($permission)) {
            abort(403, "Accès refusé : la permission [{$permission}] est requise.");
        }

        return $next($request);
    }
}
