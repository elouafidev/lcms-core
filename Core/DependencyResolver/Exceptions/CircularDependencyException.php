<?php

declare(strict_types=1);

namespace App\Core\DependencyResolver\Exceptions;

use RuntimeException;

class CircularDependencyException extends RuntimeException
{
    /**
     * @param  list<string>  $cycle
     */
    public static function forCycle(array $cycle): self
    {
        $path = implode(' -> ', $cycle);

        return new self("Circular dependency detected: {$path}");
    }
}
