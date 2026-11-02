<?php

declare(strict_types=1);

namespace Plugins\I18n\Providers;

use Core\Admin\NavigationItem;
use Core\Admin\NavigationRegistry;
use Core\Hooks\HookManager;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Plugins\I18n\Http\Middleware\LocalizationMiddleware;
use Plugins\I18n\Services\LanguageManager;

class I18nServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services in the container.
     */
    public function register(): void
    {
        $this->app->singleton(LanguageManager::class, function (): LanguageManager {
            return new LanguageManager;
        });

        $this->app->alias(LanguageManager::class, 'i18n.manager');
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $pluginRoot = dirname(__DIR__, 2);

        $viewsPath = $pluginRoot.'/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'i18n');
        }

        $routesPath = $pluginRoot.'/routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Register Localization Middleware on web group
        if ($this->app->bound('router')) {
            /** @var Router $router */
            $router = $this->app->make('router');
            $router->pushMiddlewareToGroup('web', LocalizationMiddleware::class);
        }

        // Register SEO Hreflang Tags in cms.head hook
        if ($this->app->bound(HookManager::class)) {
            /** @var HookManager $hookManager */
            $hookManager = $this->app->make(HookManager::class);
            $hookManager->addFilter('cms.head', function (string $content): string {
                /** @var LanguageManager $manager */
                $manager = $this->app->make(LanguageManager::class);
                $languages = $manager->getActiveLanguages();

                if ($languages->count() <= 1) {
                    return $content;
                }

                $hreflangTags = "\n";
                $currentUrl = url()->current();

                foreach ($languages as $lang) {
                    $hreflangTags .= "    <link rel=\"alternate\" hreflang=\"{$lang->code}\" href=\"{$currentUrl}?lang={$lang->code}\" />\n";
                }

                $defaultLang = $manager->getDefaultLanguage();
                if ($defaultLang !== null) {
                    $hreflangTags .= "    <link rel=\"alternate\" hreflang=\"x-default\" href=\"{$currentUrl}\" />\n";
                }

                return $content.$hreflangTags;
            });
        }

        // Register Admin Navigation
        if ($this->app->bound(NavigationRegistry::class)) {
            /** @var NavigationRegistry $navigation */
            $navigation = $this->app->make(NavigationRegistry::class);
            $navigation->registerItem(
                id: 'i18n-management',
                label: 'Langues & i18n',
                url: '/admin/i18n/languages',
                icon: 'bi bi-globe',
                priority: 45,
                permission: 'i18n.languages.manage',
                pluginId: 'lcmscore.i18n',
                children: [
                    NavigationItem::make('i18n-languages', 'Gestion des Langues', '/admin/i18n/languages', 'bi bi-translate', 10, 'i18n.languages.manage', 'lcmscore.i18n'),
                    NavigationItem::make('i18n-translations', 'Dictionnaire de Contenu', '/admin/i18n/translations', 'bi bi-list-columns', 20, 'i18n.translations.manage', 'lcmscore.i18n'),
                ]
            );
        }

        // Register Blade Directive @langSwitcher
        Blade::directive('langSwitcher', function (): string {
            return "<?php echo view('i18n::components.switcher')->render(); ?>";
        });
    }
}
