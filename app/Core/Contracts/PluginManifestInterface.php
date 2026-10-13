<?php

declare(strict_types=1);

namespace App\Core\Contracts;

interface PluginManifestInterface
{
    /**
     * Get unique identifier (e.g. "acme.demo-hello").
     */
    public function getId(): string;

    /**
     * Get human-readable name.
     */
    public function getName(): string;

    /**
     * Get semantic version.
     */
    public function getVersion(): string;

    /**
     * Get plugin description.
     */
    public function getDescription(): string;

    /**
     * Get author information.
     *
     * @return array<string, string>
     */
    public function getAuthor(): array;

    /**
     * Get compatibility constraints.
     *
     * @return array<string, string>
     */
    public function getCompatibility(): array;

    /**
     * Get list of ServiceProvider class names.
     *
     * @return list<string>
     */
    public function getProviders(): array;

    /**
     * Get plugin dependencies mapped to version constraints.
     *
     * @return array<string, string>
     */
    public function getDependencies(): array;

    /**
     * Get list of conflicting plugin identifiers.
     *
     * @return list<string>
     */
    public function getConflicts(): array;

    /**
     * Get permissions declared by this plugin.
     *
     * @return list<array{key: string, label: string}>
     */
    public function getPermissions(): array;

    /**
     * Get declared route file mappings.
     *
     * @return array{web?: ?string, api?: ?string}
     */
    public function getRoutes(): array;

    /**
     * Get PSR-4 autoload mappings.
     *
     * @return array{psr-4?: array<string, string>}
     */
    public function getAutoload(): array;

    /**
     * Export manifest data as an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
