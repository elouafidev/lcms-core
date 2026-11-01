<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Console\Command;
use Throwable;

class PluginEnableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:plugin:enable {id : The plugin identifier (e.g. lcmscore.demo-hello)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install (if needed) and activate a CMS plugin';

    public function handle(PluginManager $pluginManager, PluginRegistry $registry): int
    {
        $id = $this->argument('id');
        if (! is_string($id) || $id === '') {
            $this->error('The plugin identifier argument is required.');

            return self::FAILURE;
        }

        $record = $registry->find($id);
        $discovered = $registry->getDiscovered($id);

        if ($record === null && $discovered === null) {
            $this->error("Plugin '{$id}' not found.");

            return self::FAILURE;
        }

        try {
            if ($record === null) {
                $this->info("Installing plugin '{$id}'...");
                $pluginManager->install($id);
            }

            $this->info("Activating plugin '{$id}'...");
            $pluginManager->activate($id);

            $this->info("Plugin '{$id}' has been enabled successfully.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Failed to enable plugin '{$id}': ".$e->getMessage());

            return self::FAILURE;
        }
    }
}
