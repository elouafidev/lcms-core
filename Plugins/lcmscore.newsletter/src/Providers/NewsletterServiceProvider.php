<?php

declare(strict_types=1);

namespace Plugins\Newsletter\Providers;

use Core\Admin\NavigationRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class NewsletterServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services in the container.
     */
    public function register(): void
    {
        // Container bindings for newsletter plugin
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $pluginRoot = dirname(__DIR__, 2);

        $viewsPath = $pluginRoot.'/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'newsletter');
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
                id: 'newsletter-management',
                label: 'Newsletter & Abonnés',
                url: '/admin/newsletter',
                icon: 'bi bi-envelope-paper',
                priority: 45,
                permission: 'newsletter.manage',
                pluginId: 'lcmscore.newsletter'
            );
        }

        // Register Blade directive @newsletterForm
        Blade::directive('newsletterForm', function (): string {
            return "<?php echo view('newsletter::components.form')->render(); ?>";
        });
    }
}
