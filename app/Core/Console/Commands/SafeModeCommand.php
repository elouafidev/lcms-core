<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\PluginManager\PluginManager;
use Illuminate\Console\Command;

class SafeModeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:recovery:safe-mode {--on : Enable emergency safe mode} {--off : Disable emergency safe mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage emergency safe mode to bypass all third-party plugins';

    public function handle(PluginManager $pluginManager): int
    {
        $sentinelFile = storage_path('framework/cms_safe_mode');

        if ($this->option('on')) {
            $dir = dirname($sentinelFile);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($sentinelFile, (string) now());
            $this->warn('Emergency Safe Mode is now ENABLED. All third-party plugins will be bypassed at boot.');

            return self::SUCCESS;
        }

        if ($this->option('off')) {
            if (file_exists($sentinelFile)) {
                unlink($sentinelFile);
            }

            $this->info('Emergency Safe Mode is now DISABLED. Plugins will load normally.');

            return self::SUCCESS;
        }

        $isActive = $pluginManager->isSafeMode();

        if ($isActive) {
            $this->warn('Emergency Safe Mode is currently ACTIVE.');
        } else {
            $this->info('Emergency Safe Mode is currently INACTIVE.');
        }

        return self::SUCCESS;
    }
}
