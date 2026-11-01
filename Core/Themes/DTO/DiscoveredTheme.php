<?php

declare(strict_types=1);

namespace App\Core\Themes\DTO;

use App\Core\Contracts\ThemeInterface;
use App\Core\Contracts\ThemeManifestInterface;

class DiscoveredTheme implements ThemeInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly string $path,
        private readonly ThemeManifestInterface $manifest,
        private bool $active = false
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getManifest(): ThemeManifestInterface
    {
        return $this->manifest;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getViewPath(): string
    {
        if (is_dir($this->path.'/views')) {
            return $this->path.'/views';
        }

        return $this->path.'/resources/views';
    }

    public function getAssetPath(): string
    {
        if (is_dir($this->path.'/assets')) {
            return $this->path.'/assets';
        }

        return $this->path.'/resources/assets';
    }
}
