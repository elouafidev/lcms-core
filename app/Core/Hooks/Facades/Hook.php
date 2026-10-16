<?php

declare(strict_types=1);

namespace App\Core\Hooks\Facades;

use App\Core\Contracts\HookSubscriberInterface;
use App\Core\Hooks\HookManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void addAction(string $hook, callable $callback, int $priority = 20)
 * @method static void doAction(string $hook, mixed ...$args)
 * @method static bool hasAction(string $hook, ?callable $callback = null)
 * @method static bool removeAction(string $hook, ?callable $callback = null, ?int $priority = null)
 * @method static void addFilter(string $hook, callable $callback, int $priority = 20)
 * @method static mixed applyFilter(string $hook, mixed $value, mixed ...$args)
 * @method static bool hasFilter(string $hook, ?callable $callback = null)
 * @method static bool removeFilter(string $hook, ?callable $callback = null, ?int $priority = null)
 * @method static void addSubscriber(HookSubscriberInterface $subscriber)
 * @method static void removeAll(string $hook)
 * @method static void flush()
 *
 * @see HookManager
 */
class Hook extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return HookManager::class;
    }
}
