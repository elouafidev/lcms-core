<?php

declare(strict_types=1);

namespace App\Core\Contracts;

interface PluginInterface
{
    /**
     * Get the validated immutable manifest of this plugin.
     */
    public function getManifest(): PluginManifestInterface;

    /**
     * Get the absolute root directory path of the plugin.
     */
    public function getPath(): string;

    /**
     * Get current lifecycle state (e.g. AVAILABLE, INSTALLED, ACTIVE, DISABLED, ERROR).
     */
    public function getState(): string;

    /**
     * Check whether the plugin is currently active.
     */
    public function isActive(): bool;
}
