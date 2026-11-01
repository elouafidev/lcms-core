<?php

declare(strict_types=1);

namespace App\Core\Admin;

use App\Core\Contracts\NavigationItemContract;

class NavigationItem implements NavigationItemContract
{
    /**
     * @param  list<NavigationItemContract>  $children
     */
    public function __construct(
        private string $id,
        private string $label,
        private string $url,
        private ?string $icon = null,
        private int $priority = 50,
        private ?string $permission = null,
        private ?string $pluginId = null,
        private array $children = []
    ) {}

    /**
     * @param  list<NavigationItemContract>  $children
     */
    public static function make(
        string $id,
        string $label,
        string $url,
        ?string $icon = null,
        int $priority = 50,
        ?string $permission = null,
        ?string $pluginId = null,
        array $children = []
    ): self {
        return new self($id, $label, $url, $icon, $priority, $permission, $pluginId, $children);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getPluginId(): ?string
    {
        return $this->pluginId;
    }

    /**
     * @return list<NavigationItemContract>
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
