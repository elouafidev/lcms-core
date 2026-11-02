<?php

declare(strict_types=1);

namespace Plugins\Analytics\Providers;

use Core\Admin\NavigationRegistry;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Plugins\Analytics\Http\Middleware\TrackPageviewMiddleware;

class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services in the container.
     */
    public function register(): void
    {
        // Container bindings for analytics plugin
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $pluginRoot = dirname(__DIR__, 2);

        $viewsPath = $pluginRoot.'/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'analytics');
        }

        $routesPath = $pluginRoot.'/routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Register Global HTTP Middleware for pageview tracking
        if ($this->app->bound(HttpKernelContract::class)) {
            $kernel = $this->app->make(HttpKernelContract::class);
            if ($kernel instanceof HttpKernel) {
                $kernel->pushMiddleware(TrackPageviewMiddleware::class);
            }
        }

        // Also register in router web group
        if ($this->app->bound('router')) {
            /** @var Router $router */
            $router = $this->app->make('router');
            $router->pushMiddlewareToGroup('web', TrackPageviewMiddleware::class);
        }

        // Register Admin Navigation Item
        if ($this->app->bound(NavigationRegistry::class)) {
            /** @var NavigationRegistry $navigation */
            $navigation = $this->app->make(NavigationRegistry::class);
            $navigation->registerItem(
                id: 'analytics-management',
                label: 'Statistiques & Audience',
                url: '/admin/analytics',
                icon: 'bi bi-graph-up-arrow',
                priority: 42,
                permission: 'analytics.view',
                pluginId: 'lcmscore.analytics'
            );
        }
    }
}
