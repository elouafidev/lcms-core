<?php

declare(strict_types=1);

namespace App\Core;

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
    }

    /**
     * Bootstrap any core CMS services.
     */
    public function boot(): void
    {
        // Core infrastructure boot sequence initialized in US-001
    }
}
