<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Lifecycle\PluginMigrationManager;
use App\Core\PluginDiscovery\Autoload\PluginClassLoader;
use App\Core\PluginDiscovery\ManifestValidator;
use App\Core\PluginDiscovery\PluginDiscovery;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PluginMigrationManagerTest extends TestCase
{
    use RefreshDatabase;

    private string $tempPluginsDir;

    private PluginMigrationManager $migrationManager;

    private PluginManager $pluginManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPluginsDir = sys_get_temp_dir().'/cms_mig_test_'.uniqid();
        mkdir($this->tempPluginsDir, 0777, true);

        $classLoader = new PluginClassLoader;
        $validator = new ManifestValidator;
        $this->migrationManager = new PluginMigrationManager;
        $discovery = new PluginDiscovery($validator, $classLoader);
        $registry = new PluginRegistry($discovery);
        $this->pluginManager = new PluginManager($registry, $this->migrationManager, $this->app);

        config(['cms.plugins.path' => $this->tempPluginsDir]);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempPluginsDir);
        parent::tearDown();
    }

    public function test_migrates_plugin_database_tables_on_installation(): void
    {
        $pluginPath = $this->createMigratedPlugin('acme.migrated-plugin', 'plugin_test_items');

        $this->assertFalse(Schema::hasTable('plugin_test_items'));

        $this->pluginManager->install('acme.migrated-plugin');

        $this->assertTrue(Schema::hasTable('plugin_test_items'));
        $this->assertDatabaseHas('cms_plugin_migrations', [
            'plugin_id' => 'acme.migrated-plugin',
            'migration' => '2026_10_13_000010_create_plugin_test_items_table.php',
            'batch' => 1,
        ]);
    }

    public function test_deactivation_does_not_rollback_or_delete_tables(): void
    {
        $this->createMigratedPlugin('acme.preserve-plugin', 'plugin_preserve_items');

        $this->pluginManager->install('acme.preserve-plugin');
        $this->pluginManager->activate('acme.preserve-plugin');

        // Insert dummy row to verify persistence
        DB::table('plugin_preserve_items')->insert([
            'title' => 'Preserved data test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Deactivate plugin
        $this->pluginManager->deactivate('acme.preserve-plugin');

        // Strict verification: table and records must NOT be dropped
        $this->assertTrue(Schema::hasTable('plugin_preserve_items'));
        $this->assertDatabaseHas('plugin_preserve_items', [
            'title' => 'Preserved data test',
        ]);
        $this->assertDatabaseHas('cms_plugin_migrations', [
            'plugin_id' => 'acme.preserve-plugin',
            'migration' => '2026_10_13_000010_create_plugin_preserve_items_table.php',
        ]);
    }

    public function test_subsequent_migrate_does_not_rerun_applied_migrations(): void
    {
        $pluginPath = $this->createMigratedPlugin('acme.idempotent-plugin', 'plugin_idempotent_items');

        $appliedFirst = $this->migrationManager->migrate('acme.idempotent-plugin', $pluginPath);
        $this->assertCount(1, $appliedFirst);

        // Second run should skip already ran migration
        $appliedSecond = $this->migrationManager->migrate('acme.idempotent-plugin', $pluginPath);
        $this->assertCount(0, $appliedSecond);
    }

    public function test_uninstall_with_purge_rolls_back_tables_and_removes_tracking(): void
    {
        $this->createMigratedPlugin('acme.purge-plugin', 'plugin_purge_items');

        $this->pluginManager->install('acme.purge-plugin');
        $this->assertTrue(Schema::hasTable('plugin_purge_items'));

        // Uninstall with purgeData = true
        $this->pluginManager->uninstall('acme.purge-plugin', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_purge_items'));
        $this->assertDatabaseMissing('cms_plugin_migrations', [
            'plugin_id' => 'acme.purge-plugin',
        ]);
        $this->assertDatabaseMissing('cms_plugins', [
            'id' => 'acme.purge-plugin',
        ]);
    }

    private function createMigratedPlugin(string $id, string $tableName): string
    {
        $slug = explode('.', $id)[1] ?? $id;
        $pluginDir = $this->tempPluginsDir.'/'.$slug;
        $migrationsDir = $pluginDir.'/database/migrations';
        mkdir($migrationsDir, 0777, true);

        file_put_contents($pluginDir.'/plugin.json', json_encode([
            'id' => $id,
            'name' => 'Migrated Plugin '.$slug,
            'version' => '1.0.0',
        ]));

        $migrationFilename = "2026_10_13_000010_create_{$tableName}_table.php";
        $migrationContent = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table): void {
            \$table->id();
            \$table->string('title');
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;

        file_put_contents($migrationsDir.'/'.$migrationFilename, $migrationContent);

        return $pluginDir;
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
