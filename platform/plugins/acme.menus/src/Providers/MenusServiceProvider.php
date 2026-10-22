<?php

declare(strict_types=1);

namespace Plugins\Menus\Providers;

use App\Core\Admin\NavigationRegistry;
use App\Core\Hooks\HookManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Plugins\Menus\Services\MenuRenderer;

class MenusServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services in the container.
     */
    public function register(): void
    {
        $this->app->singleton(MenuRenderer::class, function ($app): MenuRenderer {
            return new MenuRenderer(
                registry: $app->make(\App\Core\PluginRegistry\PluginRegistry::class),
                hookManager: $app->bound(HookManager::class) ? $app->make(HookManager::class) : null
            );
        });

        $this->app->alias(MenuRenderer::class, 'menu.renderer');
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $pluginRoot = dirname(__DIR__, 2);

        $viewsPath = $pluginRoot.'/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'menus');
        }

        $routesPath = $pluginRoot.'/routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        if ($this->app->bound(NavigationRegistry::class)) {
            /** @var NavigationRegistry $navigation */
            $navigation = $this->app->make(NavigationRegistry::class);
            $navigation->registerItem(
                id: 'menus-management',
                label: 'Menus de Navigation',
                url: '/admin/menus',
                icon: 'bi bi-menu-down',
                priority: 30,
                permission: 'menus.manage',
                pluginId: 'acme.menus'
            );
        }

        if ($this->app->bound(HookManager::class)) {
            /** @var HookManager $hookManager */
            $hookManager = $this->app->make(HookManager::class);
            $hookManager->addFilter('cms.menu', function (mixed $default, string $slug, array $options = []): string {
                /** @var MenuRenderer $renderer */
                $renderer = $this->app->make(MenuRenderer::class);

                /** @var array<string, mixed> $options */
                return $renderer->render($slug, $options);
            });
        }

        Blade::directive('menu', function (string $expression): string {
            return "<?php echo app(\\Plugins\\Menus\\Services\\MenuRenderer::class)->render({$expression}); ?>";
        });
    }
}
