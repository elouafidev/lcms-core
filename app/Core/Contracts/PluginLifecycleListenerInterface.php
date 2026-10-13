<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use Throwable;

interface PluginLifecycleListenerInterface
{
    /**
     * Triggered when a plugin has completed installation.
     */
    public function onInstalled(string $pluginId): void;

    /**
     * Triggered when a plugin has been activated.
     */
    public function onActivated(string $pluginId): void;

    /**
     * Triggered when a plugin has been deactivated.
     */
    public function onDeactivated(string $pluginId): void;

    /**
     * Triggered when a plugin has been uninstalled.
     */
    public function onUninstalled(string $pluginId): void;

    /**
     * Triggered when a critical exception occurs during plugin registration or boot.
     */
    public function onError(string $pluginId, Throwable $exception): void;
}
