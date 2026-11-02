<?php

declare(strict_types=1);

namespace Plugins\ApiHeadless\Providers;

use Core\Admin\NavigationRegistry;
use Illuminate\Support\ServiceProvider;

class ApiHeadlessServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services in the container.
     */
    public function register(): void
    {
        // Container bindings for api-headless plugin
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $pluginRoot = dirname(__DIR__, 2);

        $viewsPath = $pluginRoot.'/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'api-headless');
        }

        $webRoutesPath = $pluginRoot.'/routes/web.php';
        if (file_exists($webRoutesPath)) {
            $this->loadRoutesFrom($webRoutesPath);
        }

        $apiRoutesPath = $pluginRoot.'/routes/api.php';
        if (file_exists($apiRoutesPath)) {
            $this->loadRoutesFrom($apiRoutesPath);
        }

        // Register Admin Navigation Item
        if ($this->app->bound(NavigationRegistry::class)) {
            /** @var NavigationRegistry $navigation */
            $navigation = $this->app->make(NavigationRegistry::class);
            $navigation->registerItem(
                id: 'api-headless-management',
                label: 'API REST & Headless',
                url: '/admin/api-tokens',
                icon: 'bi bi-cpu',
                priority: 40,
                permission: 'api.tokens.manage',
                pluginId: 'lcmscore.api-headless'
            );
        }
    }
}
