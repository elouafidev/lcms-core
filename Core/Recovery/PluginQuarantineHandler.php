<?php

declare(strict_types=1);

namespace App\Core\Recovery;

use App\Core\PluginRegistry\Models\PluginRecord;
use App\Core\PluginRegistry\PluginRegistry;
use App\Core\Recovery\Models\PluginErrorRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use Psr\Log\LoggerInterface;
use Throwable;

class PluginQuarantineHandler
{
    public function __construct(
        private PluginRegistry $registry,
        private ?LoggerInterface $logger = null
    ) {}

    public function getRegistry(): PluginRegistry
    {
        return $this->registry;
    }

    /**
     * Quarantine a failing plugin: transition to ERROR state, deactivate, and record stack trace.
     */
    public function quarantine(PluginRecord $plugin, Throwable $exception, ?string $providerClass = null): PluginErrorRecord
    {
        $errorMessage = 'Quarantined due to ['.get_class($exception).']: '.$exception->getMessage();

        // 1. Update plugin record state and error_message via registry
        $this->registry->recordError($plugin->id, $errorMessage);
        $plugin->refresh();

        // 2. Persist audit details in cms_plugin_errors table if table exists
        $errorRecord = new PluginErrorRecord([
            'plugin_id' => $plugin->id,
            'provider' => $providerClass,
            'error_message' => $exception->getMessage(),
            'stack_trace' => $exception->getTraceAsString(),
            'context' => [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
            ],
        ]);

        if (Schema::hasTable('cms_plugin_errors')) {
            $errorRecord->save();
        }

        // 3. Log critical message
        $this->logger?->critical("Plugin [{$plugin->id}] quarantined: {$errorMessage}", [
            'plugin_id' => $plugin->id,
            'provider' => $providerClass,
            'exception' => $exception,
        ]);

        return $errorRecord;
    }

    /**
     * Get all recorded errors for a specific plugin.
     *
     * @return Collection<int, PluginErrorRecord>
     */
    public function getErrorsForPlugin(string $pluginId): Collection
    {
        if (! Schema::hasTable('cms_plugin_errors')) {
            return new Collection;
        }

        return PluginErrorRecord::query()
            ->where('plugin_id', $pluginId)
            ->latest('created_at')
            ->get();
    }

    /**
     * Get all plugin errors recorded in the system.
     *
     * @return Collection<int, PluginErrorRecord>
     */
    public function getAllErrors(): Collection
    {
        if (! Schema::hasTable('cms_plugin_errors')) {
            return new Collection;
        }

        return PluginErrorRecord::query()
            ->latest('created_at')
            ->get();
    }

    /**
     * Clear recorded errors for a given plugin.
     */
    public function clearErrorsForPlugin(string $pluginId): void
    {
        if (Schema::hasTable('cms_plugin_errors')) {
            PluginErrorRecord::query()->where('plugin_id', $pluginId)->delete();
        }
    }
}
