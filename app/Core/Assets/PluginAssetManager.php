<?php

declare(strict_types=1);

namespace App\Core\Assets;

use Illuminate\Filesystem\Filesystem;

class PluginAssetManager
{
    private Filesystem $files;

    private string $publicBasePath;

    public function __construct(
        ?Filesystem $files = null,
        ?string $publicBasePath = null
    ) {
        $this->files = $files ?? new Filesystem;
        $this->publicBasePath = $publicBasePath ?? public_path('vendor/plugins');
    }

    /**
     * Publish static assets for a plugin from its resources/assets directory.
     */
    public function publish(string $pluginId, string $pluginPath, bool $force = false): bool
    {
        $sourcePath = rtrim($pluginPath, '/\\').'/resources/assets';

        if (! is_dir($sourcePath)) {
            return false;
        }

        $destinationPath = $this->getPublishedPath($pluginId);

        if (! $this->files->isDirectory($destinationPath)) {
            $this->files->makeDirectory($destinationPath, 0755, true, true);
        } elseif ($force) {
            $this->files->cleanDirectory($destinationPath);
        }

        return $this->files->copyDirectory($sourcePath, $destinationPath);
    }

    /**
     * Remove published assets for a specific plugin.
     */
    public function clean(string $pluginId): bool
    {
        $destinationPath = $this->getPublishedPath($pluginId);

        if ($this->files->isDirectory($destinationPath)) {
            return $this->files->deleteDirectory($destinationPath);
        }

        return false;
    }

    /**
     * Check if a plugin has published assets.
     */
    public function hasPublished(string $pluginId): bool
    {
        return $this->files->isDirectory($this->getPublishedPath($pluginId));
    }

    /**
     * Generate an absolute web URL for a published plugin asset.
     */
    public static function url(string $pluginId, string $assetPath): string
    {
        $relativePath = 'vendor/plugins/'.$pluginId.'/'.ltrim($assetPath, '/');

        return asset($relativePath);
    }

    /**
     * Get the absolute filesystem path for a published plugin asset or directory.
     */
    public function getPublishedPath(string $pluginId, string $assetPath = ''): string
    {
        $base = rtrim($this->publicBasePath, '/\\').'/'.$pluginId;

        if ($assetPath === '') {
            return $base;
        }

        return $base.'/'.ltrim($assetPath, '/\\');
    }

    public function getPublicBasePath(): string
    {
        return $this->publicBasePath;
    }
}
