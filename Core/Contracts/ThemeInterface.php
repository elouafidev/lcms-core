<?php

declare(strict_types=1);

namespace App\Core\Contracts;

interface ThemeInterface
{
    /**
     * Unique theme identifier.
     */
    public function getId(): string;

    /**
     * Human-readable name.
     */
    public function getName(): string;

    /**
     * Absolute filesystem path to the theme directory.
     */
    public function getPath(): string;

    /**
     * Parsed theme manifest instance.
     */
    public function getManifest(): ThemeManifestInterface;

    /**
     * Determine whether the theme is currently active.
     */
    public function isActive(): bool;

    /**
     * Absolute filesystem path to theme views directory.
     */
    public function getViewPath(): string;

    /**
     * Absolute filesystem path to theme assets directory.
     */
    public function getAssetPath(): string;
}
