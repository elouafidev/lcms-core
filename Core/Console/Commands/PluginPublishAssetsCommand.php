<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Assets\PluginAssetManager;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Console\Command;
use Throwable;

class PluginPublishAssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:plugin:publish-assets 
                            {id? : The plugin identifier (e.g. lcmscore.demo-hello)} 
                            {--all : Publish assets for all active plugins} 
                            {--force : Overwrite existing published files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish static assets from plugin directories to the public vendor directory';

    public function handle(PluginAssetManager $assetManager, PluginRegistry $registry): int
    {
        $id = $this->argument('id');
        $all = (bool) $this->option('all');
        $force = (bool) $this->option('force');

        if (! $all && (! is_string($id) || $id === '')) {
            $this->error('Please specify a plugin identifier or use the --all option.');

            return self::FAILURE;
        }

        try {
            if ($all) {
                return $this->publishAll($assetManager, $registry, $force);
            }

            /** @var string $pluginId */
            $pluginId = $id;

            return $this->publishSingle($pluginId, $assetManager, $registry, $force);
        } catch (Throwable $e) {
            $this->error('Failed to publish assets: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function publishSingle(string $id, PluginAssetManager $assetManager, PluginRegistry $registry, bool $force): int
    {
        $discovered = $registry->getDiscovered($id);
        $record = $registry->find($id);

        if ($discovered === null && $record === null) {
            $this->error("Plugin '{$id}' not found.");

            return self::FAILURE;
        }

        $path = $discovered !== null ? $discovered->getPath() : $record->getPath();

        if ($path === '' || ! is_dir($path.'/resources/assets')) {
            $this->warn("Plugin '{$id}' does not have any assets in 'resources/assets'.");

            return self::SUCCESS;
        }

        $assetManager->publish($id, $path, $force);
        $this->info("Assets for plugin '{$id}' published successfully to 'public/vendor/plugins/{$id}'.");

        return self::SUCCESS;
    }

    private function publishAll(PluginAssetManager $assetManager, PluginRegistry $registry, bool $force): int
    {
        $activePlugins = $registry->getActive();

        if ($activePlugins->isEmpty()) {
            $this->info('No active plugins found.');

            return self::SUCCESS;
        }

        $publishedCount = 0;

        foreach ($activePlugins as $plugin) {
            $discovered = $registry->getDiscovered($plugin->id);
            $path = $discovered !== null ? $discovered->getPath() : $plugin->getPath();
            if (is_dir($path.'/resources/assets')) {
                $assetManager->publish($plugin->id, $path, $force);
                $this->line("Published assets for <info>{$plugin->id}</info>");
                $publishedCount++;
            }
        }

        $this->info("Published assets for {$publishedCount} active plugin(s).");

        return self::SUCCESS;
    }
}
