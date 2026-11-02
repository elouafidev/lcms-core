<?php

declare(strict_types=1);

namespace Plugins\Blocks\Providers;

use Core\Admin\NavigationRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BlocksServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services in the container.
     */
    public function register(): void
    {
        // Container bindings for blocks plugin
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $pluginRoot = dirname(__DIR__, 2);

        $viewsPath = $pluginRoot.'/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'blocks');
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
                id: 'blocks-management',
                label: 'Blocs & Sections',
                url: '/admin/blocks',
                icon: 'bi bi-grid-1x2',
                priority: 28,
                permission: 'blocks.manage',
                pluginId: 'lcmscore.blocks'
            );
        }

        // Register Blade directive @renderBlock('slug')
        Blade::directive('renderBlock', function (string $expression): string {
            return "<?php echo \Plugins\Blocks\Models\Block::renderBySlug({$expression}); ?>";
        });
    }
}
