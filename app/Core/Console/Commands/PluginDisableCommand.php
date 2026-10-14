<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Console\Command;
use Throwable;

class PluginDisableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:plugin:disable {id : The plugin identifier (e.g. acme.demo-hello)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate a CMS plugin';

    public function handle(PluginManager $pluginManager, PluginRegistry $registry): int
    {
        $id = $this->argument('id');
        if (! is_string($id) || $id === '') {
            $this->error('The plugin identifier argument is required.');

            return self::FAILURE;
        }

        $record = $registry->find($id);

        if ($record === null) {
            $this->error("Plugin '{$id}' is not installed.");

            return self::FAILURE;
        }

        try {
            $pluginManager->deactivate($id);
            $this->info("Plugin '{$id}' has been disabled successfully.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Failed to disable plugin '{$id}': ".$e->getMessage());

            return self::FAILURE;
        }
    }
}
