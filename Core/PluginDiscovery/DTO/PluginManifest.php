<?php

declare(strict_types=1);

namespace App\Core\PluginDiscovery\DTO;

use App\Core\Contracts\PluginManifestInterface;

readonly class PluginManifest implements PluginManifestInterface
{
    /**
     * @param  array<string, string>  $author
     * @param  array<string, string>  $compatibility
     * @param  list<string>  $providers
     * @param  array<string, string>  $dependencies
     * @param  list<string>  $conflicts
     * @param  list<array{key: string, label: string}>  $permissions
     * @param  array{web?: ?string, api?: ?string}  $routes
     * @param array{psr-4?: array<string, string>} $autoload
     * @param  array<string, mixed>  $rawAttributes
     */
    public function __construct(
        private string $id,
        private string $name,
        private string $version,
        private string $description = '',
        private array $author = [],
        private array $compatibility = [],
        private array $providers = [],
        private array $dependencies = [],
        private array $conflicts = [],
        private array $permissions = [],
        private array $routes = [],
        private array $autoload = [],
        private array $rawAttributes = []
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array<string, string>
     */
    public function getAuthor(): array
    {
        return $this->author;
    }

    /**
     * @return array<string, string>
     */
    public function getCompatibility(): array
    {
        return $this->compatibility;
    }

    /**
     * @return list<string>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * @return array<string, string>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @return list<string>
     */
    public function getConflicts(): array
    {
        return $this->conflicts;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @return array{web?: ?string, api?: ?string}
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @return array{psr-4?: array<string, string>}
     */
    public function getAutoload(): array
    {
        return $this->autoload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge($this->rawAttributes, [
            'id' => $this->id,
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'author' => $this->author,
            'compatibility' => $this->compatibility,
            'providers' => $this->providers,
            'dependencies' => $this->dependencies,
            'conflicts' => $this->conflicts,
            'permissions' => $this->permissions,
            'routes' => $this->routes,
            'autoload' => $this->autoload,
        ]);
    }
}
