<?php

declare(strict_types=1);

namespace App\Core\PluginDiscovery;

use App\Core\PluginDiscovery\DTO\PluginManifest;
use App\Core\PluginDiscovery\Exceptions\InvalidPluginArchiveException;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use RuntimeException;
use ZipArchive;

class PluginZipInstaller
{
    /**
     * List of dangerous or binary file extensions strictly forbidden in uploaded plugins.
     *
     * @var list<string>
     */
    private const FORBIDDEN_EXTENSIONS = [
        'exe', 'sh', 'bat', 'cmd', 'phar', 'phtml', 'bin', 'msi', 'dll', 'so', 'com', 'vbs',
    ];

    public function __construct(
        private ManifestValidator $validator,
        private PluginRegistry $registry,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Install a plugin from an uploaded ZIP archive or filesystem path.
     *
     * @throws InvalidPluginArchiveException
     */
    public function installArchive(UploadedFile|string $archive, bool $overwrite = false): PluginManifest
    {
        $archivePath = $archive instanceof UploadedFile ? $archive->getRealPath() : $archive;
        if (! is_string($archivePath) || ! file_exists($archivePath) || ! is_readable($archivePath)) {
            throw InvalidPluginArchiveException::unreadableArchive();
        }

        $zip = new ZipArchive;
        $openResult = $zip->open($archivePath);
        if ($openResult !== true) {
            throw InvalidPluginArchiveException::openFailed($openResult);
        }

        $sandboxDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lcms_zip_'.Str::uuid()->toString();
        if (! mkdir($sandboxDir, 0777, true) && ! is_dir($sandboxDir)) {
            $zip->close();
            throw new RuntimeException("Impossible de créer le répertoire temporaire d'extraction.");
        }

        try {
            $entryCount = $zip->numFiles;
            if ($entryCount === 0) {
                throw new InvalidPluginArchiveException("L'archive ZIP est vide.");
            }

            for ($i = 0; $i < $entryCount; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat === false) {
                    continue;
                }

                $entryName = $stat['name'];

                // Protection against Zip Slip directory traversal
                if (
                    str_contains($entryName, '..') ||
                    str_starts_with($entryName, '/') ||
                    str_starts_with($entryName, '\\') ||
                    preg_match('/^[a-zA-Z]:/', $entryName) === 1
                ) {
                    throw InvalidPluginArchiveException::zipSlipDetected($entryName);
                }

                // Protection against forbidden executable extensions
                $ext = strtolower(pathinfo($entryName, PATHINFO_EXTENSION));
                if (in_array($ext, self::FORBIDDEN_EXTENSIONS, true)) {
                    throw InvalidPluginArchiveException::forbiddenExtension($entryName);
                }
            }

            $extractSuccess = $zip->extractTo($sandboxDir);
            $zip->close();

            if (! $extractSuccess) {
                throw new InvalidPluginArchiveException("Échec lors de l'extraction de l'archive ZIP.");
            }

            $pluginRoot = $this->locatePluginRoot($sandboxDir);
            if ($pluginRoot === null) {
                throw InvalidPluginArchiveException::missingManifest();
            }

            $manifestPath = $pluginRoot.DIRECTORY_SEPARATOR.'plugin.json';
            $manifest = $this->validator->validateFile($manifestPath);
            $pluginId = $manifest->getId();

            $pluginsBasePath = (string) config('cms.plugins.path', base_path('platform/plugins'));
            $targetDir = $pluginsBasePath.DIRECTORY_SEPARATOR.$pluginId;

            if (is_dir($targetDir) && ! $overwrite) {
                throw InvalidPluginArchiveException::alreadyExists($pluginId);
            }

            if ($this->registry->isInstalled($pluginId) && ! $overwrite) {
                throw InvalidPluginArchiveException::alreadyExists($pluginId);
            }

            if (! is_dir($pluginsBasePath)) {
                mkdir($pluginsBasePath, 0777, true);
            }

            if (is_dir($targetDir) && $overwrite) {
                $this->deleteDirectory($targetDir);
            }

            $this->copyDirectory($pluginRoot, $targetDir);

            // Re-discover the newly added plugin
            $this->registry->getDiscovery()->discover();

            $this->logger?->info("Plugin '{$pluginId}' ({$manifest->getVersion()}) importé et installé avec succès depuis ZIP.");

            return $manifest;
        } finally {
            $this->deleteDirectory($sandboxDir);
        }
    }

    /**
     * Locate directory containing plugin.json (searches sandbox root and subdirectories up to 3 levels deep).
     */
    private function locatePluginRoot(string $dir): ?string
    {
        $queue = [$dir];
        $depth = 0;
        $maxDepth = 3;

        while (! empty($queue) && $depth <= $maxDepth) {
            $nextQueue = [];
            foreach ($queue as $currentDir) {
                if (file_exists($currentDir.DIRECTORY_SEPARATOR.'plugin.json')) {
                    return $currentDir;
                }

                $items = scandir($currentDir);
                if ($items === false) {
                    continue;
                }

                foreach ($items as $item) {
                    if ($item === '.' || $item === '..' || $item === '__MACOSX' || str_starts_with($item, '.')) {
                        continue;
                    }

                    $subDir = $currentDir.DIRECTORY_SEPARATOR.$item;
                    if (is_dir($subDir)) {
                        $nextQueue[] = $subDir;
                    }
                }
            }

            $queue = $nextQueue;
            $depth++;
        }

        return null;
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        $items = scandir($source);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcPath = $source.DIRECTORY_SEPARATOR.$item;
            $dstPath = $destination.DIRECTORY_SEPARATOR.$item;

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
