<?php

declare(strict_types=1);

namespace App\Core\Updater;

use App\Core\Security\Audit\AuditLogger;
use App\Core\Updater\Models\UpdateHistory;
use App\Core\Updater\Services\BackupService;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;
use ZipArchive;

class CoreZipUpdater
{
    /**
     * Allowed top-level directory prefixes for Core updates.
     *
     * @var list<string>
     */
    private const ALLOWED_PREFIXES = [
        'app/Core/',
        'platform/core/',
        'config/cms.php',
        'database/migrations/',
    ];

    public function __construct(
        private BackupService $backupService,
        private AuditLogger $auditLogger
    ) {}

    /**
     * Apply a verified update from a ZIP archive.
     *
     * @return array{success: bool, from_version: string, to_version: string, backup_path: string}
     */
    public function updateFromZip(string $zipFilePath, ?User $user = null): array
    {
        if (! file_exists($zipFilePath)) {
            throw new RuntimeException("Le fichier d'archive ZIP est introuvable : {$zipFilePath}");
        }

        $currentVersion = (string) config('cms.version', '1.1.0');
        $zip = new ZipArchive;

        if ($zip->open($zipFilePath) !== true) {
            throw new RuntimeException("Impossible d'ouvrir l'archive ZIP spécifiée.");
        }

        // 1. Verify update.json exists
        $manifestContent = $zip->getFromName('update.json');
        if ($manifestContent === false) {
            $zip->close();
            throw new RuntimeException("Manifeste [update.json] manquant à la racine de l'archive de mise à jour.");
        }

        /** @var array<string, mixed>|null $manifest */
        $manifest = json_decode($manifestContent, true);
        if (! is_array($manifest) || ! isset($manifest['version']) || ($manifest['name'] ?? '') !== 'lcms-core') {
            $zip->close();
            throw new RuntimeException('Le fichier [update.json] est invalide ou ne cible pas [lcms-core].');
        }

        $targetVersion = (string) $manifest['version'];
        $minCoreVersion = (string) ($manifest['min_core_version'] ?? '1.0.0');

        if (version_compare($currentVersion, $minCoreVersion, '<')) {
            $zip->close();
            throw new RuntimeException("Version actuelle ({$currentVersion}) incompatible. Cette mise à jour requiert au minimum la version {$minCoreVersion}.");
        }

        // 2. Validate zip contents against Zip-Slip & unauthorized file modifications
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if ($entryName === false) {
                continue;
            }

            $normalized = str_replace('\\', '/', $entryName);

            // Zip Slip protection
            if (str_contains($normalized, '../') || str_starts_with($normalized, '/')) {
                $zip->close();
                throw new RuntimeException("Archive compromise : tentative de traversée de répertoire détectée ({$normalized}).");
            }

            if ($normalized === 'update.json' || str_ends_with($normalized, '/')) {
                continue;
            }

            // Check against allowed scopes
            $isAllowed = false;
            foreach (self::ALLOWED_PREFIXES as $prefix) {
                if (str_starts_with($normalized, $prefix)) {
                    $isAllowed = true;
                    break;
                }
            }

            if (! $isAllowed) {
                $zip->close();
                throw new RuntimeException("Fichier non autorisé dans l'archive de mise à jour Core : {$normalized}. La mise à jour ne peut modifier que app/Core/, platform/core/, config/cms.php et les migrations.");
            }
        }

        // 3. Perform pre-update backup
        $backupPath = $this->backupService->createBackup($currentVersion);

        /** @var UpdateHistory $history */
        $history = UpdateHistory::create([
            'from_version' => $currentVersion,
            'to_version' => $targetVersion,
            'status' => 'PENDING',
            'log' => "Début de la mise à jour de {$currentVersion} vers {$targetVersion}.",
            'backup_path' => $backupPath,
            'user_id' => $user?->id,
            'created_at' => now(),
        ]);

        // 4. Sandbox extraction & overwrite
        $sandboxDir = storage_path('app/cms-updates-temp/'.uniqid('update_', true));
        mkdir($sandboxDir, 0755, true);

        try {
            $zip->extractTo($sandboxDir);
            $zip->close();

            // Copy sandboxed files to actual destination
            $this->copyDirectoryContents($sandboxDir, base_path());

            // Run migrations
            Artisan::call('migrate', ['--force' => true]);
            $migrationLog = Artisan::output();

            // Clear cache
            Artisan::call('optimize:clear');
            $cacheLog = Artisan::output();

            // Clean sandbox
            File::deleteDirectory($sandboxDir);

            $history->status = 'SUCCESS';
            $history->log = "Mise à jour appliquée avec succès.\nMigrations:\n{$migrationLog}\nOptimisations:\n{$cacheLog}";
            $history->save();

            $this->auditLogger->log(
                action: 'core.update.success',
                resourceType: 'CoreUpdate',
                resourceId: (string) $history->id,
                oldValues: ['version' => $currentVersion],
                newValues: ['version' => $targetVersion],
                user: $user
            );

            return [
                'success' => true,
                'from_version' => $currentVersion,
                'to_version' => $targetVersion,
                'backup_path' => $backupPath,
            ];
        } catch (Throwable $e) {
            // Automatic Rollback
            $this->backupService->rollback($backupPath);

            if (is_dir($sandboxDir)) {
                File::deleteDirectory($sandboxDir);
            }

            $history->status = 'ROLLED_BACK';
            $history->log = "Échec : {$e->getMessage()}\nRestauration automatique effectuée depuis {$backupPath}.\nTrace:\n{$e->getTraceAsString()}";
            $history->save();

            $this->auditLogger->log(
                action: 'core.update.failed_rolled_back',
                resourceType: 'CoreUpdate',
                resourceId: (string) $history->id,
                user: $user
            );

            throw new RuntimeException("Échec de la mise à jour : {$e->getMessage()}. Les fichiers précédents ont été restaurés.", 0, $e);
        }
    }

    /**
     * Copy directory contents excluding update.json.
     */
    private function copyDirectoryContents(string $sourceDir, string $destinationDir): void
    {
        $items = scandir($sourceDir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === 'update.json') {
                continue;
            }

            $srcPath = $sourceDir.DIRECTORY_SEPARATOR.$item;
            $dstPath = $destinationDir.DIRECTORY_SEPARATOR.$item;

            if (is_dir($srcPath)) {
                if (! is_dir($dstPath)) {
                    mkdir($dstPath, 0755, true);
                }
                $this->copyDirectoryContents($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }
}
