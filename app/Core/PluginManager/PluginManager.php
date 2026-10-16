<?php

declare(strict_types=1);

namespace App\Core\PluginManager;

use App\Core\Admin\NavigationRegistry;
use App\Core\DependencyResolver\DependencyResolver;
use App\Core\Lifecycle\Exceptions\InvalidStateTransitionException;
use App\Core\Lifecycle\PluginMigrationManager;
use App\Core\Lifecycle\PluginState;
use App\Core\Permissions\PermissionRegistry;
use App\Core\PluginRegistry\Models\PluginRecord;
use App\Core\PluginRegistry\PluginRegistry;
use App\Core\Recovery\PluginQuarantineHandler;
use App\Core\Recovery\SafeModeManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class PluginManager
{
    private DependencyResolver $dependencyResolver;

    private SafeModeManager $safeModeManager;

    private PluginQuarantineHandler $quarantineHandler;

    private NavigationRegistry $navigationRegistry;

    private PermissionRegistry $permissionRegistry;

    public function __construct(
        private PluginRegistry $registry,
        private PluginMigrationManager $migrationManager,
        private Application $app,
        private ?LoggerInterface $logger = null,
        ?DependencyResolver $dependencyResolver = null,
        ?SafeModeManager $safeModeManager = null,
        ?PluginQuarantineHandler $quarantineHandler = null,
        ?NavigationRegistry $navigationRegistry = null,
        ?PermissionRegistry $permissionRegistry = null
    ) {
        $this->dependencyResolver = $dependencyResolver ?? new DependencyResolver;
        $this->safeModeManager = $safeModeManager ?? new SafeModeManager;
        $this->quarantineHandler = $quarantineHandler ?? new PluginQuarantineHandler($this->registry, $this->logger);
        $this->navigationRegistry = $navigationRegistry ?? new NavigationRegistry($this->registry);
        $this->permissionRegistry = $permissionRegistry ?? new PermissionRegistry;
    }

    /**
     * Install a discovered plugin and execute its isolated database migrations.
     */
    public function install(string $pluginId): PluginRecord
    {
        $existing = $this->registry->find($pluginId);
        $discovered = $this->registry->getDiscovered($pluginId);

        if ($existing !== null) {
            $path = $discovered !== null ? $discovered->getPath() : $existing->getPath();
            $this->migrationManager->migrate($pluginId, $path);

            return $existing;
        }

        if ($discovered === null) {
            throw new RuntimeException("Cannot install plugin '{$pluginId}': Not found on disk.");
        }

        $manifest = $discovered->getManifest();

        $record = new PluginRecord([
            'id' => $manifest->getId(),
            'name' => $manifest->getName(),
            'version' => $manifest->getVersion(),
            'state' => PluginState::INSTALLED,
            'is_active' => false,
            'manifest' => $manifest->toArray(),
            'error_message' => null,
        ]);

        $saved = $this->registry->save($record);

        // Execute plugin migrations
        $this->migrationManager->migrate($pluginId, $discovered->getPath());

        return $saved;
    }

    /**
     * Activate an installed or disabled plugin.
     */
    public function activate(string $pluginId): PluginRecord
    {
        $record = $this->registry->find($pluginId);
        if ($record === null) {
            throw new RuntimeException("Cannot activate plugin '{$pluginId}': Plugin is not installed.");
        }

        if (! $record->state->canTransitionTo(PluginState::ACTIVE)) {
            throw InvalidStateTransitionException::forbidden($pluginId, $record->state->value, PluginState::ACTIVE->value);
        }

        // Validate dependencies and verify absence of circular dependency loops
        $candidates = $this->registry->getActive()->all();
        $candidates[$pluginId] = $record;

        $toProcess = [$pluginId];
        $visited = [];

        while (! empty($toProcess)) {
            $currentId = array_shift($toProcess);
            if (isset($visited[$currentId])) {
                continue;
            }
            $visited[$currentId] = true;

            $currentPlugin = $candidates[$currentId] ?? $this->registry->find($currentId) ?? $this->registry->getDiscovered($currentId);
            if ($currentPlugin !== null) {
                $candidates[$currentId] = $currentPlugin;
                foreach (array_keys($currentPlugin->getManifest()->getDependencies()) as $depId) {
                    if (! isset($visited[$depId])) {
                        $toProcess[] = $depId;
                    }
                }
            }
        }

        $this->dependencyResolver->resolve($candidates, checkMissing: true);

        $record->state = PluginState::ACTIVE;
        $record->is_active = true;
        $record->error_message = null;
        $record->save();

        $this->permissionRegistry->registerFromManifest($record->getManifest());

        $this->registry->getDiscovery()->discover();
        $this->bootPlugin($record);

        return $record;
    }

    /**
     * Deactivate an active plugin.
     */
    public function deactivate(string $pluginId): PluginRecord
    {
        $record = $this->registry->find($pluginId);
        if ($record === null) {
            throw new RuntimeException("Cannot deactivate plugin '{$pluginId}': Plugin is not installed.");
        }

        if (! $record->state->canTransitionTo(PluginState::DISABLED)) {
            throw InvalidStateTransitionException::forbidden($pluginId, $record->state->value, PluginState::DISABLED->value);
        }

        $record->state = PluginState::DISABLED;
        $record->is_active = false;
        $record->save();

        $this->navigationRegistry->unregisterByPlugin($pluginId);
        $this->permissionRegistry->unregisterByPlugin($pluginId);

        return $record;
    }

    /**
     * Check whether Emergency Safe Mode is active (via file flag or configuration).
     */
    public function isSafeMode(): bool
    {
        return $this->safeModeManager->isEnabled();
    }

    public function getSafeModeManager(): SafeModeManager
    {
        return $this->safeModeManager;
    }

    public function getQuarantineHandler(): PluginQuarantineHandler
    {
        return $this->quarantineHandler;
    }

    public function getNavigationRegistry(): NavigationRegistry
    {
        return $this->navigationRegistry;
    }

    public function getPermissionRegistry(): PermissionRegistry
    {
        return $this->permissionRegistry;
    }

    public function getRegistry(): PluginRegistry
    {
        return $this->registry;
    }

    public function getDependencyResolver(): DependencyResolver
    {
        return $this->dependencyResolver;
    }

    /**
     * Boot all active plugins at application startup.
     */
    public function boot(): void
    {
        // Emergency Safe Mode bypasses all plugins
        if ($this->safeModeManager->isEnabled()) {
            $this->logger?->info('Emergency Safe Mode active: Skipping third-party plugin boot.');

            return;
        }

        try {
            if (! Schema::hasTable('cms_plugins')) {
                return;
            }

            $this->registry->getDiscovery()->discover();

            $activePlugins = $this->registry->getActive();
            $orderedPlugins = $this->dependencyResolver->resolve($activePlugins, checkMissing: false);

            foreach ($orderedPlugins as $plugin) {
                if ($plugin instanceof PluginRecord) {
                    $this->permissionRegistry->registerFromManifest($plugin->getManifest());
                    $this->bootPlugin($plugin);
                }
            }
        } catch (Throwable $e) {
            $this->logger?->warning('PluginManager boot skipped: Database not ready or unreachable ('.$e->getMessage().').');
        }
    }

    /**
     * Boot a single plugin's ServiceProviders within an isolated quarantine boundary.
     */
    public function bootPlugin(PluginRecord $plugin): void
    {
        $manifest = $plugin->getManifest();

        foreach ($manifest->getProviders() as $providerClass) {
            try {
                if (class_exists($providerClass)) {
                    $this->app->register($providerClass);
                }
            } catch (Throwable $e) {
                $this->quarantineHandler->quarantine($plugin, $e, $providerClass);

                break;
            }
        }
    }

    /**
     * Uninstall a plugin, optionally purging its database tables.
     */
    public function uninstall(string $pluginId, bool $purgeData = false): void
    {
        $record = $this->registry->find($pluginId);
        if ($record === null) {
            return;
        }

        if ($record->is_active) {
            $this->deactivate($pluginId);
        }

        $this->navigationRegistry->unregisterByPlugin($pluginId);
        $this->permissionRegistry->unregisterByPlugin($pluginId);

        if ($purgeData) {
            $discovered = $this->registry->getDiscovered($pluginId);
            $path = $discovered !== null ? $discovered->getPath() : $record->getPath();
            $this->migrationManager->rollback($pluginId, $path);
        }

        $record->delete();
    }
}
