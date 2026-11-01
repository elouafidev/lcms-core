<?php

declare(strict_types=1);

namespace App\Core\DependencyResolver;

use App\Core\Contracts\PluginInterface;
use App\Core\DependencyResolver\Exceptions\CircularDependencyException;
use App\Core\DependencyResolver\Exceptions\MissingDependencyException;

class DependencyResolver
{
    /**
     * Resolve and return plugins ordered topologically according to their dependencies (Kahn's Algorithm).
     *
     * @param  iterable<PluginInterface>  $plugins
     * @param  bool  $checkMissing  Whether to throw when a declared dependency is missing from the candidate set
     * @return list<PluginInterface>
     *
     * @throws CircularDependencyException
     * @throws MissingDependencyException
     */
    public function resolve(iterable $plugins, bool $checkMissing = true): array
    {
        /** @var array<string, PluginInterface> $pluginsMap */
        $pluginsMap = [];
        foreach ($plugins as $plugin) {
            $pluginsMap[$plugin->getManifest()->getId()] = $plugin;
        }

        if (empty($pluginsMap)) {
            return [];
        }

        /** @var array<string, int> $inDegree */
        $inDegree = [];
        /** @var array<string, list<string>> $adj */
        $adj = [];

        foreach ($pluginsMap as $id => $plugin) {
            $inDegree[$id] = 0;
            $adj[$id] = [];
        }

        // Build Graph: If plugin A depends on B, B must be loaded BEFORE A.
        // Therefore, the directed edge is B -> A (B unlocks A).
        foreach ($pluginsMap as $id => $plugin) {
            $dependencies = $plugin->getManifest()->getDependencies();

            foreach ($dependencies as $depId => $constraint) {
                if (! isset($pluginsMap[$depId])) {
                    if ($checkMissing) {
                        throw MissingDependencyException::forPlugin($id, $depId, (string) $constraint);
                    }

                    continue;
                }

                // Directed edge: $depId -> $id
                $adj[$depId][] = $id;
                $inDegree[$id]++;
            }
        }

        // Kahn's algorithm: Initialize queue with nodes having in-degree 0 (no dependencies)
        /** @var list<string> $queue */
        $queue = [];
        foreach ($inDegree as $id => $degree) {
            if ($degree === 0) {
                $queue[] = $id;
            }
        }

        // Deterministic alphabetical sorting for tie-breaking
        sort($queue, SORT_STRING);

        /** @var list<PluginInterface> $resolved */
        $resolved = [];
        $visitedCount = 0;

        while (! empty($queue)) {
            $currentId = array_shift($queue);
            $resolved[] = $pluginsMap[$currentId];
            $visitedCount++;

            /** @var list<string> $newlyAvailable */
            $newlyAvailable = [];

            foreach ($adj[$currentId] as $dependentId) {
                $inDegree[$dependentId]--;
                if ($inDegree[$dependentId] === 0) {
                    $newlyAvailable[] = $dependentId;
                }
            }

            if (! empty($newlyAvailable)) {
                sort($newlyAvailable, SORT_STRING);
                foreach ($newlyAvailable as $item) {
                    $queue[] = $item;
                }
            }
        }

        if ($visitedCount !== count($pluginsMap)) {
            $this->detectAndThrowCycle($pluginsMap, $inDegree);
        }

        return $resolved;
    }

    /**
     * Trace cycle path among nodes with remaining in-degree > 0 and throw CircularDependencyException.
     *
     * @param  array<string, PluginInterface>  $pluginsMap
     * @param  array<string, int>  $inDegree
     */
    private function detectAndThrowCycle(array $pluginsMap, array $inDegree): never
    {
        // Remaining nodes with in-degree > 0 belong to or depend on cycles
        $remaining = [];
        foreach ($inDegree as $id => $degree) {
            if ($degree > 0) {
                $remaining[$id] = true;
            }
        }

        // Reconstruct dependency edges: A depends on B (A -> B in dependency sense)
        /** @var array<string, list<string>> $dependsOn */
        $dependsOn = [];
        foreach ($remaining as $id => $_) {
            $deps = $pluginsMap[$id]->getManifest()->getDependencies();
            $dependsOn[$id] = [];
            foreach (array_keys($deps) as $depId) {
                if (isset($remaining[$depId])) {
                    $dependsOn[$id][] = $depId;
                }
            }
        }

        // DFS to find cycle path
        $visited = [];
        $recursionStack = [];
        $cyclePath = [];

        foreach (array_keys($remaining) as $startNode) {
            $path = [];
            if ($this->findCycleDfs($startNode, $dependsOn, $visited, $recursionStack, $path)) {
                $cyclePath = $path;
                break;
            }
        }

        if (empty($cyclePath)) {
            $cyclePath = array_keys($remaining);
            $cyclePath[] = $cyclePath[0];
        }

        throw CircularDependencyException::forCycle($cyclePath);
    }

    /**
     * @param  array<string, list<string>>  $graph
     * @param  array<string, bool>  $visited
     * @param  array<string, bool>  $stack
     * @param  list<string>  $path
     */
    private function findCycleDfs(
        string $node,
        array $graph,
        array &$visited,
        array &$stack,
        array &$path
    ): bool {
        $visited[$node] = true;
        $stack[$node] = true;
        $path[] = $node;

        foreach ($graph[$node] ?? [] as $neighbor) {
            if (! isset($visited[$neighbor])) {
                if ($this->findCycleDfs($neighbor, $graph, $visited, $stack, $path)) {
                    return true;
                }
            } elseif (! empty($stack[$neighbor])) {
                $path[] = $neighbor;
                // Trim path to start from the repeated neighbor
                $startIndex = array_search($neighbor, $path, true);
                if ($startIndex !== false) {
                    $path = array_slice($path, (int) $startIndex);
                }

                return true;
            }
        }

        $stack[$node] = false;
        array_pop($path);

        return false;
    }
}
