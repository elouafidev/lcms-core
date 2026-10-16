<?php

declare(strict_types=1);

namespace App\Core\Permissions;

use App\Core\Contracts\PluginManifestInterface;

class PermissionRegistry
{
    /**
     * Stored permissions.
     *
     * @var array<string, array{key: string, label: string, plugin_id: ?string, description: ?string}>
     */
    private array $permissions = [];

    /**
     * Register a new permission.
     */
    public function register(string $key, string $label, ?string $pluginId = null, ?string $description = null): void
    {
        $this->permissions[$key] = [
            'key' => $key,
            'label' => $label,
            'plugin_id' => $pluginId,
            'description' => $description,
        ];
    }

    /**
     * Register all permissions declared by a plugin manifest.
     */
    public function registerFromManifest(PluginManifestInterface $manifest): void
    {
        $pluginId = $manifest->getId();

        foreach ($manifest->getPermissions() as $permission) {
            $this->register(
                key: $permission['key'],
                label: $permission['label'],
                pluginId: $pluginId
            );
        }
    }

    /**
     * Unregister a permission by key.
     */
    public function unregister(string $key): void
    {
        unset($this->permissions[$key]);
    }

    /**
     * Unregister all permissions associated with a specific plugin.
     */
    public function unregisterByPlugin(string $pluginId): void
    {
        foreach ($this->permissions as $key => $permission) {
            if ($permission['plugin_id'] === $pluginId) {
                unset($this->permissions[$key]);
            }
        }
    }

    /**
     * Determine if a permission key is registered.
     */
    public function has(string $key): bool
    {
        return isset($this->permissions[$key]);
    }

    /**
     * Get a specific permission definition.
     *
     * @return array{key: string, label: string, plugin_id: ?string, description: ?string}|null
     */
    public function get(string $key): ?array
    {
        return $this->permissions[$key] ?? null;
    }

    /**
     * Get all registered permissions.
     *
     * @return array<string, array{key: string, label: string, plugin_id: ?string, description: ?string}>
     */
    public function all(): array
    {
        return $this->permissions;
    }

    /**
     * Get all permissions belonging to a specific plugin.
     *
     * @return array<string, array{key: string, label: string, plugin_id: ?string, description: ?string}>
     */
    public function getByPlugin(string $pluginId): array
    {
        return array_filter(
            $this->permissions,
            fn (array $p): bool => $p['plugin_id'] === $pluginId
        );
    }

    /**
     * Flush all registered permissions.
     */
    public function flush(): void
    {
        $this->permissions = [];
    }
}
