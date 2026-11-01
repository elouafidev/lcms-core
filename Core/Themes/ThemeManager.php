<?php

declare(strict_types=1);

namespace App\Core\Themes;

use App\Core\Contracts\ThemeInterface;
use App\Core\Themes\DTO\DiscoveredTheme;
use App\Core\Themes\Models\ThemeRecord;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\View\FileViewFinder;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class ThemeManager
{
    private ?ThemeInterface $activeTheme = null;

    /**
     * @var list<string>
     */
    private array $appliedThemePaths = [];

    public function __construct(
        private readonly ThemeDiscovery $discovery,
        private readonly ViewFactory $viewFactory,
        private readonly ThemeAssetManager $assetManager,
        private readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * Boot the active theme from database on application startup.
     */
    public function boot(): void
    {
        try {
            if (! Schema::hasTable('cms_themes')) {
                return;
            }

            /** @var ThemeRecord|null $record */
            $record = ThemeRecord::query()->where('is_active', true)->first();

            if ($record === null) {
                return;
            }

            // Ensure discovery has scanned disk
            $this->discovery->discover();

            $theme = $this->discovery->get($record->theme_id);

            if ($theme === null) {
                $this->logger?->warning("Active theme '{$record->theme_id}' from database not found on filesystem.");

                return;
            }

            if ($theme instanceof DiscoveredTheme) {
                $theme->setActive(true);
            }

            $this->activeTheme = $theme;
            $this->applyThemeViews($theme);
        } catch (Throwable $e) {
            $this->logger?->warning('ThemeManager boot skipped: Database not ready or unreachable ('.$e->getMessage().').');
        }
    }

    /**
     * Determine whether an active theme is currently loaded.
     */
    public function hasActiveTheme(): bool
    {
        return $this->activeTheme !== null;
    }

    /**
     * Get the active theme instance.
     */
    public function getActiveTheme(): ?ThemeInterface
    {
        return $this->activeTheme;
    }

    /**
     * Get the ID of the currently active theme.
     */
    public function getActiveThemeId(): ?string
    {
        return $this->activeTheme?->getId();
    }

    /**
     * Activate a theme by ID. Only ONE theme can be active at a time.
     */
    public function activate(string $themeId, bool $cleanPreviousAssets = false): ThemeInterface
    {
        // Re-discover to make sure newly created themes are recognized
        $this->discovery->discover();

        $theme = $this->discovery->get($themeId);

        if ($theme === null) {
            throw new InvalidArgumentException("Theme '{$themeId}' not found on disk.");
        }

        // Deactivate previous active theme if any
        if ($this->activeTheme !== null) {
            if ($cleanPreviousAssets) {
                $this->assetManager->clean($this->activeTheme->getId());
            }

            $this->removeThemeViews($this->activeTheme);
            if ($this->activeTheme instanceof DiscoveredTheme) {
                $this->activeTheme->setActive(false);
            }
        }

        // Update database: ensure only this theme is marked active
        ThemeRecord::query()->where('is_active', true)->update(['is_active' => false]);

        /** @var ThemeRecord $record */
        $record = ThemeRecord::firstOrNew(['theme_id' => $themeId]);
        $record->name = $theme->getName();
        $record->is_active = true;
        if ($record->settings === null) {
            $record->settings = $theme->getManifest()->getSettings();
        }
        $record->save();

        if ($theme instanceof DiscoveredTheme) {
            $theme->setActive(true);
        }

        $this->activeTheme = $theme;
        $this->applyThemeViews($theme);
        $this->assetManager->publish($theme->getId(), $theme->getPath(), force: true);

        return $theme;
    }

    /**
     * Deactivate the active theme.
     */
    public function deactivate(): void
    {
        if ($this->activeTheme === null) {
            return;
        }

        ThemeRecord::query()->where('is_active', true)->update(['is_active' => false]);

        $this->removeThemeViews($this->activeTheme);

        if ($this->activeTheme instanceof DiscoveredTheme) {
            $this->activeTheme->setActive(false);
        }

        $this->activeTheme = null;
    }

    /**
     * Apply view paths and plugin override hierarchy for a theme.
     */
    public function applyThemeViews(ThemeInterface $theme): void
    {
        // If parent theme is specified, apply parent first so child overrides parent
        $parentThemeId = $theme->getManifest()->getParentTheme();
        if ($parentThemeId !== null) {
            $parent = $this->discovery->get($parentThemeId);
            if ($parent !== null) {
                $this->applySingleThemeViews($parent);
            }
        }

        $this->applySingleThemeViews($theme);
    }

    /**
     * Apply view paths for a single theme instance.
     */
    private function applySingleThemeViews(ThemeInterface $theme): void
    {
        $finder = $this->viewFactory->getFinder();
        $themeRoot = $theme->getPath();
        $this->appliedThemePaths[] = $themeRoot;

        $viewPaths = $this->resolveViewDirectories($theme);

        foreach ($viewPaths as $viewPath) {
            if ($finder instanceof FileViewFinder) {
                $finder->prependLocation($viewPath);
            } else {
                $finder->addLocation($viewPath);
            }

            // Expose theme namespace directly (e.g. view('theme::layout'))
            $finder->prependNamespace('theme', $viewPath);

            // Register plugin overrides in views/plugins/{plugin}
            $pluginsOverrideDir = $viewPath.'/plugins';
            if (is_dir($pluginsOverrideDir)) {
                $subDirs = glob($pluginsOverrideDir.'/*', GLOB_ONLYDIR) ?: [];
                foreach ($subDirs as $pluginDir) {
                    $namespace = basename($pluginDir);
                    $finder->prependNamespace($namespace, $pluginDir);
                }
            }

            // Also check for vendor/ directory overrides
            $vendorOverrideDir = $viewPath.'/vendor';
            if (is_dir($vendorOverrideDir)) {
                $subDirs = glob($vendorOverrideDir.'/*', GLOB_ONLYDIR) ?: [];
                foreach ($subDirs as $vendorDir) {
                    $namespace = basename($vendorDir);
                    $finder->prependNamespace($namespace, $vendorDir);
                }
            }
        }

        $finder->flush();
    }

    /**
     * Remove view paths and plugin overrides for a theme.
     */
    public function removeThemeViews(ThemeInterface $theme): void
    {
        $finder = $this->viewFactory->getFinder();
        $themeRoot = $theme->getPath();

        // Also remove parent theme paths if applicable
        $parentThemeId = $theme->getManifest()->getParentTheme();
        $pathsToRemove = [$themeRoot];
        if ($parentThemeId !== null) {
            $parent = $this->discovery->get($parentThemeId);
            if ($parent !== null) {
                $pathsToRemove[] = $parent->getPath();
            }
        }

        if ($finder instanceof FileViewFinder) {
            // Filter locations
            $currentLocations = $finder->getPaths();
            $filteredLocations = array_values(array_filter($currentLocations, function (string $path) use ($pathsToRemove): bool {
                $normalizedPath = str_replace('\\', '/', $path);
                foreach ($pathsToRemove as $prefix) {
                    $normalizedPrefix = str_replace('\\', '/', $prefix);
                    if (str_starts_with($normalizedPath, $normalizedPrefix)) {
                        return false;
                    }
                }

                return true;
            }));
            $finder->setPaths($filteredLocations);

            // Filter hints
            /** @var array<string, list<string>> $hints */
            $hints = $finder->getHints();
            foreach ($hints as $namespace => $paths) {
                $filteredPaths = array_values(array_filter($paths, function (string $path) use ($pathsToRemove): bool {
                    $normalizedPath = str_replace('\\', '/', $path);
                    foreach ($pathsToRemove as $prefix) {
                        $normalizedPrefix = str_replace('\\', '/', $prefix);
                        if (str_starts_with($normalizedPath, $normalizedPrefix)) {
                            return false;
                        }
                    }

                    return true;
                }));

                $finder->replaceNamespace($namespace, $filteredPaths);
            }
        }

        $this->appliedThemePaths = array_values(array_filter(
            $this->appliedThemePaths,
            fn (string $p): bool => ! in_array($p, $pathsToRemove, true)
        ));

        $finder->flush();
    }

    /**
     * Resolve existing view directories for a theme.
     *
     * @return list<string>
     */
    public function resolveViewDirectories(ThemeInterface $theme): array
    {
        $paths = [];
        $candidates = [
            $theme->getViewPath(),
            $theme->getPath().'/views',
            $theme->getPath().'/resources/views',
        ];

        foreach ($candidates as $candidate) {
            if (is_dir($candidate) && ! in_array($candidate, $paths, true)) {
                $paths[] = $candidate;
            }
        }

        if (empty($paths)) {
            $paths[] = $theme->getViewPath();
        }

        return $paths;
    }

    /**
     * Get settings for a theme.
     *
     * @return array<string, mixed>
     */
    public function getSettings(string $themeId): array
    {
        /** @var ThemeRecord|null $record */
        $record = ThemeRecord::query()->where('theme_id', $themeId)->first();
        if ($record !== null && $record->settings !== null) {
            return $record->settings;
        }

        $theme = $this->discovery->get($themeId);

        return $theme !== null ? $theme->getManifest()->getSettings() : [];
    }

    /**
     * Update settings for a theme.
     *
     * @param  array<string, mixed>  $settings
     */
    public function updateSettings(string $themeId, array $settings): ThemeRecord
    {
        /** @var ThemeRecord|null $record */
        $record = ThemeRecord::query()->where('theme_id', $themeId)->first();
        if ($record === null) {
            throw new InvalidArgumentException("Theme record '{$themeId}' not found.");
        }

        $record->settings = array_merge($record->settings ?? [], $settings);
        $record->save();

        return $record;
    }

    public function getAssetManager(): ThemeAssetManager
    {
        return $this->assetManager;
    }

    public function getDiscovery(): ThemeDiscovery
    {
        return $this->discovery;
    }

    /**
     * Delete an inactive theme completely from the filesystem.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function delete(string $themeId): void
    {
        if ($this->getActiveThemeId() === $themeId) {
            throw new InvalidArgumentException("Impossible de supprimer le thème '{$themeId}' car il est actuellement actif. Veuillez activer un autre thème ou le désactiver au préalable.");
        }

        /** @var ThemeRecord|null $record */
        $record = ThemeRecord::query()->where('theme_id', $themeId)->first();
        if ($record !== null && $record->is_active) {
            throw new InvalidArgumentException("Impossible de supprimer le thème '{$themeId}' car il est enregistré comme actif dans la base de données.");
        }

        $theme = $this->discovery->get($themeId);
        $path = $theme?->getPath();

        if ($path === null) {
            $themesBasePath = (string) config('cms.themes.path', base_path('platform/themes'));
            $candidatePath = $themesBasePath.DIRECTORY_SEPARATOR.$themeId;
            if (is_dir($candidatePath)) {
                $path = $candidatePath;
            }
        }

        if ($path === null || ! is_dir($path)) {
            throw new RuntimeException("Dossier introuvable sur le disque pour le thème '{$themeId}'.");
        }

        $themesBasePath = realpath((string) config('cms.themes.path', base_path('platform/themes')));
        $realPath = realpath($path);

        if ($realPath === false || $themesBasePath === false || ! str_starts_with($realPath, $themesBasePath.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException("Chemin non autorisé ou en dehors du répertoire des thèmes pour '{$themeId}'.");
        }

        $this->deleteDirectoryRecursively($realPath);
        $this->assetManager->clean($themeId);

        if ($record !== null) {
            $record->delete();
        }

        $this->discovery->discover();

        $this->logger?->info("Thème '{$themeId}' supprimé définitivement du système de fichiers.");
    }

    private function deleteDirectoryRecursively(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            if (is_dir($path)) {
                $this->deleteDirectoryRecursively($path);
            } else {
                @chmod($path, 0777);
                unlink($path);
            }
        }

        @chmod($dir, 0777);
        rmdir($dir);
    }
}
