<?php

declare(strict_types=1);

namespace Core\Updater\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;
use ZipArchive;

class BackupService
{
    private string $backupDir;

    private string $sourceBasePath;

    public function __construct(
        ?string $backupDir = null,
        ?string $sourceBasePath = null
    ) {
        $this->backupDir = $backupDir ?? storage_path('app/cms-backups');
        $this->sourceBasePath = $sourceBasePath ?? base_path();
    }

    public function setBackupDir(string $dir): self
    {
        $this->backupDir = $dir;

        return $this;
    }

    public function getBackupDir(): string
    {
        return $this->backupDir;
    }

    public function setSourceBasePath(string $path): self
    {
        $this->sourceBasePath = $path;

        return $this;
    }

    public function getSourceBasePath(): string
    {
        return $this->sourceBasePath;
    }

    /**
     * Create a pre-update snapshot backup of core files and database.
     *
     * @return string Absolute path to the created backup zip file
     */
    public function createBackup(string $version): string
    {
        $backupDir = $this->backupDir;
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = date('Ymd_His');
        $backupFileName = "backup-core-{$version}-{$timestamp}.zip";
        $backupPath = $backupDir.DIRECTORY_SEPARATOR.$backupFileName;

        $zip = new ZipArchive;
        if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Impossible d'initialiser l'archive de sauvegarde : {$backupPath}");
        }

        // Add Core directory
        $coreDir = $this->sourceBasePath.DIRECTORY_SEPARATOR.'Core';
        if (is_dir($coreDir)) {
            $this->addDirectoryToZip($zip, $coreDir, 'Core');
        }

        // Add config/cms.php
        $cmsConfig = $this->sourceBasePath.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'cms.php';
        if (! file_exists($cmsConfig)) {
            $cmsConfig = config_path('cms.php');
        }
        if (file_exists($cmsConfig)) {
            $zip->addFile($cmsConfig, 'config/cms.php');
        }

        // Add database if SQLite
        $sqliteDb = database_path('database.sqlite');
        if (file_exists($sqliteDb)) {
            $zip->addFile($sqliteDb, 'database/database.sqlite');
        }

        $zip->close();

        return $backupPath;
    }

    /**
     * Restore core files and database from a backup zip archive.
     */
    public function rollback(string $backupPath): bool
    {
        if (! file_exists($backupPath)) {
            return false;
        }

        $zip = new ZipArchive;
        if ($zip->open($backupPath) !== true) {
            return false;
        }

        try {
            $zip->extractTo($this->sourceBasePath);
            $zip->close();

            return true;
        } catch (Throwable) {
            $zip->close();

            return false;
        }
    }

    /**
     * Helper to recursively add a directory to a ZipArchive.
     */
    private function addDirectoryToZip(ZipArchive $zip, string $sourceDir, string $zipDir): void
    {
        if (! is_dir($sourceDir)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            $filePath = $file->getRealPath();
            if ($filePath === false) {
                continue;
            }

            $relativePath = substr($filePath, strlen($sourceDir) + 1);
            $normalizedRelative = str_replace('\\', '/', $relativePath);
            $zipEntryPath = $zipDir.'/'.$normalizedRelative;

            if ($file->isDir()) {
                $zip->addEmptyDir($zipEntryPath);
            } else {
                $zip->addFile($filePath, $zipEntryPath);
            }
        }
    }
}
