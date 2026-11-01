<?php

declare(strict_types=1);

namespace App\Core\PluginDiscovery\Exceptions;

use RuntimeException;

class InvalidManifestException extends RuntimeException
{
    public static function malformedJson(string $error): self
    {
        return new self("Invalid plugin manifest: JSON syntax error ({$error}).");
    }

    public static function missingRequiredField(string $field): self
    {
        return new self("Invalid plugin manifest: Required field '{$field}' is missing.");
    }

    public static function invalidIdentifier(string $id): self
    {
        return new self("Invalid plugin manifest: Identifier '{$id}' is invalid. Must match '^[a-z0-9_-]+\\.[a-z0-9_-]+$'.");
    }

    public static function invalidVersion(string $version): self
    {
        return new self("Invalid plugin manifest: Version '{$version}' does not comply with SemVer 2.0.0 (MAJOR.MINOR.PATCH).");
    }

    public static function pathTraversalDetected(string $path): self
    {
        return new self("Invalid plugin manifest: Directory traversal detected in path '{$path}'. Relative paths must not contain '..'.");
    }

    public static function absolutePathNotAllowed(string $path): self
    {
        return new self("Invalid plugin manifest: Absolute path '{$path}' is not allowed. Paths must be relative to the plugin root.");
    }

    public static function invalidFieldType(string $field, string $expectedType): self
    {
        return new self("Invalid plugin manifest: Field '{$field}' must be of type '{$expectedType}'.");
    }
}
