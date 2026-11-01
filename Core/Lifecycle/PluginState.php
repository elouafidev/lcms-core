<?php

declare(strict_types=1);

namespace App\Core\Lifecycle;

enum PluginState: string
{
    case AVAILABLE = 'AVAILABLE';
    case INSTALLED = 'INSTALLED';
    case ACTIVE = 'ACTIVE';
    case DISABLED = 'DISABLED';
    case ERROR = 'ERROR';

    /**
     * Determine if a transition to the target state is allowed.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::AVAILABLE => in_array($target, [self::INSTALLED, self::ERROR], true),
            self::INSTALLED => in_array($target, [self::ACTIVE, self::ERROR], true),
            self::ACTIVE => in_array($target, [self::DISABLED, self::ERROR], true),
            self::DISABLED => in_array($target, [self::ACTIVE, self::INSTALLED, self::ERROR], true),
            self::ERROR => in_array($target, [self::DISABLED, self::INSTALLED], true),
        };
    }
}
