<?php

declare(strict_types=1);

namespace App\Core\Themes;

use App\Core\Contracts\ThemeInterface;
use App\Core\Themes\DTO\DiscoveredTheme;
use App\Core\Themes\DTO\ThemeManifest;
use DirectoryIterator;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;

class ThemeDiscovery
{
    /**
     * Cache of discovered themes keyed by ID.
     *
     * @var array<string, ThemeInterface>
     */
    private array $discovered = [];

    public function __construct(
        private ?string $themesPath = null,
        private ?LoggerInterface $logger = null
    ) {
        $this->themesPath = $themesPath ?? (string) config('cms.themes.path', base_path('platform/themes'));
    }

    /**
     * Scan the themes directory and discover all valid themes.
     *
     * @return array<string, ThemeInterface>
     */
    public function discover(): array
    {
        $this->discovered = [];

        if ($this->themesPath === null || ! is_dir($this->themesPath)) {
            return [];
        }

        $iterator = new DirectoryIterator($this->themesPath);

        foreach ($iterator as $item) {
            if ($item->isDot() || ! $item->isDir()) {
                continue;
            }

            $themeDirName = $item->getFilename();

            // Anti-traversal check
            if (str_contains($themeDirName, '..') || str_contains($themeDirName, '/') || str_contains($themeDirName, '\\')) {
                $this->logger?->warning("Skipping invalid theme directory name: {$themeDirName}");

                continue;
            }

            $manifestFile = $item->getPathname().DIRECTORY_SEPARATOR.'theme.json';

            if (! file_exists($manifestFile) || ! is_readable($manifestFile)) {
                continue;
            }

            try {
                $content = file_get_contents($manifestFile);
                if ($content === false) {
                    continue;
                }

                /** @var array<string, mixed> $data */
                $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

                $manifest = ThemeManifest::fromArray($data);

                $theme = new DiscoveredTheme(
                    id: $manifest->getId(),
                    name: $manifest->getName(),
                    path: $item->getPathname(),
                    manifest: $manifest,
                    active: false
                );

                $this->discovered[$manifest->getId()] = $theme;
            } catch (JsonException|InvalidArgumentException $e) {
                $this->logger?->warning("Failed to parse theme in {$themeDirName}: {$e->getMessage()}");
            }
        }

        return $this->discovered;
    }

    /**
     * Get all discovered themes.
     *
     * @return array<string, ThemeInterface>
     */
    public function all(): array
    {
        if ($this->discovered === []) {
            $this->discover();
        }

        return $this->discovered;
    }

    /**
     * Find a discovered theme by ID.
     */
    public function get(string $id): ?ThemeInterface
    {
        return $this->all()[$id] ?? null;
    }

    /**
     * Check if a theme ID exists among discovered themes.
     */
    public function has(string $id): bool
    {
        return isset($this->all()[$id]);
    }

    /**
     * Set a custom themes path for runtime or testing.
     */
    public function setThemesPath(string $path): void
    {
        $this->themesPath = $path;
        $this->discovered = [];
    }

    /**
     * Get the active themes directory path.
     */
    public function getThemesPath(): ?string
    {
        return $this->themesPath;
    }
}
