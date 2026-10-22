<?php

declare(strict_types=1);

namespace Plugins\MediaLibrary\Providers;

use App\Core\Admin\NavigationRegistry;
use App\Core\Contracts\MediaStorageInterface;
use Illuminate\Support\ServiceProvider;
use Plugins\MediaLibrary\Services\MediaStorageService;

class MediaLibraryServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services in the container.
     */
    public function register(): void
    {
        $this->app->singleton(MediaStorageInterface::class, function (): MediaStorageInterface {
            return new MediaStorageService;
        });
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $pluginRoot = dirname(__DIR__, 2);

        $viewsPath = $pluginRoot.'/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'media-library');
        }

        $routesPath = $pluginRoot.'/routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        if ($this->app->bound(NavigationRegistry::class)) {
            /** @var NavigationRegistry $navigation */
            $navigation = $this->app->make(NavigationRegistry::class);
            $navigation->registerItem(
                id: 'media-library',
                label: 'Médiathèque',
                url: '/admin/media',
                icon: 'bi bi-images',
                priority: 30,
                permission: 'media.manage',
                pluginId: 'lcmscore.media-library'
            );
        }
    }
}
