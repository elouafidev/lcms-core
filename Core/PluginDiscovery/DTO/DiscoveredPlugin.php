<?php

declare(strict_types=1);

namespace Core\PluginDiscovery\DTO;

use Core\Contracts\PluginInterface;
use Core\Contracts\PluginManifestInterface;

readonly class DiscoveredPlugin implements PluginInterface
{
    public function __construct(
        private PluginManifestInterface $manifest,
        private string $path,
        private string $state = 'AVAILABLE'
    ) {}

    public function getManifest(): PluginManifestInterface
    {
        return $this->manifest;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function isActive(): bool
    {
        return $this->state === 'ACTIVE';
    }
}
