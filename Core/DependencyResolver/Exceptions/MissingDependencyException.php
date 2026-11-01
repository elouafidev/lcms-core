<?php

declare(strict_types=1);

namespace App\Core\DependencyResolver\Exceptions;

use RuntimeException;

class MissingDependencyException extends RuntimeException
{
    public static function forPlugin(string $pluginId, string $dependencyId, string $constraint = '*'): self
    {
        return new self(
            "Plugin '{$pluginId}' requires missing dependency '{$dependencyId}' (constraint: '{$constraint}')."
        );
    }
}
