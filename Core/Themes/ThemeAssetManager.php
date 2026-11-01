<?php

declare(strict_types=1);

namespace App\Core\Themes;

use Illuminate\Filesystem\Filesystem;
use Throwable;

class ThemeAssetManager
{
    private Filesystem $files;

    private string $publicBasePath;

    public function __construct(
        ?Filesystem $files = null,
        ?string $publicBasePath = null
    ) {
        $this->files = $files ?? new Filesystem;
        $this->publicBasePath = $publicBasePath ?? public_path('vendor/themes');
    }

    /**
     * Publish static assets for a theme from its assets or resources/assets directory.
     */
    public function publish(string $themeId, string $themePath, bool $force = false, bool $symlink = false): bool
    {
        $sourcePath = $this->resolveSourceAssetPath($themePath);

        if ($sourcePath === null) {
            return false;
        }

        $destinationPath = $this->getPublishedPath($themeId);

        if ($symlink) {
            return $this->publishSymlink($sourcePath, $destinationPath, $force);
        }

        return $this->publishCopy($sourcePath, $destinationPath, $force);
    }

    /**
     * Remove published assets for a specific theme.
     */
    public function clean(string $themeId): bool
    {
        $destinationPath = $this->getPublishedPath($themeId);

        if ($this->files->isDirectory($destinationPath) || is_link($destinationPath)) {
            return $this->files->deleteDirectory($destinationPath);
        }

        return false;
    }

    /**
     * Check if a theme has published assets.
     */
    public function hasPublished(string $themeId): bool
    {
        $path = $this->getPublishedPath($themeId);

        return $this->files->isDirectory($path) || is_link($path);
    }

    /**
     * Generate an absolute web URL for a published theme asset.
     */
    public static function url(string $themeId, string $assetPath): string
    {
        $relativePath = 'vendor/themes/'.$themeId.'/'.ltrim($assetPath, '/');

        return asset($relativePath);
    }

    /**
     * Get the absolute filesystem path for a published theme asset or directory.
     */
    public function getPublishedPath(string $themeId, string $assetPath = ''): string
    {
        $base = rtrim($this->publicBasePath, '/\\').'/'.$themeId;

        if ($assetPath === '') {
            return $base;
        }

        return $base.'/'.ltrim($assetPath, '/\\');
    }

    public function getPublicBasePath(): string
    {
        return $this->publicBasePath;
    }

    /**
     * Resolve the source asset directory for a theme.
     */
    public function resolveSourceAssetPath(string $themePath): ?string
    {
        $normalizedThemePath = rtrim($themePath, '/\\');

        $candidates = [
            $normalizedThemePath.'/resources/assets',
            $normalizedThemePath.'/assets',
        ];

        foreach ($candidates as $candidate) {
            if (is_dir($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function publishCopy(string $sourcePath, string $destinationPath, bool $force): bool
    {
        if (! $this->files->isDirectory($destinationPath)) {
            $this->files->makeDirectory($destinationPath, 0755, true, true);
        } elseif ($force) {
            $this->files->cleanDirectory($destinationPath);
        }

        return $this->files->copyDirectory($sourcePath, $destinationPath);
    }

    private function publishSymlink(string $sourcePath, string $destinationPath, bool $force): bool
    {
        if ($this->files->exists($destinationPath) || is_link($destinationPath)) {
            if (! $force) {
                return true;
            }
            $this->files->delete($destinationPath);
            if ($this->files->isDirectory($destinationPath)) {
                $this->files->deleteDirectory($destinationPath);
            }
        }

        // Ensure parent directory exists
        $parentDir = dirname($destinationPath);
        if (! $this->files->isDirectory($parentDir)) {
            $this->files->makeDirectory($parentDir, 0755, true, true);
        }

        try {
            $this->files->link($sourcePath, $destinationPath);

            return true;
        } catch (Throwable) {
            // Fallback to copy if symlink creation is not permitted on the host OS
            return $this->publishCopy($sourcePath, $destinationPath, $force);
        }
    }
}
