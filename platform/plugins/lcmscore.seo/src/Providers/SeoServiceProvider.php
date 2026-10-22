<?php

declare(strict_types=1);

namespace Plugins\Seo\Providers;

use App\Core\Admin\NavigationRegistry;
use App\Core\Hooks\HookManager;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Support\ServiceProvider;
use Plugins\Seo\Services\SeoMetaManager;
use Plugins\Seo\Subscribers\SeoHookSubscriber;

class SeoServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services in the container.
     */
    public function register(): void
    {
        $this->app->singleton(SeoMetaManager::class, function (): SeoMetaManager {
            return new SeoMetaManager;
        });

        $this->app->singleton(SeoHookSubscriber::class, function ($app): SeoHookSubscriber {
            return new SeoHookSubscriber(
                metaManager: $app->make(SeoMetaManager::class),
                registry: $app->make(PluginRegistry::class)
            );
        });

        $this->app->alias(SeoMetaManager::class, 'seo.meta');
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $pluginRoot = dirname(__DIR__, 2);

        $viewsPath = $pluginRoot.'/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'seo');
        }

        $routesPath = $pluginRoot.'/routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        if ($this->app->bound(HookManager::class)) {
            /** @var HookManager $hookManager */
            $hookManager = $this->app->make(HookManager::class);
            /** @var SeoHookSubscriber $subscriber */
            $subscriber = $this->app->make(SeoHookSubscriber::class);
            $hookManager->addSubscriber($subscriber);
        }

        if ($this->app->bound(NavigationRegistry::class)) {
            /** @var NavigationRegistry $navigation */
            $navigation = $this->app->make(NavigationRegistry::class);
            $navigation->registerItem(
                id: 'seo-settings',
                label: 'Configuration SEO',
                url: '/admin/seo',
                icon: 'bi bi-search',
                priority: 40,
                permission: 'seo.manage',
                pluginId: 'lcmscore.seo'
            );
        }
    }
}
