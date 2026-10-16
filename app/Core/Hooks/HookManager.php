<?php

declare(strict_types=1);

namespace App\Core\Hooks;

use App\Core\Contracts\HookSubscriberInterface;

class HookManager
{
    public const DEFAULT_PRIORITY = 20;

    /**
     * Storage for registered action callbacks.
     * Format: [hook => [priority => [callbacks]]]
     *
     * @var array<string, array<int, list<callable>>>
     */
    private array $actions = [];

    /**
     * Storage for registered filter callbacks.
     * Format: [hook => [priority => [callbacks]]]
     *
     * @var array<string, array<int, list<callable>>>
     */
    private array $filters = [];

    /**
     * Register an action hook callback.
     */
    public function addAction(string $hook, callable $callback, int $priority = self::DEFAULT_PRIORITY): void
    {
        $this->actions[$hook][$priority][] = $callback;
    }

    /**
     * Execute all callbacks attached to an action hook, ordered by priority (ascending).
     */
    public function doAction(string $hook, mixed ...$args): void
    {
        if (! isset($this->actions[$hook])) {
            return;
        }

        $callbacks = $this->getSortedCallbacks($this->actions[$hook]);

        foreach ($callbacks as $callback) {
            $callback(...$args);
        }
    }

    /**
     * Determine if an action hook has any callbacks registered (or a specific callback).
     */
    public function hasAction(string $hook, ?callable $callback = null): bool
    {
        return $this->hasCallback($this->actions, $hook, $callback);
    }

    /**
     * Remove an action callback from an action hook.
     */
    public function removeAction(string $hook, ?callable $callback = null, ?int $priority = null): bool
    {
        [$this->actions, $removed] = $this->removeCallback($this->actions, $hook, $callback, $priority);

        return $removed;
    }

    /**
     * Register a filter hook callback.
     */
    public function addFilter(string $hook, callable $callback, int $priority = self::DEFAULT_PRIORITY): void
    {
        $this->filters[$hook][$priority][] = $callback;
    }

    /**
     * Pass a value through all callbacks attached to a filter hook, ordered by priority (ascending).
     */
    public function applyFilter(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (! isset($this->filters[$hook])) {
            return $value;
        }

        $callbacks = $this->getSortedCallbacks($this->filters[$hook]);

        foreach ($callbacks as $callback) {
            $value = $callback($value, ...$args);
        }

        return $value;
    }

    /**
     * Determine if a filter hook has any callbacks registered (or a specific callback).
     */
    public function hasFilter(string $hook, ?callable $callback = null): bool
    {
        return $this->hasCallback($this->filters, $hook, $callback);
    }

    /**
     * Remove a filter callback from a filter hook.
     */
    public function removeFilter(string $hook, ?callable $callback = null, ?int $priority = null): bool
    {
        [$this->filters, $removed] = $this->removeCallback($this->filters, $hook, $callback, $priority);

        return $removed;
    }

    /**
     * Register all actions and filters declared by a HookSubscriber.
     */
    public function addSubscriber(HookSubscriberInterface $subscriber): void
    {
        $subscriptions = $subscriber->getSubscribedHooks();

        if (isset($subscriptions['actions'])) {
            foreach ($subscriptions['actions'] as $hook => $params) {
                $this->registerSubscriberItem($hook, $subscriber, $params, isFilter: false);
            }
        }

        if (isset($subscriptions['filters'])) {
            foreach ($subscriptions['filters'] as $hook => $params) {
                $this->registerSubscriberItem($hook, $subscriber, $params, isFilter: true);
            }
        }
    }

    /**
     * Remove all callbacks attached to a specific hook (both actions and filters).
     */
    public function removeAll(string $hook): void
    {
        unset($this->actions[$hook], $this->filters[$hook]);
    }

    /**
     * Flush all registered actions and filters.
     */
    public function flush(): void
    {
        $this->actions = [];
        $this->filters = [];
    }

    /**
     * Flatten and sort callback buckets by priority in ascending numerical order.
     *
     * @param  array<int, list<callable>>  $buckets
     * @return list<callable>
     */
    private function getSortedCallbacks(array $buckets): array
    {
        ksort($buckets, SORT_NUMERIC);

        $sorted = [];
        foreach ($buckets as $callbackList) {
            foreach ($callbackList as $callback) {
                $sorted[] = $callback;
            }
        }

        return $sorted;
    }

