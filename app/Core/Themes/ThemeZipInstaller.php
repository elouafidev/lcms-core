<?php

declare(strict_types=1);

namespace App\Core\Themes;

use App\Core\Themes\DTO\ThemeManifest;
use App\Core\Themes\Exceptions\InvalidThemeArchiveException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use ZipArchive;

class ThemeZipInstaller
{
    /**
     * List of dangerous or binary file extensions strictly forbidden in uploaded themes.
     *
     * @var list<string>
     */
    private const FORBIDDEN_EXTENSIONS = [
        'exe', 'sh', 'bat', 'cmd', 'phar', 'phtml', 'bin', 'msi', 'dll', 'so', 'com', 'vbs',
    ];

    public function __construct(
        private ThemeDiscovery $discovery,
        private ThemeManager $themeManager,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Install a theme from an uploaded ZIP archive or filesystem path.
     *
     * @throws InvalidThemeArchiveException
     */
    public function installArchive(UploadedFile|string $archive, bool $overwrite = false): ThemeManifest
    {
        $archivePath = $archive instanceof UploadedFile ? $archive->getRealPath() : $archive;
        if (! is_string($archivePath) || ! file_exists($archivePath) || ! is_readable($archivePath)) {
            throw InvalidThemeArchiveException::unreadableArchive();
        }

        $zip = new ZipArchive;
        $openResult = $zip->open($archivePath);
        if ($openResult !== true) {
            throw InvalidThemeArchiveException::openFailed($openResult);
        }

        $sandboxDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lcms_theme_zip_'.Str::uuid()->toString();
        if (! mkdir($sandboxDir, 0777, true) && ! is_dir($sandboxDir)) {
            $zip->close();
            throw new RuntimeException("Impossible de créer le répertoire temporaire d'extraction.");
        }

        try {
            $entryCount = $zip->numFiles;
            if ($entryCount === 0) {
                throw new InvalidThemeArchiveException("L'archive ZIP est vide.");
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
                    throw InvalidThemeArchiveException::zipSlipDetected($entryName);
                }

                // Protection against forbidden executable extensions
                $ext = strtolower(pathinfo($entryName, PATHINFO_EXTENSION));
                if (in_array($ext, self::FORBIDDEN_EXTENSIONS, true)) {
                    throw InvalidThemeArchiveException::forbiddenExtension($entryName);
                }
            }

            $extractSuccess = $zip->extractTo($sandboxDir);
            $zip->close();

            if (! $extractSuccess) {
                throw new InvalidThemeArchiveException("Échec lors de l'extraction de l'archive ZIP.");
            }

            $themeRoot = $this->locateThemeRoot($sandboxDir);
            if ($themeRoot === null) {
                throw InvalidThemeArchiveException::missingManifest();
            }

            $manifestPath = $themeRoot.DIRECTORY_SEPARATOR.'theme.json';
            $manifest = $this->parseAndValidateManifest($manifestPath);
            $themeId = $manifest->getId();

            $themesBasePath = (string) config('cms.themes.path', base_path('platform/themes'));
            $targetDir = $themesBasePath.DIRECTORY_SEPARATOR.$themeId;

            if (is_dir($targetDir) && ! $overwrite) {
                throw InvalidThemeArchiveException::alreadyExists($themeId);
            }

            if ($this->themeManager->getActiveThemeId() === $themeId && $overwrite) {
                throw InvalidThemeArchiveException::cannotOverwriteActive($themeId);
            }

            if (! is_dir($themesBasePath)) {
                mkdir($themesBasePath, 0777, true);
            }

            if (is_dir($targetDir) && $overwrite) {
                $this->deleteDirectory($targetDir);
            }

            $this->copyDirectory($themeRoot, $targetDir);

            // Re-discover the newly added theme
            $this->discovery->discover();

            $this->logger?->info("Thème '{$themeId}' ({$manifest->getVersion()}) importé et installé avec succès depuis ZIP.");

            return $manifest;
        } finally {
            $this->deleteDirectory($sandboxDir);
        }
    }

    /**
     * Locate directory containing theme.json (either sandbox root or immediate subdirectory).
     */
    private function locateThemeRoot(string $dir): ?string
    {
        if (file_exists($dir.DIRECTORY_SEPARATOR.'theme.json')) {
            return $dir;
        }

        $items = scandir($dir);
        if ($items === false) {
            return null;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $subDir = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($subDir) && file_exists($subDir.DIRECTORY_SEPARATOR.'theme.json')) {
                return $subDir;
            }
        }

        return null;
    }

    /**
     * Parse and validate theme.json contents into a ThemeManifest.
     *
     * @throws InvalidThemeArchiveException
     */
    private function parseAndValidateManifest(string $manifestPath): ThemeManifest
    {
        if (! file_exists($manifestPath) || ! is_readable($manifestPath)) {
            throw InvalidThemeArchiveException::missingManifest();
        }

        $content = file_get_contents($manifestPath);
        if ($content === false) {
            throw new InvalidThemeArchiveException("Impossible de lire le fichier 'theme.json'.");
        }

        try {
            /** @var mixed $data */
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($data)) {
                throw new InvalidThemeArchiveException("Le contenu du fichier 'theme.json' doit être un objet JSON valide.");
            }

            /** @var array<string, mixed> $data */
            return ThemeManifest::fromArray($data);
        } catch (JsonException $e) {
            throw new InvalidThemeArchiveException("Fichier 'theme.json' JSON syntaxiquement invalide : {$e->getMessage()}", 0, $e);
        } catch (InvalidArgumentException $e) {
            throw new InvalidThemeArchiveException("Manifeste de thème non conforme : {$e->getMessage()}", 0, $e);
        }
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
