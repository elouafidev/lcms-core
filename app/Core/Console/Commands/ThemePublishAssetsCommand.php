<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Themes\ThemeAssetManager;
use App\Core\Themes\ThemeDiscovery;
use App\Core\Themes\ThemeManager;
use Illuminate\Console\Command;
use Throwable;

class ThemePublishAssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:theme:publish-assets 
                            {id? : The theme identifier (e.g. default-modern)} 
                            {--all : Publish assets for all discovered themes} 
                            {--force : Overwrite existing published files} 
                            {--symlink : Create symbolic links instead of copying files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish static assets from theme directories to the public vendor directory';

    public function handle(ThemeAssetManager $assetManager, ThemeDiscovery $discovery, ThemeManager $themeManager): int
    {
        $id = $this->argument('id');
        $all = (bool) $this->option('all');
        $force = (bool) $this->option('force');
        $symlink = (bool) $this->option('symlink');

        if (! $all && (! is_string($id) || trim($id) === '')) {
            // Check if there is an active theme
            $activeTheme = $themeManager->getActiveTheme();
            if ($activeTheme !== null) {
                $id = $activeTheme->getId();
            } else {
                $this->error('Please specify a theme identifier, activate a theme, or use the --all option.');

                return self::FAILURE;
            }
        }

        try {
            if ($all) {
                return $this->publishAll($assetManager, $discovery, $force, $symlink);
            }

            /** @var string $themeId */
            $themeId = $id;

            return $this->publishSingle($themeId, $assetManager, $discovery, $force, $symlink);
        } catch (Throwable $e) {
            $this->error('Failed to publish theme assets: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function publishSingle(string $id, ThemeAssetManager $assetManager, ThemeDiscovery $discovery, bool $force, bool $symlink): int
    {
        $theme = $discovery->get($id);

        if ($theme === null) {
            $this->error("Theme '{$id}' not found.");

            return self::FAILURE;
        }

        $sourcePath = $assetManager->resolveSourceAssetPath($theme->getPath());

        if ($sourcePath === null) {
            $this->warn("Theme '{$id}' does not have any assets in 'resources/assets' or 'assets'.");

            return self::SUCCESS;
        }

        $assetManager->publish($id, $theme->getPath(), $force, $symlink);
        $this->info("Assets for theme '{$id}' published successfully to 'public/vendor/themes/{$id}'.");

        return self::SUCCESS;
    }

    private function publishAll(ThemeAssetManager $assetManager, ThemeDiscovery $discovery, bool $force, bool $symlink): int
    {
        $themes = $discovery->all();

        if (empty($themes)) {
            $this->info('No themes found to publish assets.');

            return self::SUCCESS;
        }

        $publishedCount = 0;

        foreach ($themes as $theme) {
            $sourcePath = $assetManager->resolveSourceAssetPath($theme->getPath());
            if ($sourcePath !== null) {
                $assetManager->publish($theme->getId(), $theme->getPath(), $force, $symlink);
                $this->line("Published assets for theme <info>{$theme->getId()}</info>");
                $publishedCount++;
            }
        }

        $this->info("Published assets for {$publishedCount} theme(s).");

        return self::SUCCESS;
    }
}
