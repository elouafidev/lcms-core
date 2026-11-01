<?php

declare(strict_types=1);

namespace App\Core\Admin;

use App\Core\Contracts\NavigationItemContract;
use App\Core\PluginRegistry\PluginRegistry;

class NavigationRegistry
{
    /**
     * Stored navigation items keyed by their ID.
     *
     * @var array<string, NavigationItemContract>
     */
    private array $items = [];

    public function __construct(
        private ?PluginRegistry $pluginRegistry = null
    ) {}

    /**
     * Register a navigation item.
     */
    public function register(NavigationItemContract $item): void
    {
        $this->items[$item->getId()] = $item;
    }

    /**
     * Helper to create and register a navigation item.
     *
     * @param  list<NavigationItemContract>  $children
     */
    public function registerItem(
        string $id,
        string $label,
        string $url,
        ?string $icon = null,
        int $priority = 50,
        ?string $permission = null,
        ?string $pluginId = null,
        array $children = []
    ): NavigationItemContract {
        $item = new NavigationItem(
            id: $id,
            label: $label,
            url: $url,
            icon: $icon,
            priority: $priority,
            permission: $permission,
            pluginId: $pluginId,
            children: $children
        );

        $this->register($item);

        return $item;
    }

    /**
     * Unregister a navigation item by its unique ID.
     */
    public function unregister(string $id): void
    {
        unset($this->items[$id]);
    }

    /**
     * Unregister all navigation items associated with a plugin.
     */
    public function unregisterByPlugin(string $pluginId): void
    {
        foreach ($this->items as $id => $item) {
            if ($item->getPluginId() === $pluginId) {
                unset($this->items[$id]);
            }
        }
    }

    public function has(string $id): bool
    {
        return isset($this->items[$id]);
    }

    public function get(string $id): ?NavigationItemContract
    {
        return $this->items[$id] ?? null;
    }

    /**
     * Get all navigation items, sorted by priority ascending.
     * If $filterInactivePlugins is true, items belonging to disabled/inactive plugins are filtered out.
     *
     * @return list<NavigationItemContract>
     */
    public function getItems(bool $filterInactivePlugins = true): array
    {
        $filtered = array_values(array_filter($this->items, function (NavigationItemContract $item) use ($filterInactivePlugins): bool {
            if (! $filterInactivePlugins) {
                return true;
            }

            $pluginId = $item->getPluginId();
            if ($pluginId === null) {
                return true;
            }

            if ($this->pluginRegistry === null) {
                return true;
            }

            return $this->pluginRegistry->isActive($pluginId);
        }));

        usort($filtered, fn (NavigationItemContract $a, NavigationItemContract $b): int => $a->getPriority() <=> $b->getPriority());

        return $filtered;
    }

    /**
     * @return array<string, NavigationItemContract>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function flush(): void
    {
        $this->items = [];
    }
}
