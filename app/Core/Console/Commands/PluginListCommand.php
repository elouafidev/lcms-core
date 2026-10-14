<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Console\Command;

class PluginListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:plugin:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all discovered and registered CMS plugins with their status';

    public function handle(PluginRegistry $registry): int
    {
        $plugins = $registry->getAll();

        if ($plugins->isEmpty()) {
            $this->info('No plugins found in platform/plugins.');

            return self::SUCCESS;
        }

        $headers = ['ID', 'Name', 'Version', 'State', 'Active'];
        $rows = [];

        foreach ($plugins as $plugin) {
            $manifest = $plugin->getManifest();
            $rows[] = [
                $manifest->getId(),
                $manifest->getName(),
                $manifest->getVersion(),
                $plugin->getState(),
                $plugin->isActive() ? 'yes' : 'no',
            ];
        }

        $this->table($headers, $rows);

        return self::SUCCESS;
    }
}
