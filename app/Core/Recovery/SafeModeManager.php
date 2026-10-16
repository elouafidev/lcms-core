<?php

declare(strict_types=1);

namespace App\Core\Recovery;

class SafeModeManager
{
    public const DEFAULT_SENTINEL_RELATIVE_PATH = 'framework/cms_safe_mode';

    public function __construct(
        private ?string $sentinelPath = null
    ) {
        $this->sentinelPath = $sentinelPath ?? storage_path(self::DEFAULT_SENTINEL_RELATIVE_PATH);
    }

    /**
     * Determine if Emergency Safe Mode is active (via sentinel file, config or env).
     */
    public function isEnabled(): bool
    {
        if (file_exists($this->getSentinelPath())) {
            return true;
        }

        if ((bool) config('cms.safe_mode.enabled', false)) {
            return true;
        }

        $envValue = env('CMS_SAFE_MODE');
        if ($envValue !== null && filter_var($envValue, FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        return false;
    }

    /**
     * Enable Emergency Safe Mode by writing the sentinel file.
     */
    public function enable(): void
    {
        $path = $this->getSentinelPath();
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, (string) now());
    }

    /**
     * Disable Emergency Safe Mode by removing the sentinel file.
     */
    public function disable(): void
    {
        $path = $this->getSentinelPath();

        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function getSentinelPath(): string
    {
        return $this->sentinelPath ?? storage_path(self::DEFAULT_SENTINEL_RELATIVE_PATH);
    }
}
