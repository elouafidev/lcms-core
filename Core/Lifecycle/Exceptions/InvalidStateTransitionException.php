<?php

declare(strict_types=1);

namespace App\Core\Lifecycle\Exceptions;

use RuntimeException;

class InvalidStateTransitionException extends RuntimeException
{
    public static function forbidden(string $pluginId, string $currentState, string $targetState): self
    {
        return new self("Plugin '{$pluginId}' cannot transition from state '{$currentState}' to '{$targetState}'.");
    }
}
