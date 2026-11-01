<?php

declare(strict_types=1);

namespace App\Core\Settings\Middleware;

use App\Core\Settings\SettingManager;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * URIs that should be reachable even when maintenance mode is enabled.
     *
     * @var list<string>
     */
    private const EXEMPT_PATTERNS = [
        'admin',
        'admin/*',
        'login',
        'login/*',
        'logout',
        'locale/*',
        'admin/locale/*',
        'up',
    ];

    public function __construct(
        private SettingManager $settingManager
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isMaintenance = (bool) $this->settingManager->get('maintenance_mode', false);

        if (! $isMaintenance) {
            return $next($request);
        }

        // 1. Allow administrative, authentication, and health check routes
        foreach (self::EXEMPT_PATTERNS as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // 2. Allow authenticated administrators to browse the frontend
        /** @var User|null $user */
        $user = $request->user();
        if ($user !== null && ($user->isSuperAdmin() || $user->hasRole(['super_admin', 'admin']))) {
            return $next($request);
        }

        // 3. Render 503 Maintenance Page for public visitors
        $siteName = (string) $this->settingManager->get('site_name', config('app.name', 'LCMS Core'));
        $maintenanceMessage = (string) $this->settingManager->get('maintenance_message', 'Notre site est actuellement en maintenance pour des améliorations techniques. Nous serons de retour très bientôt.');

        if (view()->exists('errors.503')) {
            return response()->view('errors.503', [
                'siteName' => $siteName,
                'maintenanceMessage' => $maintenanceMessage,
            ], 503, [
                'Retry-After' => '300',
            ]);
        }

        abort(503, $maintenanceMessage);
    }
}
