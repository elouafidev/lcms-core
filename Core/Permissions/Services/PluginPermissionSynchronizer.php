<?php

declare(strict_types=1);

namespace Core\Permissions\Services;

use Core\Contracts\PluginManifestInterface;
use Core\PluginRegistry\PluginRegistry;
use Core\RBAC\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Psr\Log\LoggerInterface;
use Throwable;

class PluginPermissionSynchronizer
{
    public function __construct(
        private readonly PluginRegistry $pluginRegistry,
        private readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * Synchronize permissions declared by a plugin manifest into the database.
     */
    public function syncPlugin(PluginManifestInterface $manifest): void
    {
        if (! $this->isTableReady()) {
            return;
        }

        $pluginId = $manifest->getId();
        $declaredPermissions = $manifest->getPermissions();
        $activeKeys = [];

        try {
            DB::transaction(function () use ($manifest, $pluginId, $declaredPermissions, &$activeKeys): void {
                foreach ($declaredPermissions as $perm) {
                    $slug = trim((string) $perm['key']);
                    if ($slug === '') {
                        continue;
                    }

                    $label = trim((string) $perm['label']);
                    $description = isset($perm['description']) ? trim((string) $perm['description']) : null;
                    $group = isset($perm['group']) && trim((string) $perm['group']) !== ''
                        ? trim((string) $perm['group'])
                        : $manifest->getName();

                    // Security check: do not overwrite core system permissions (where plugin_id is null)
                    $existing = Permission::where('slug', $slug)->first();
                    if ($existing !== null && $existing->plugin_id === null) {
                        $this->logger?->warning("Plugin [{$pluginId}] attempted to override core system permission [{$slug}]. Skipped.");

                        continue;
                    }

                    Permission::updateOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => $label !== '' ? $label : $slug,
                            'group' => $group,
                            'plugin_id' => $pluginId,
                            'description' => $description,
                        ]
                    );

                    $activeKeys[] = $slug;
                }

                // Delete any old permissions of this plugin that were removed from the manifest
                Permission::where('plugin_id', $pluginId)
                    ->whereNotIn('slug', $activeKeys)
                    ->delete();
            });
        } catch (Throwable $e) {
            $this->logger?->error("Failed to sync permissions for plugin [{$pluginId}]: ".$e->getMessage());
        }
    }

    /**
     * Revoke and delete all permissions associated with a plugin.
     * Pivot table permission_role cascades and automatically detaches them from roles.
     */
    public function revokePlugin(string $pluginId): int
    {
        if (! $this->isTableReady()) {
            return 0;
        }

        try {
            return DB::transaction(function () use ($pluginId): int {
                $count = Permission::where('plugin_id', $pluginId)->delete();
                $this->logger?->info("Cleaned up {$count} permission(s) for plugin [{$pluginId}].");

                return $count;
            });
        } catch (Throwable $e) {
            $this->logger?->error("Failed to revoke permissions for plugin [{$pluginId}]: ".$e->getMessage());

            return 0;
        }
    }

    /**
     * Synchronize permissions for all active plugins and purge orphaned permissions.
     */
    public function syncAllActive(): void
    {
        if (! $this->isTableReady()) {
            return;
        }

        try {
            $activePlugins = $this->pluginRegistry->getActive();
            $activePluginIds = [];

            foreach ($activePlugins as $plugin) {
                $manifest = $plugin->getManifest();
                $this->syncPlugin($manifest);
                $activePluginIds[] = $manifest->getId();
            }

            $this->cleanOrphans($activePluginIds);
        } catch (Throwable $e) {
            $this->logger?->error('Failed to sync all active plugin permissions: '.$e->getMessage());
        }
    }

    /**
     * Clean up permissions for plugins that are not in the provided active list or no longer exist.
     *
     * @param  list<string>|null  $knownActiveIds
     */
    public function cleanOrphans(?array $knownActiveIds = null): int
    {
        if (! $this->isTableReady()) {
            return 0;
        }

        try {
            if ($knownActiveIds === null) {
                $knownActiveIds = array_keys($this->pluginRegistry->getActive()->all());
            }

            return Permission::whereNotNull('plugin_id')
                ->whereNotIn('plugin_id', $knownActiveIds)
                ->delete();
        } catch (Throwable $e) {
            $this->logger?->error('Failed to clean orphaned permissions: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Check if the permissions table exists and is accessible.
     */
    private function isTableReady(): bool
    {
        try {
            return Schema::hasTable('permissions');
        } catch (Throwable) {
            return false;
        }
    }
}
