<?php

declare(strict_types=1);

namespace Plugins\Search\Providers;

use Core\Admin\NavigationRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Plugins\Search\Services\SearchEngine;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services in the container.
     */
    public function register(): void
    {
        $this->app->singleton(SearchEngine::class, function () {
            return new SearchEngine;
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
            $this->loadViewsFrom($viewsPath, 'search');
        }

        $routesPath = $pluginRoot.'/routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Register Admin Navigation Item
        if ($this->app->bound(NavigationRegistry::class)) {
            /** @var NavigationRegistry $navigation */
            $navigation = $this->app->make(NavigationRegistry::class);
            $navigation->registerItem(
                id: 'search-management',
                label: 'Recherche & Stats',
                url: '/admin/search',
                icon: 'bi bi-search',
                priority: 50,
                permission: 'search.manage',
                pluginId: 'lcmscore.search'
            );
        }

        // Register Blade directive @searchBar
        Blade::directive('searchBar', function (): string {
            return "<?php echo view('search::components.bar')->render(); ?>";
        });
    }
}
