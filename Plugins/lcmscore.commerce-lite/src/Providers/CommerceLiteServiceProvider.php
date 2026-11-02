<?php

declare(strict_types=1);

namespace Plugins\CommerceLite\Providers;

use Core\Admin\NavigationRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Plugins\CommerceLite\Services\CartService;

class CommerceLiteServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services in the container.
     */
    public function register(): void
    {
        $this->app->singleton(CartService::class, function () {
            return new CartService;
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
            $this->loadViewsFrom($viewsPath, 'commerce');
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
                id: 'commerce-management',
                label: 'Boutique & Commandes',
                url: '/admin/commerce/orders',
                icon: 'bi bi-bag-check',
                priority: 35,
                permission: 'commerce.manage',
                pluginId: 'lcmscore.commerce-lite'
            );
        }

        // Register Blade directive @cartButton
        Blade::directive('cartButton', function (): string {
            return "<?php echo view('commerce::components.cart-button')->render(); ?>";
        });

        // Register Blade directive @price($cents)
        Blade::directive('price', function (string $expression): string {
            return "<?php echo number_format((float) ({$expression}) / 100, 2, ',', ' ') . ' €'; ?>";
        });
    }
}