    /**
     * @param  array<string, array<int, list<callable>>>  $store
     */
    private function hasCallback(array $store, string $hook, ?callable $callback): bool
    {
        if (! isset($store[$hook])) {
            return false;
        }

        if ($callback === null) {
            return ! empty($store[$hook]);
        }

        foreach ($store[$hook] as $callbacks) {
            foreach ($callbacks as $registered) {
                if ($registered === $callback) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, array<int, list<callable>>>  $store
     * @return array{0: array<string, array<int, list<callable>>>, 1: bool}
     */
    private function removeCallback(array $store, string $hook, ?callable $callback, ?int $priority): array
    {
        if (! isset($store[$hook])) {
            return [$store, false];
        }

        if ($callback === null && $priority === null) {
            unset($store[$hook]);

            return [$store, true];
        }

        $removed = false;

        if ($priority !== null) {
            if (! isset($store[$hook][$priority])) {
                return [$store, false];
            }

            if ($callback === null) {
                unset($store[$hook][$priority]);
                if ($store[$hook] === []) {
                    unset($store[$hook]);
                }

                return [$store, true];
            }

            /** @var list<callable> $filtered */
            $filtered = [];
            foreach ($store[$hook][$priority] as $registered) {
                if ($registered === $callback) {
                    $removed = true;
                } else {
                    $filtered[] = $registered;
                }
            }

            if ($filtered === []) {
                unset($store[$hook][$priority]);
                if ($store[$hook] === []) {
                    unset($store[$hook]);
                }
            } else {
                $store[$hook][$priority] = $filtered;
            }

            return [$store, $removed];
        }

        // Remove callback across all priorities
        foreach ($store[$hook] as $pri => $callbacks) {
            /** @var list<callable> $filtered */
            $filtered = [];
            foreach ($callbacks as $registered) {
                if ($registered === $callback) {
                    $removed = true;
                } else {
                    $filtered[] = $registered;
                }
            }

            if ($filtered === []) {
                unset($store[$hook][$pri]);
            } else {
                $store[$hook][$pri] = $filtered;
            }
        }

        if ($store[$hook] === []) {
            unset($store[$hook]);
        }

        return [$store, $removed];
    }

    /**
     * Register a single subscriber hook definition.
     */
    private function registerSubscriberItem(string $hook, HookSubscriberInterface $subscriber, mixed $params, bool $isFilter): void
    {
        if (is_string($params)) {
            $this->addHookCallback($hook, $this->createSubscriberCallback($subscriber, $params), self::DEFAULT_PRIORITY, $isFilter);

            return;
        }

        if (is_array($params)) {
            // Check if it's ['methodName', priority]
            if (isset($params[0]) && is_string($params[0])) {
                $method = $params[0];
                $priority = isset($params[1]) && is_int($params[1]) ? $params[1] : self::DEFAULT_PRIORITY;
                $this->addHookCallback($hook, $this->createSubscriberCallback($subscriber, $method), $priority, $isFilter);

                return;
            }

            // List of multiple definitions: [['method1', 10], ['method2', 20]]
            foreach ($params as $subParam) {
                if (is_string($subParam)) {
                    $this->addHookCallback($hook, $this->createSubscriberCallback($subscriber, $subParam), self::DEFAULT_PRIORITY, $isFilter);
                } elseif (is_array($subParam) && isset($subParam[0]) && is_string($subParam[0])) {
                    $method = $subParam[0];
                    $priority = isset($subParam[1]) && is_int($subParam[1]) ? $subParam[1] : self::DEFAULT_PRIORITY;
                    $this->addHookCallback($hook, $this->createSubscriberCallback($subscriber, $method), $priority, $isFilter);
                }
            }
        }
    }

    private function createSubscriberCallback(HookSubscriberInterface $subscriber, string $method): \Closure
    {
        return fn (mixed ...$args): mixed => $subscriber->{$method}(...$args);
    }

    private function addHookCallback(string $hook, callable $callback, int $priority, bool $isFilter): void
    {
        if ($isFilter) {
            $this->addFilter($hook, $callback, $priority);
        } else {
            $this->addAction($hook, $callback, $priority);
        }
    }
}
