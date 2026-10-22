<?php

declare(strict_types=1);

namespace Plugins\Pages\Providers;

use App\Core\Admin\NavigationRegistry;
use Illuminate\Support\ServiceProvider;

class PagesServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services in the container.
     */
    public function register(): void
    {
        // Container bindings for pages
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $pluginRoot = dirname(__DIR__, 2);

        $viewsPath = $pluginRoot.'/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'pages');
        }

        $routesPath = $pluginRoot.'/routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        if ($this->app->bound(NavigationRegistry::class)) {
            /** @var NavigationRegistry $navigation */
            $navigation = $this->app->make(NavigationRegistry::class);
            $navigation->registerItem(
                id: 'pages-management',
                label: 'Pages Statiques',
                url: '/admin/pages',
                icon: 'bi bi-file-text',
                priority: 25,
                permission: 'pages.manage',
                pluginId: 'acme.pages'
            );
        }
    }
}
