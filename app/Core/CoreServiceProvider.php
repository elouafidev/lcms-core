<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Admin\NavigationRegistry;
use App\Core\Assets\PluginAssetManager;
use App\Core\Console\Commands\DisableTwoFactorCommand;
use App\Core\Console\Commands\EnableTwoFactorCommand;
use App\Core\Console\Commands\PluginDisableCommand;
use App\Core\Console\Commands\PluginEnableCommand;
use App\Core\Console\Commands\PluginListCommand;
use App\Core\Console\Commands\PluginPublishAssetsCommand;
use App\Core\Console\Commands\PluginSeedCommand;
use App\Core\Console\Commands\SafeModeCommand;
use App\Core\Console\Commands\ThemeActivateCommand;
use App\Core\Console\Commands\ThemeListCommand;
use App\Core\Console\Commands\ThemePublishAssetsCommand;
use App\Core\DependencyResolver\DependencyResolver;
use App\Core\Hooks\HookManager;
use App\Core\Lifecycle\PluginMigrationManager;
use App\Core\Lifecycle\PluginSeederManager;
use App\Core\Permissions\PermissionRegistry;
use App\Core\PluginDiscovery\Autoload\PluginClassLoader;
use App\Core\PluginDiscovery\ManifestValidator;
use App\Core\PluginDiscovery\PluginDiscovery;
use App\Core\PluginDiscovery\PluginZipInstaller;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use App\Core\RBAC\Services\RbacManager;
use App\Core\Recovery\PluginQuarantineHandler;
use App\Core\Recovery\SafeModeManager;
use App\Core\Security\Audit\AuditLogger;
use App\Core\Security\Services\RecoveryCodeService;
use App\Core\Security\Services\TotpService;
use App\Core\Settings\Services\MailTesterService;
use App\Core\Settings\SettingManager;
use App\Core\Themes\ThemeAssetManager;
use App\Core\Themes\ThemeDiscovery;
use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeZipInstaller;
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
        $this->app->singleton(PluginZipInstaller::class);
        $this->app->singleton(DependencyResolver::class);
        $this->app->singleton(HookManager::class);
        $this->app->singleton(PluginMigrationManager::class);
        $this->app->singleton(PluginSeederManager::class);
        $this->app->singleton(PluginRegistry::class);
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(NavigationRegistry::class);
        $this->app->singleton(SafeModeManager::class);
        $this->app->singleton(PluginQuarantineHandler::class);
        $this->app->singleton(PluginAssetManager::class);
        $this->app->singleton(PluginManager::class);
        $this->app->singleton(ThemeDiscovery::class);
        $this->app->singleton(ThemeAssetManager::class);
        $this->app->singleton(ThemeManager::class);
        $this->app->singleton(ThemeZipInstaller::class);

        // Security & Settings & RBAC Services
        $this->app->singleton(SettingManager::class);
        $this->app->singleton(TotpService::class);
        $this->app->singleton(RecoveryCodeService::class);
        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(RbacManager::class);
        $this->app->singleton(MailTesterService::class);
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
                PluginPublishAssetsCommand::class,
                PluginSeedCommand::class,
                ThemePublishAssetsCommand::class,
                ThemeListCommand::class,
                ThemeActivateCommand::class,
                EnableTwoFactorCommand::class,
                DisableTwoFactorCommand::class,
            ]);
        }

        $navigation = $this->app->make(NavigationRegistry::class);
        $navigation->registerItem(
            id: 'themes-management',
            label: 'Gestion des Thèmes',
            url: '/admin/themes',
            icon: 'palette',
            priority: 15
        );

        $this->app->make(SettingManager::class)->loadAndApplyConfigOverrides();

        $hookManager = $this->app->make(HookManager::class);

        $hookManager->addFilter('cms.head', function (string $content): string {
            $settings = app(SettingManager::class);
            $headTags = (string) $settings->get('appearance_custom_head_tags', '');
            $customCss = (string) $settings->get('appearance_custom_css', '');

            $injected = '';
            if (trim($headTags) !== '') {
                $injected .= "\n".$headTags;
            }
            if (trim($customCss) !== '') {
                $injected .= "\n<style type=\"text/css\" id=\"lcms-custom-css\">\n".$customCss."\n</style>";
            }

            return $content.$injected;
        }, priority: 100);

        $hookManager->addFilter('cms.footer', function (string $content): string {
            $settings = app(SettingManager::class);
            $customJs = (string) $settings->get('appearance_custom_js', '');

            if (trim($customJs) !== '') {
                return $content."\n<script id=\"lcms-custom-js\">\n".$customJs."\n</script>";
            }

            return $content;
        }, priority: 100);

        $this->app->booted(function (): void {
            $this->app->make(PluginManager::class)->boot();
            $this->app->make(ThemeManager::class)->boot();
        });
    }
}
