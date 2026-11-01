<?php

declare(strict_types=1);

namespace App\Core\PluginDiscovery;

use App\Core\PluginDiscovery\Autoload\PluginClassLoader;
use App\Core\PluginDiscovery\DTO\DiscoveredPlugin;
use DirectoryIterator;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Throwable;

class PluginDiscovery
{
    public function __construct(
        private ManifestValidator $validator,
        private PluginClassLoader $classLoader,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Scan the plugins directory and return a collection of validated plugins.
     *
     * @return Collection<string, DiscoveredPlugin>
     */
    public function discover(?string $basePath = null): Collection
    {
        $targetPath = $basePath ?? (string) config('cms.plugins.path', base_path('platform/plugins'));

        if (! is_dir($targetPath) || ! is_readable($targetPath)) {
            return collect();
        }

        /** @var array<string, DiscoveredPlugin> $discovered */
        $discovered = [];
        $iterator = new DirectoryIterator($targetPath);

        foreach ($iterator as $item) {
            if ($item->isDot() || ! $item->isDir()) {
                continue;
            }

            $pluginDir = $item->getPathname();
            $manifestPath = $pluginDir.DIRECTORY_SEPARATOR.'plugin.json';

            if (file_exists($manifestPath)) {
                $this->processCandidate($manifestPath, $pluginDir, $discovered);

                continue;
            }

            // Check for nested vendor structure (e.g. platform/plugins/{vendor}/{plugin-slug})
            $subIterator = new DirectoryIterator($pluginDir);
            foreach ($subIterator as $subItem) {
                if ($subItem->isDot() || ! $subItem->isDir()) {
                    continue;
                }

                $nestedDir = $subItem->getPathname();
                $nestedManifestPath = $nestedDir.DIRECTORY_SEPARATOR.'plugin.json';

                if (file_exists($nestedManifestPath)) {
                    $this->processCandidate($nestedManifestPath, $nestedDir, $discovered);
                }
            }
        }

        return collect($discovered);
    }

    /**
     * Process and register a candidate plugin.json file.
     *
     * @param  array<string, DiscoveredPlugin>  $discovered
     */
    private function processCandidate(string $manifestPath, string $pluginDir, array &$discovered): void
    {
        try {
            $manifest = $this->validator->validateFile($manifestPath);
            $normalizedPluginDir = str_replace('\\', '/', $pluginDir);

            // Register PSR-4 autoloading dynamically
            $autoload = $manifest->getAutoload();
            if (isset($autoload['psr-4'])) {
                foreach ($autoload['psr-4'] as $prefix => $relativeDir) {
                    $absoluteDir = $normalizedPluginDir.'/'.trim($relativeDir, '/\\');
                    $this->classLoader->register($prefix, $absoluteDir);
                }
            }

            $discoveredPlugin = new DiscoveredPlugin(
                manifest: $manifest,
                path: $normalizedPluginDir,
                state: 'AVAILABLE'
            );

            $discovered[$manifest->getId()] = $discoveredPlugin;
        } catch (Throwable $e) {
            $this->logger?->warning("Plugin discovery skipped invalid manifest at '{$manifestPath}': {$e->getMessage()}");
        }
    }
}
