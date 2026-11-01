<?php

declare(strict_types=1);

namespace Core\Settings\Services;

use Illuminate\Support\Facades\Artisan;
use Throwable;

class StorageCacheService
{
    /**
     * Get storage and public link diagnostics.
     *
     * @return array{
     *     link_exists: bool,
     *     is_linked: bool,
     *     public_storage_path: string,
     *     target_storage_path: string,
     *     storage_writable: bool,
     *     free_space_human: string
     * }
     */
    public function getStorageStatus(): array
    {
        $publicLink = public_path('storage');
        $targetPath = storage_path('app/public');

        $linkExists = file_exists($publicLink) || is_link($publicLink);
        $isLinked = $linkExists && realpath($publicLink) !== false && realpath($publicLink) === realpath($targetPath);

        $freeBytes = @disk_free_space(storage_path());
        $freeSpace = $freeBytes !== false ? $this->formatBytes((float) $freeBytes) : 'Non disponible';

        return [
            'link_exists' => $linkExists,
            'is_linked' => (bool) $isLinked,
            'public_storage_path' => $publicLink,
            'target_storage_path' => $targetPath,
            'storage_writable' => is_writable(storage_path()),
            'free_space_human' => $freeSpace,
        ];
    }

    /**
     * Create or re-create the storage:link symbolic link.
     *
     * @return array{success: bool, message: string}
     */
    public function linkStorage(bool $force = false): array
    {
        try {
            $publicLink = public_path('storage');
            $targetPath = storage_path('app/public');

            // Ensure target directory exists
            if (! is_dir($targetPath)) {
                @mkdir($targetPath, 0755, true);
            }

            $alreadyLinked = file_exists($publicLink) && realpath($publicLink) !== false && realpath($publicLink) === realpath($targetPath);
            if ($alreadyLinked && ! $force) {
                return [
                    'success' => true,
                    'message' => 'Le lien symbolique de stockage est déjà connecté et opérationnel.',
                ];
            }

            // Remove existing link/directory if force requested or if broken link
            if (file_exists($publicLink) || is_link($publicLink)) {
                if (PHP_OS_FAMILY === 'Windows' && is_dir($publicLink) && ! is_link($publicLink)) {
                    @rmdir($publicLink);
                } else {
                    @unlink($publicLink);
                }
            }

            $exitCode = Artisan::call('storage:link');
            $output = trim(Artisan::output());

            $nowLinked = file_exists($publicLink) && realpath($publicLink) !== false && realpath($publicLink) === realpath($targetPath);

            if ($nowLinked || $exitCode === 0) {
                return [
                    'success' => true,
                    'message' => 'Le lien de stockage public a été généré avec succès.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Impossible d\'activer le lien symbolique : '.($output !== '' ? $output : 'erreur système inconnue'),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Échec lors de la création du lien symbolique : '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get system cache status.
     *
     * @return array{
     *     driver: string,
     *     views_count: int,
     *     is_routes_cached: bool,
     *     is_config_cached: bool,
     *     is_events_cached: bool
     * }
     */
    public function getCacheStatus(): array
    {
        $views = glob(storage_path('framework/views/*.php'));
        $viewsCount = is_array($views) ? count($views) : 0;

        return [
            'driver' => (string) config('cache.default', 'file'),
            'views_count' => $viewsCount,
            'is_routes_cached' => app()->routesAreCached(),
            'is_config_cached' => app()->configurationIsCached(),
            'is_events_cached' => app()->eventsAreCached(),
        ];
    }

    /**
     * Clear application and framework caches.
     *
     * @param  string  $type  all|cache|views|routes|config
     * @return array{success: bool, message: string}
     */
    public function clearCache(string $type = 'all'): array
    {
        try {
            switch ($type) {
                case 'all':
                    Artisan::call('optimize:clear');

                    return [
                        'success' => true,
                        'message' => 'Tous les caches applicatifs, vues compilées, routes et configurations ont été vidés avec succès.',
                    ];

                case 'cache':
                    Artisan::call('cache:clear');

                    return [
                        'success' => true,
                        'message' => 'Le cache applicatif (données et objets) a été vidé avec succès.',
                    ];

                case 'views':
                    Artisan::call('view:clear');

                    return [
                        'success' => true,
                        'message' => 'Le cache des vues Blade compilées a été vidé avec succès.',
                    ];

                case 'routes':
                    Artisan::call('route:clear');

                    return [
                        'success' => true,
                        'message' => 'Le cache des routes a été réinitialisé avec succès.',
                    ];

                case 'config':
                    Artisan::call('config:clear');

                    return [
                        'success' => true,
                        'message' => 'Le cache des fichiers de configuration a été supprimé avec succès.',
                    ];

                default:
                    return [
                        'success' => false,
                        'message' => 'Type de cache non reconnu : '.$type,
                    ];
            }
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors du nettoyage du cache : '.$e->getMessage(),
            ];
        }
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(float $bytes, int $precision = 2): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes > 0 ? log($bytes) : 0) / log(1024));
        $pow = min((int) $pow, count($units) - 1);

        $bytes /= (1024 ** $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
