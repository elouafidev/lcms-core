<?php

declare(strict_types=1);

namespace App\Core\PluginRegistry;

use App\Core\Contracts\PluginInterface;
use App\Core\Lifecycle\PluginState;
use App\Core\PluginDiscovery\DTO\DiscoveredPlugin;
use App\Core\PluginDiscovery\PluginDiscovery;
use App\Core\PluginRegistry\Models\PluginRecord;
use Illuminate\Support\Collection;

class PluginRegistry
{
    public function __construct(
        private PluginDiscovery $discovery
    ) {}

    public function getDiscovery(): PluginDiscovery
    {
        return $this->discovery;
    }

    /**
     * Get all plugins known to the CMS, combining filesystem discovery and database state.
     *
     * @return Collection<string, PluginInterface>
     */
    public function getAll(): Collection
    {
        $discovered = $this->discovery->discover();

        /** @var Collection<string, PluginRecord> $records */
        $records = PluginRecord::all()->keyBy('id');

        /** @var Collection<string, PluginInterface> $all */
        $all = collect();

        foreach ($discovered as $id => $plugin) {
            $record = $records->get($id);
            if ($record instanceof PluginRecord) {
                $all->put($id, $record);
            } else {
                $all->put($id, $plugin);
            }
        }

        // Include any database records that might not be physically present on disk
        foreach ($records as $id => $record) {
            if (! $all->has($id)) {
                $all->put($id, $record);
            }
        }

        return $all;
    }

    public function find(string $id): ?PluginRecord
    {
        /** @var PluginRecord|null $record */
        $record = PluginRecord::query()->find($id);

        return $record;
    }

    public function getDiscovered(string $id): ?DiscoveredPlugin
    {
        /** @var DiscoveredPlugin|null $plugin */
        $plugin = $this->discovery->discover()->get($id);

        return $plugin;
    }

    /**
     * Get all active plugins resolved for boot execution.
     *
     * @return Collection<string, PluginRecord>
     */
    public function getActive(): Collection
    {
        return PluginRecord::query()
            ->where('is_active', true)
            ->where('state', PluginState::ACTIVE->value)
            ->get()
            ->keyBy('id');
    }

    public function isInstalled(string $id): bool
    {
        return PluginRecord::query()->where('id', $id)->exists();
    }

    public function isActive(string $id): bool
    {
        return PluginRecord::query()
            ->where('id', $id)
            ->where('is_active', true)
            ->where('state', PluginState::ACTIVE->value)
            ->exists();
    }

    public function save(PluginRecord $record): PluginRecord
    {
        $record->save();

        return $record;
    }

    public function recordError(string $id, string $errorMessage): void
    {
        $record = $this->find($id);
        if ($record !== null) {
            $record->state = PluginState::ERROR;
            $record->is_active = false;
            $record->error_message = $errorMessage;
            $record->save();
        }
    }
}
