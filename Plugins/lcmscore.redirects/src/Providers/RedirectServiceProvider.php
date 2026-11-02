<?php

declare(strict_types=1);

namespace Plugins\Redirects\Providers;

use Core\Admin\NavigationRegistry;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Plugins\Redirects\Http\Middleware\RedirectMiddleware;

class RedirectServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services in the container.
     */
    public function register(): void
    {
        // Container bindings for redirects plugin
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $pluginRoot = dirname(__DIR__, 2);

        $viewsPath = $pluginRoot.'/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'redirects');
        }

        $routesPath = $pluginRoot.'/routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Register Global HTTP Middleware to intercept unmatched URLs and detect 404s
        if ($this->app->bound(HttpKernelContract::class)) {
            $kernel = $this->app->make(HttpKernelContract::class);
            if ($kernel instanceof HttpKernel) {
                $kernel->prependMiddleware(RedirectMiddleware::class);
            }
        }

        // Also register in router web group for route-level requests
        if ($this->app->bound('router')) {
            /** @var Router $router */
            $router = $this->app->make('router');
            $router->pushMiddlewareToGroup('web', RedirectMiddleware::class);
        }

        // Register Admin Navigation Item
        if ($this->app->bound(NavigationRegistry::class)) {
            /** @var NavigationRegistry $navigation */
            $navigation = $this->app->make(NavigationRegistry::class);
            $navigation->registerItem(
                id: 'redirects-management',
                label: 'Redirections & 404',
                url: '/admin/redirects',
                icon: 'bi bi-arrow-left-right',
                priority: 48,
                permission: 'redirects.manage',
                pluginId: 'lcmscore.redirects'
            );
        }
    }
}
