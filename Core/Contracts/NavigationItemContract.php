<?php

declare(strict_types=1);

namespace App\Core\Contracts;

interface NavigationItemContract
{
    public function getId(): string;

    public function getLabel(): string;

    public function getUrl(): string;

    public function getIcon(): ?string;

    public function getPriority(): int;

    public function getPermission(): ?string;

    public function getPluginId(): ?string;

    /**
     * @return list<NavigationItemContract>
     */
    public function getChildren(): array;
}
