<?php

declare(strict_types=1);

namespace App\Core\Lifecycle;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class PluginMigrationManager
{
    private const TABLE_NAME = 'cms_plugin_migrations';

    /**
     * Run all pending migrations for a given plugin.
     *
     * @return list<string> Applied migration filenames
     */
    public function migrate(string $pluginId, string $pluginPath): array
    {
        $this->ensureTrackingTableExists();

        $migrationsDir = rtrim(str_replace('\\', '/', $pluginPath), '/').'/database/migrations';
        if (! is_dir($migrationsDir)) {
            return [];
        }

        $allFiles = glob($migrationsDir.'/*.php') ?: [];
        sort($allFiles);

        $ran = $this->getRanMigrations($pluginId);
        $nextBatch = $this->getNextBatchNumber($pluginId);
        $applied = [];

        foreach ($allFiles as $file) {
            $filename = basename($file);
            if (in_array($filename, $ran, true)) {
                continue;
            }

            $migration = $this->resolveMigrationInstance($file);
            if (! method_exists($migration, 'up')) {
                throw new RuntimeException("Migration file '{$filename}' in plugin '{$pluginId}' does not define an up() method.");
            }

            try {
                $migration->up();

                DB::table(self::TABLE_NAME)->insert([
                    'plugin_id' => $pluginId,
                    'migration' => $filename,
                    'batch' => $nextBatch,
                    'created_at' => now(),
                ]);

                $applied[] = $filename;
            } catch (Throwable $e) {
                throw new RuntimeException(
                    "Failed executing migration '{$filename}' for plugin '{$pluginId}': ".$e->getMessage(),
                    (int) $e->getCode(),
                    $e
                );
            }
        }

        return $applied;
    }

    /**
     * Rollback migrations for a given plugin (used during full uninstallation with data purge).
     *
     * @return list<string> Rolled back migration filenames
     */
    public function rollback(string $pluginId, string $pluginPath): array
    {
        $this->ensureTrackingTableExists();

        $migrationsDir = rtrim(str_replace('\\', '/', $pluginPath), '/').'/database/migrations';

        /** @var list<object{id: int, migration: string, batch: int}> $records */
        $records = DB::table(self::TABLE_NAME)
            ->where('plugin_id', $pluginId)
            ->orderBy('batch', 'desc')
            ->orderBy('id', 'desc')
            ->get(['id', 'migration', 'batch'])
            ->all();

        $rolledBack = [];

        foreach ($records as $record) {
            $filePath = $migrationsDir.'/'.$record->migration;

            if (file_exists($filePath)) {
                $migration = $this->resolveMigrationInstance($filePath);
                if (method_exists($migration, 'down')) {
                    $migration->down();
                }
            }

            DB::table(self::TABLE_NAME)->where('id', $record->id)->delete();
            $rolledBack[] = $record->migration;
        }

        return $rolledBack;
    }

    /**
     * Get all migration filenames already executed for a plugin.
     *
     * @return list<string>
     */
    public function getRanMigrations(string $pluginId): array
    {
        if (! Schema::hasTable(self::TABLE_NAME)) {
            return [];
        }

        /** @var list<string> $migrations */
        $migrations = DB::table(self::TABLE_NAME)
            ->where('plugin_id', $pluginId)
            ->pluck('migration')
            ->all();

        return $migrations;
    }

    private function getNextBatchNumber(string $pluginId): int
    {
        $max = DB::table(self::TABLE_NAME)
            ->where('plugin_id', $pluginId)
            ->max('batch');

        return $max !== null ? ((int) $max) + 1 : 1;
    }

    private function ensureTrackingTableExists(): void
    {
        if (! Schema::hasTable(self::TABLE_NAME)) {
            Schema::create(self::TABLE_NAME, function ($table): void {
                $table->id();
                $table->string('plugin_id')->index();
                $table->string('migration');
                $table->integer('batch');
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['plugin_id', 'migration']);
            });
        }
    }

    private function resolveMigrationInstance(string $filePath): Migration
    {
        $declaredClassesBefore = get_declared_classes();

        /** @var mixed $instance */
        $instance = require $filePath;

        if ($instance instanceof Migration) {
            return $instance;
        }

        $declaredClassesAfter = get_declared_classes();
        $diff = array_diff($declaredClassesAfter, $declaredClassesBefore);

        foreach ($diff as $className) {
            if (is_subclass_of($className, Migration::class)) {
                return new $className;
            }
        }

        throw new RuntimeException("Could not resolve Migration instance from file '{$filePath}'.");
    }
}
