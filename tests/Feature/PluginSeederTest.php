<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginSeederTest extends TestCase
{
    use RefreshDatabase;

    private string $tempPluginsDir;

    private PluginManager $pluginManager;

    private PluginRegistry $pluginRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPluginsDir = sys_get_temp_dir().'/cms_seeder_test_'.uniqid();
        mkdir($this->tempPluginsDir, 0777, true);
        config(['cms.plugins.path' => $this->tempPluginsDir]);

        $this->pluginRegistry = $this->app->make(PluginRegistry::class);
        $this->pluginManager = $this->app->make(PluginManager::class);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempPluginsDir);

        parent::tearDown();
    }

    public function test_plugin_seed_command_fails_without_arguments_or_all_flag(): void
    {
        $this->artisan('cms:plugin:seed')
            ->expectsOutputToContain('Please specify a plugin identifier or use the --all option.')
            ->assertFailed();
    }

    public function test_plugin_seed_command_fails_for_unknown_plugin(): void
    {
        $this->artisan('cms:plugin:seed', ['id' => 'unknown.plugin'])
            ->expectsOutputToContain("Plugin 'unknown.plugin' not found.")
            ->assertFailed();
    }

    public function test_plugin_seed_command_warns_when_plugin_has_no_seeders(): void
    {
        $this->createTestPlugin('lcmscore.empty-plugin', hasSeeders: false);

        $this->artisan('cms:plugin:seed', ['id' => 'lcmscore.empty-plugin'])
            ->expectsOutputToContain("No seeders found for plugin 'lcmscore.empty-plugin'.")
            ->assertSuccessful();
    }

    public function test_plugin_seed_command_executes_plugin_seeder_successfully(): void
    {
        $this->createTestPlugin('lcmscore.test-plugin', hasSeeders: true);

        // Install & activate plugin so migration creates the table
        $this->pluginManager->install('lcmscore.test-plugin');
        $this->pluginManager->activate('lcmscore.test-plugin');

        $this->artisan('cms:plugin:seed', ['id' => 'lcmscore.test-plugin'])
            ->expectsOutputToContain('Seeding plugin: lcmscore.test-plugin...')
            ->expectsOutputToContain('LcmscoreTestPluginDatabaseSeeder')
            ->expectsOutputToContain("Plugin 'lcmscore.test-plugin' seeded successfully.")
            ->assertSuccessful();

        $this->assertDatabaseHas('plugin_test_lcmscore_test_plugin', [
            'title' => 'Seeded Item 1',
        ]);
        $this->assertDatabaseHas('plugin_test_lcmscore_test_plugin', [
            'title' => 'Seeded Item 2',
        ]);
    }

    public function test_plugin_seed_command_with_all_flag_seeds_active_plugins(): void
    {
        $this->createTestPlugin('lcmscore.seeded-active', hasSeeders: true);
        $this->createTestPlugin('lcmscore.unseeded-active', hasSeeders: false);

        $this->pluginManager->install('lcmscore.seeded-active');
        $this->pluginManager->activate('lcmscore.seeded-active');

        $this->pluginManager->install('lcmscore.unseeded-active');
        $this->pluginManager->activate('lcmscore.unseeded-active');

        $this->artisan('cms:plugin:seed', ['--all' => true])
            ->expectsOutputToContain('Seeding plugin: lcmscore.seeded-active...')
            ->expectsOutputToContain("No seeders found for 'lcmscore.unseeded-active'.")
            ->expectsOutputToContain('Finished seeding active plugins')
            ->assertSuccessful();

        $this->assertDatabaseHas('plugin_test_lcmscore_seeded_active', [
            'title' => 'Seeded Item 1',
        ]);
    }

    public function test_database_seeder_runs_active_plugin_seeders(): void
    {
        $this->createTestPlugin('lcmscore.demo-seeded', hasSeeders: true);

        $this->pluginManager->install('lcmscore.demo-seeded');
        $this->pluginManager->activate('lcmscore.demo-seeded');

        $this->artisan('db:seed')
            ->assertSuccessful();

        $this->assertDatabaseHas('plugin_test_lcmscore_demo_seeded', [
            'title' => 'Seeded Item 1',
        ]);
    }

    private function createTestPlugin(string $id, bool $hasSeeders = true): void
    {
        $studly = str_replace(' ', '', ucwords(str_replace(['.', '-'], ' ', $id)));
        $table = 'plugin_test_'.str_replace(['.', '-'], '_', $id);

        $pluginDir = $this->tempPluginsDir.'/'.$id;
        mkdir($pluginDir.'/src/Providers', 0777, true);
        mkdir($pluginDir.'/database/migrations', 0777, true);

        $manifest = [
            'id' => $id,
            'name' => 'Test Plugin '.$id,
            'version' => '1.0.0',
            'description' => 'Test seeder plugin',
            'author' => ['name' => 'LCMS Core', 'email' => 'dev@lcmscore.local'],
            'compatibility' => ['php' => '^8.2', 'cms' => '^1.0'],
            'autoload' => [
                'psr-4' => ["Plugins\\{$studly}\\" => 'src/'],
            ],
            'providers' => ["Plugins\\{$studly}\\Providers\\{$studly}ServiceProvider"],
        ];

        file_put_contents($pluginDir.'/plugin.json', json_encode($manifest, JSON_PRETTY_PRINT));

        $providerCode = <<<PHP
<?php
namespace Plugins\\{$studly}\\Providers;
use Illuminate\Support\ServiceProvider;
class {$studly}ServiceProvider extends ServiceProvider {
    public function register(): void {}
    public function boot(): void {}
}
PHP;
        file_put_contents($pluginDir."/src/Providers/{$studly}ServiceProvider.php", $providerCode);

        $migrationCode = <<<PHP
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('title');
            \$table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('{$table}');
    }
};
PHP;
        file_put_contents($pluginDir.'/database/migrations/2026_10_26_000001_create_table.php', $migrationCode);

        if ($hasSeeders) {
            mkdir($pluginDir.'/database/seeders', 0777, true);
            $seederClass = "{$studly}DatabaseSeeder";
            $seederCode = <<<PHP
<?php
namespace Plugins\\{$studly}\\Database\\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class {$seederClass} extends Seeder {
    public function run(): void {
        DB::table('{$table}')->insert([
            ['title' => 'Seeded Item 1', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Seeded Item 2', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
PHP;
            file_put_contents($pluginDir."/database/seeders/{$seederClass}.php", $seederCode);
        }
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = scandir($path) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $current = $path.'/'.$item;
            if (is_dir($current)) {
                $this->deleteDirectory($current);
            } else {
                unlink($current);
            }
        }
        rmdir($path);
    }
}
