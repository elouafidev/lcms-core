<?php

declare(strict_types=1);

namespace App\Core\PluginManager;

use App\Core\Lifecycle\Exceptions\InvalidStateTransitionException;
use App\Core\Lifecycle\PluginMigrationManager;
use App\Core\Lifecycle\PluginState;
use App\Core\PluginRegistry\Models\PluginRecord;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class PluginManager
{
    public function __construct(
        private PluginRegistry $registry,
        private PluginMigrationManager $migrationManager,
        private Application $app,
        private ?LoggerInterface $logger = null
    ) {}

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

        $record->state = PluginState::ACTIVE;
        $record->is_active = true;
        $record->error_message = null;
        $record->save();

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

        return $record;
    }

    /**
     * Boot all active plugins at application startup.
     */
    public function boot(): void
    {
        // Emergency Safe Mode bypasses all plugins
        if ((bool) config('cms.safe_mode.enabled', false)) {
            $this->logger?->info('Emergency Safe Mode active: Skipping third-party plugin boot.');

            return;
        }

        try {
            if (! Schema::hasTable('cms_plugins')) {
                return;
            }

            $activePlugins = $this->registry->getActive();

            foreach ($activePlugins as $plugin) {
                $this->bootPlugin($plugin);
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
                $errorMessage = "Unhandled exception during boot of [{$providerClass}]: {$e->getMessage()}";
                $this->registry->recordError($plugin->id, $errorMessage);
                $this->logger?->critical("Plugin [{$plugin->id}] quarantined: {$errorMessage}", [
                    'exception' => $e,
                ]);

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

        if ($purgeData) {
            $discovered = $this->registry->getDiscovered($pluginId);
            $path = $discovered !== null ? $discovered->getPath() : $record->getPath();
            $this->migrationManager->rollback($pluginId, $path);
        }

        $record->delete();
    }
}
