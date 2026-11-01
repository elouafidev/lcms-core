<?php

declare(strict_types=1);

namespace Plugins\Forms\Providers;

use Core\Admin\NavigationRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class FormsServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services in the container.
     */
    public function register(): void
    {
        // Container bindings for forms plugin
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $pluginRoot = dirname(__DIR__, 2);

        $viewsPath = $pluginRoot.'/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'forms');
        }

        $routesPath = $pluginRoot.'/routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Register Navigation item
        if ($this->app->bound(NavigationRegistry::class)) {
            /** @var NavigationRegistry $navigation */
            $navigation = $this->app->make(NavigationRegistry::class);
            $navigation->registerItem(
                id: 'forms-management',
                label: 'Formulaires',
                url: '/admin/forms',
                icon: 'bi bi-input-cursor-text',
                priority: 26,
                permission: 'forms.manage',
                pluginId: 'lcmscore.forms'
            );
        }

        // Register Blade directive @renderForm('slug')
        Blade::directive('renderForm', function (string $expression): string {
            return "<?php \$__form = \Plugins\Forms\Models\Form::query()->where('slug', {$expression})->where('is_active', true)->first(); if (\$__form) { echo view('forms::components.render', ['form' => \$__form])->render(); } ?>";
        });
    }
}
