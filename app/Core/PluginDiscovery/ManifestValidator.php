<?php

declare(strict_types=1);

namespace App\Core\PluginDiscovery;

use App\Core\PluginDiscovery\DTO\PluginManifest;
use App\Core\PluginDiscovery\Exceptions\InvalidManifestException;

class ManifestValidator
{
    private const ID_REGEX = '/^[a-z0-9_-]+\.[a-z0-9_-]+$/';

    private const SEMVER_REGEX = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    /**
     * Validate a manifest JSON file from disk.
     */
    public function validateFile(string $filePath): PluginManifest
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new InvalidManifestException("Cannot read manifest file at '{$filePath}'.");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new InvalidManifestException("Failed to read manifest file contents at '{$filePath}'.");
        }

        return $this->validate($content);
    }

    /**
     * Validate a raw JSON string or pre-parsed array.
     *
     * @param  string|array<string, mixed>  $data
     */
    public function validate(string|array $data): PluginManifest
    {
        if (is_string($data)) {
            /** @var array<string, mixed>|null $parsed */
            $parsed = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($parsed)) {
                throw InvalidManifestException::malformedJson(json_last_error_msg());
            }
            $data = $parsed;
        }

        $this->assertRequiredFields($data);

        $id = (string) $data['id'];
        $name = (string) $data['name'];
        $version = (string) $data['version'];

        $this->assertIdentifier($id);
        $this->assertVersion($version);

        $description = isset($data['description']) && is_string($data['description']) ? $data['description'] : '';

        /** @var array<string, string> $author */
        $author = isset($data['author']) && is_array($data['author']) ? $data['author'] : [];

        /** @var array<string, string> $compatibility */
        $compatibility = isset($data['compatibility']) && is_array($data['compatibility']) ? $data['compatibility'] : [];

        /** @var list<string> $providers */
        $providers = isset($data['providers']) && is_array($data['providers']) ? array_values(array_filter($data['providers'], 'is_string')) : [];

        /** @var array<string, string> $dependencies */
        $dependencies = isset($data['dependencies']) && is_array($data['dependencies']) ? $data['dependencies'] : [];

        /** @var list<string> $conflicts */
        $conflicts = isset($data['conflicts']) && is_array($data['conflicts']) ? array_values(array_filter($data['conflicts'], 'is_string')) : [];

        /** @var list<array{key: string, label: string}> $permissions */
        $permissions = isset($data['permissions']) && is_array($data['permissions']) ? array_values($data['permissions']) : [];

        /** @var array{web?: ?string, api?: ?string} $routes */
        $routes = isset($data['routes']) && is_array($data['routes']) ? $data['routes'] : [];

        /** @var array{psr-4?: array<string, string>} $autoload */
        $autoload = isset($data['autoload']) && is_array($data['autoload']) ? $data['autoload'] : [];

        $this->assertSecurityPathIntegrity($routes, $autoload);

        return new PluginManifest(
            id: $id,
            name: $name,
            version: $version,
            description: $description,
            author: $author,
            compatibility: $compatibility,
            providers: $providers,
            dependencies: $dependencies,
            conflicts: $conflicts,
            permissions: $permissions,
            routes: $routes,
            autoload: $autoload,
            rawAttributes: $data
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertRequiredFields(array $data): void
    {
        $required = ['id', 'name', 'version'];
        foreach ($required as $field) {
            if (! array_key_exists($field, $data) || ! is_string($data[$field]) || trim($data[$field]) === '') {
                throw InvalidManifestException::missingRequiredField($field);
            }
        }
    }

    private function assertIdentifier(string $id): void
    {
        if (! preg_match(self::ID_REGEX, $id)) {
            throw InvalidManifestException::invalidIdentifier($id);
        }
    }

    private function assertVersion(string $version): void
    {
        if (! preg_match(self::SEMVER_REGEX, $version)) {
            throw InvalidManifestException::invalidVersion($version);
        }
    }

    /**
     * Ensure all declared file and directory paths do not attempt directory traversal or absolute path escape.
     *
     * @param  array{web?: ?string, api?: ?string}  $routes
     * @param array{psr-4?: array<string, string>} $autoload
     */
    private function assertSecurityPathIntegrity(array $routes, array $autoload): void
    {
        $pathsToCheck = [];

        foreach ($routes as $routePath) {
            if (is_string($routePath) && trim($routePath) !== '') {
                $pathsToCheck[] = $routePath;
            }
        }

        if (isset($autoload['psr-4'])) {
            foreach ($autoload['psr-4'] as $dirPath) {
                if (trim($dirPath) !== '') {
                    $pathsToCheck[] = $dirPath;
                }
            }
        }

        foreach ($pathsToCheck as $path) {
            $this->validateSafePath($path);
        }
    }

    private function validateSafePath(string $path): void
    {
        $normalized = str_replace('\\', '/', $path);

        // Check for directory traversal sequences
        if (str_contains($normalized, '..')) {
            throw InvalidManifestException::pathTraversalDetected($path);
        }

        // Check for absolute path (UNIX style /path or Windows style C:/path or //server)
        if (str_starts_with($normalized, '/') || preg_match('/^[a-zA-Z]:\//', $normalized)) {
            throw InvalidManifestException::absolutePathNotAllowed($path);
        }
    }
}
