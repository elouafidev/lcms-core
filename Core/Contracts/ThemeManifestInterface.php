<?php

declare(strict_types=1);

namespace App\Core\Contracts;

interface ThemeManifestInterface
{
    /**
     * Unique alphanumeric identifier of the theme.
     */
    public function getId(): string;

    /**
     * Human-readable name of the theme.
     */
    public function getName(): string;

    /**
     * Semantic version of the theme.
     */
    public function getVersion(): string;

    /**
     * Brief description of the theme.
     */
    public function getDescription(): string;

    /**
     * Author details (name, email, url).
     *
     * @return array{name: string, email?: string, url?: string}
     */
    public function getAuthor(): array;

    /**
     * Optional parent theme identifier for child themes.
     */
    public function getParentTheme(): ?string;

    /**
     * Declarative assets mapping.
     *
     * @return array<string, string>
     */
    public function getAssets(): array;

    /**
     * Declarative default theme settings.
     *
     * @return array<string, mixed>
     */
    public function getSettings(): array;
}
