<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Console\Commands\PluginDisableCommand;
use App\Core\Console\Commands\PluginEnableCommand;
use App\Core\Console\Commands\PluginListCommand;
use App\Core\Console\Commands\SafeModeCommand;
use App\Core\DependencyResolver\DependencyResolver;
use App\Core\Lifecycle\PluginMigrationManager;
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
        $this->app->singleton(DependencyResolver::class);
        $this->app->singleton(PluginMigrationManager::class);
        $this->app->singleton(PluginRegistry::class);
        $this->app->singleton(PluginManager::class);
    }

    /**
     * Bootstrap any core CMS services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/database/migrations');

        $viewsPath = dirname(__DIR__, 2).'/platform/core/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'core');
        }

        $adminRoutes = dirname(__DIR__, 2).'/platform/core/routes/admin.php';
        if (file_exists($adminRoutes)) {
            $this->loadRoutesFrom($adminRoutes);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                PluginListCommand::class,
                PluginEnableCommand::class,
                PluginDisableCommand::class,
                SafeModeCommand::class,
            ]);
        }

        $this->app->make(PluginManager::class)->boot();
    }
}
