<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Recovery\SafeModeManager;
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

    public function handle(SafeModeManager $safeModeManager): int
    {
        if ($this->option('on')) {
            $safeModeManager->enable();
            $this->warn('Emergency Safe Mode is now ENABLED. All third-party plugins will be bypassed at boot.');

            return self::SUCCESS;
        }

        if ($this->option('off')) {
            $safeModeManager->disable();
            $this->info('Emergency Safe Mode is now DISABLED. Plugins will load normally.');

            return self::SUCCESS;
        }

        if ($safeModeManager->isEnabled()) {
            $this->warn('Emergency Safe Mode is currently ACTIVE.');
        } else {
            $this->info('Emergency Safe Mode is currently INACTIVE.');
        }

        return self::SUCCESS;
    }
}
