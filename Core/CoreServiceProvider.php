<?php

declare(strict_types=1);

namespace Core;

use Core\Admin\NavigationRegistry;
use Core\Assets\PluginAssetManager;
use Core\Console\Commands\DisableTwoFactorCommand;
use Core\Console\Commands\EnableTwoFactorCommand;
use Core\Console\Commands\PluginDisableCommand;
use Core\Console\Commands\PluginEnableCommand;
use Core\Console\Commands\PluginListCommand;
use Core\Console\Commands\PluginPublishAssetsCommand;
use Core\Console\Commands\PluginSeedCommand;
use Core\Console\Commands\SafeModeCommand;
use Core\Console\Commands\ThemeActivateCommand;
use Core\Console\Commands\ThemeListCommand;
use Core\Console\Commands\ThemePublishAssetsCommand;
use Core\DependencyResolver\DependencyResolver;
use Core\Hooks\HookManager;
use Core\Lifecycle\PluginMigrationManager;
use Core\Lifecycle\PluginSeederManager;
use Core\Permissions\PermissionRegistry;
use Core\PluginDiscovery\Autoload\PluginClassLoader;
use Core\PluginDiscovery\ManifestValidator;
use Core\PluginDiscovery\PluginDiscovery;
use Core\PluginDiscovery\PluginZipInstaller;
use Core\PluginManager\PluginManager;
use Core\PluginRegistry\PluginRegistry;
use Core\RBAC\Services\RbacManager;
use Core\Recovery\PluginQuarantineHandler;
use Core\Recovery\SafeModeManager;
use Core\Security\Audit\AuditLogger;
use Core\Security\Services\RecoveryCodeService;
use Core\Security\Services\TotpService;
use Core\Settings\Services\MailTesterService;
use Core\Settings\SettingManager;
use Core\Themes\ThemeAssetManager;
use Core\Themes\ThemeDiscovery;
use Core\Themes\ThemeManager;
use Core\Themes\ThemeZipInstaller;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any core CMS services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            base_path('config/cms.php'),
            'cms'
        );

        $legacyAliases = [
            \Core\Models\User::class => 'App\Models\User',
            \Core\Http\Controllers\Controller::class => 'App\Http\Controllers\Controller',
            \Core\Hooks\Facades\Hook::class => 'App\Core\Hooks\Facades\Hook',
            \Core\Hooks\HookManager::class => 'App\Core\Hooks\HookManager',
            \Core\PluginRegistry\PluginRegistry::class => 'App\Core\PluginRegistry\PluginRegistry',
            \Core\Themes\ThemeAssetManager::class => 'App\Core\Themes\ThemeAssetManager',
            \Core\Recovery\SafeModeManager::class => 'App\Core\Recovery\SafeModeManager',
            \Core\Admin\NavigationRegistry::class => 'App\Core\Admin\NavigationRegistry',
            \Core\Http\Controllers\Auth\LoginController::class => 'App\Http\Controllers\Auth\LoginController',
            \Core\Http\Controllers\Auth\RegisterController::class => 'App\Http\Controllers\Auth\RegisterController',
            \Core\Http\Controllers\Auth\ForgotPasswordController::class => 'App\Http\Controllers\Auth\ForgotPasswordController',
            \Core\Http\Controllers\Auth\ResetPasswordController::class => 'App\Http\Controllers\Auth\ResetPasswordController',
            \Core\Http\Controllers\Auth\ConfirmPasswordController::class => 'App\Http\Controllers\Auth\ConfirmPasswordController',
            \Core\Http\Controllers\Auth\VerificationController::class => 'App\Http\Controllers\Auth\VerificationController',
        ];
        foreach ($legacyAliases as $target => $alias) {
            if (! class_exists($alias, false)) {
                class_alias($target, $alias);
            }
        }

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
        $this->loadMigrationsFrom(base_path('database/migrations'));

        $viewsPath = __DIR__.'/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'core');
        }

        $adminRoutes = __DIR__.'/routes/admin.php';
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
