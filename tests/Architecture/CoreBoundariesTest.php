<?php

declare(strict_types=1);

namespace Tests\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class CoreBoundariesTest extends TestCase
{
    /**
     * Ensure Core never imports classes from the Plugins namespace.
     */
    public function test_core_never_imports_plugins_namespace(): void
    {
        $coreDir = base_path('Core');
        $this->assertDirectoryExists($coreDir);

        $files = $this->getPhpFiles($coreDir);
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());
            $this->assertIsString($content);

            $this->assertStringNotContainsString(
                'use Plugins\\',
                $content,
                "Core file {$file->getFilename()} must never import classes from Plugins\\ namespace."
            );
        }
    }

    /**
     * Ensure Core never imports obsolete Platform\ namespace.
     */
    public function test_core_never_depends_on_platform_admin_layer(): void
    {
        $coreDir = base_path('Core');
        $files = $this->getPhpFiles($coreDir);

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());
            $this->assertIsString($content);

            $this->assertStringNotContainsString(
                'use Platform\\',
                $content,
                "Core file {$file->getFilename()} must never depend on the legacy Platform\\ namespace."
            );
        }
    }

    /**
     * Ensure every PHP file in Core declares strict types.
     */
    public function test_all_core_files_declare_strict_types(): void
    {
        $coreDir = base_path('Core');
        $files = $this->getPhpFiles($coreDir);

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());
            $this->assertIsString($content);

            $this->assertStringContainsString(
                'declare(strict_types=1);',
                $content,
                "File {$file->getFilename()} must declare strict_types=1."
            );
        }
    }

    /**
     * Ensure Core Admin Panel does not directly import concrete Plugins classes.
     */
    public function test_platform_admin_panel_never_imports_concrete_plugins_classes(): void
    {
        $adminDir = base_path('Core/Http/Controllers/Admin');
        $this->assertDirectoryExists($adminDir);

        $files = $this->getPhpFiles($adminDir);

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());
            $this->assertIsString($content);

            $this->assertStringNotContainsString(
                'use Plugins\\',
                $content,
                "Admin controller {$file->getFilename()} must not import concrete Plugins\\ classes."
            );
        }
    }

    /**
     * Ensure Lean Core rule: no business logic tables (posts, products, carts, media) exist in core migrations.
     */
    public function test_core_database_migrations_contain_no_business_logic_tables(): void
    {
        $migrationsDir = database_path('migrations');
        $files = $this->getPhpFiles($migrationsDir);

        $forbiddenTables = ['posts', 'articles', 'products', 'orders', 'carts', 'categories', 'tags', 'comments'];

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());
            $this->assertIsString($content);

            foreach ($forbiddenTables as $table) {
                $this->assertDoesNotMatchRegularExpression(
                    "/Schema::create\(\s*['\"]{$table}['\"]/",
                    $content,
                    "Lean Core violation: Migration {$file->getFilename()} defines a forbidden business domain table '{$table}'."
                );
            }
        }
    }

    /**
     * @return list<SplFileInfo>
     */
    private function getPhpFiles(string $dir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item instanceof SplFileInfo && $item->isFile() && $item->getExtension() === 'php') {
                if (str_ends_with($item->getFilename(), '.blade.php')) {
                    continue;
                }
                $files[] = $item;
            }
        }

        return $files;
    }
}
