<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\PluginDiscovery\Autoload\PluginClassLoader;
use App\Core\PluginDiscovery\ManifestValidator;
use App\Core\PluginDiscovery\PluginDiscovery;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any core CMS services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__, 2).'/config/cms.php',
            'cms'
        );

        $this->app->singleton(ManifestValidator::class);
        $this->app->singleton(PluginClassLoader::class);
        $this->app->singleton(PluginDiscovery::class);
        $this->app->singleton(PluginRegistry::class);
        $this->app->singleton(PluginManager::class);
    }

    /**
     * Bootstrap any core CMS services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/database/migrations');

        $this->app->make(PluginManager::class)->boot();
    }
}
