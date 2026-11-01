<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Lifecycle\PluginSeederManager;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Console\Command;
use Throwable;

class PluginSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:plugin:seed 
                            {id? : The plugin identifier (e.g. lcmscore.demo-hello)} 
                            {--class= : The class name of the specific seeder to run} 
                            {--all : Seed all active plugins} 
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with records from plugin seeders';

    public function handle(PluginSeederManager $seederManager, PluginRegistry $registry): int
    {
        $id = $this->argument('id');
        $all = (bool) $this->option('all');
        $specificClass = $this->option('class');

        if (! $all && (! is_string($id) || $id === '')) {
            $this->error('Please specify a plugin identifier or use the --all option.');

            return self::FAILURE;
        }

        try {
            if ($all) {
                return $this->seedAll($seederManager, $registry, is_string($specificClass) ? $specificClass : null);
            }

            /** @var string $pluginId */
            $pluginId = $id;

            return $this->seedSingle($pluginId, $seederManager, $registry, is_string($specificClass) ? $specificClass : null);
        } catch (Throwable $e) {
            $this->error('Failed to seed plugin database: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function seedSingle(
        string $id,
        PluginSeederManager $seederManager,
        PluginRegistry $registry,
        ?string $specificClass
    ): int {
        $discovered = $registry->getDiscovered($id);
        $record = $registry->find($id);

        if ($discovered === null && $record === null) {
            $this->error("Plugin '{$id}' not found.");

            return self::FAILURE;
        }

        $path = $discovered !== null ? $discovered->getPath() : $record->getPath();

        $this->info("Seeding plugin: {$id}...");
        $executed = $seederManager->seed($id, $path, $specificClass, $this);

        if (empty($executed)) {
            $this->warn("No seeders found for plugin '{$id}'.");

            return self::SUCCESS;
        }

        foreach ($executed as $class) {
            $this->line("<info>Seeded:</info> {$class}");
        }

        $this->info("Plugin '{$id}' seeded successfully.");

        return self::SUCCESS;
    }

    private function seedAll(
        PluginSeederManager $seederManager,
        PluginRegistry $registry,
        ?string $specificClass
    ): int {
        $activePlugins = $registry->getActive();

        if ($activePlugins->isEmpty()) {
            $this->info('No active plugins found.');

            return self::SUCCESS;
        }

        $seededCount = 0;

        foreach ($activePlugins as $plugin) {
            $discovered = $registry->getDiscovered($plugin->id);
            $path = $discovered !== null ? $discovered->getPath() : $plugin->getPath();

            $this->info("Seeding plugin: {$plugin->id}...");
            $executed = $seederManager->seed($plugin->id, $path, $specificClass, $this);

            if (! empty($executed)) {
                foreach ($executed as $class) {
                    $this->line("<info>Seeded:</info> {$class}");
                }
                $seededCount++;
            } else {
                $this->comment("No seeders found for '{$plugin->id}'.");
            }
        }

        $this->info("Finished seeding active plugins ({$seededCount} plugin(s) with seeders executed).");

        return self::SUCCESS;
    }
}
