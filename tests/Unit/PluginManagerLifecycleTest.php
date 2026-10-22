<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Lifecycle\Exceptions\InvalidStateTransitionException;
use App\Core\Lifecycle\PluginMigrationManager;
use App\Core\Lifecycle\PluginState;
use App\Core\PluginDiscovery\Autoload\PluginClassLoader;
use App\Core\PluginDiscovery\ManifestValidator;
use App\Core\PluginDiscovery\PluginDiscovery;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\Models\PluginRecord;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Tests\TestCase;

// Dummy Test Service Provider for Lifecycle testing
class DummyLifecycleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config(['cms.test_dummy_loaded' => true]);
    }
}

// Dummy Failing Service Provider for Quarantine testing
class DummyFailingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        throw new RuntimeException('Intentional boot crash for testing quarantine.');
    }
}

class PluginManagerLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private string $tempPluginsDir;

    private PluginDiscovery $discovery;

    private PluginRegistry $registry;

    private PluginManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPluginsDir = sys_get_temp_dir().'/cms_lifecycle_test_'.uniqid();
        mkdir($this->tempPluginsDir, 0777, true);

        $classLoader = new PluginClassLoader;
        $validator = new ManifestValidator;
        $migrationManager = new PluginMigrationManager;
        $this->discovery = new PluginDiscovery($validator, $classLoader);
        $this->registry = new PluginRegistry($this->discovery);
        $this->manager = new PluginManager($this->registry, $migrationManager, $this->app);

        // Override CMS plugins path config for testing
        config(['cms.plugins.path' => $this->tempPluginsDir]);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempPluginsDir);
        parent::tearDown();
    }

    public function test_install_transitions_available_plugin_to_installed(): void
    {
        $this->createPluginOnDisk('lcmscore.sample-plugin', 'Sample Plugin', '1.0.0');

        $this->assertFalse($this->registry->isInstalled('lcmscore.sample-plugin'));

        $record = $this->manager->install('lcmscore.sample-plugin');

        $this->assertEquals(PluginState::INSTALLED, $record->state);
        $this->assertFalse($record->is_active);
        $this->assertDatabaseHas('cms_plugins', [
            'id' => 'lcmscore.sample-plugin',
            'state' => 'INSTALLED',
            'is_active' => false,
        ]);
        $this->assertTrue($this->registry->isInstalled('lcmscore.sample-plugin'));
    }

    public function test_activate_transitions_installed_plugin_to_active_and_registers_provider(): void
    {
        $this->createPluginOnDisk(
            id: 'lcmscore.active-plugin',
            name: 'Active Plugin',
            version: '1.0.0',
            providers: [DummyLifecycleServiceProvider::class]
        );

        $this->manager->install('lcmscore.active-plugin');

        $this->assertNull(config('cms.test_dummy_loaded'));

        $record = $this->manager->activate('lcmscore.active-plugin');

        $this->assertEquals(PluginState::ACTIVE, $record->state);
        $this->assertTrue($record->is_active);
        $this->assertTrue($this->registry->isActive('lcmscore.active-plugin'));
        $this->assertTrue(config('cms.test_dummy_loaded'));
    }

    public function test_deactivate_transitions_active_plugin_to_disabled(): void
    {
        $this->createPluginOnDisk('lcmscore.toggle-plugin', 'Toggle Plugin', '1.0.0');

        $this->manager->install('lcmscore.toggle-plugin');
        $this->manager->activate('lcmscore.toggle-plugin');

        $record = $this->manager->deactivate('lcmscore.toggle-plugin');

        $this->assertEquals(PluginState::DISABLED, $record->state);
        $this->assertFalse($record->is_active);
        $this->assertFalse($this->registry->isActive('lcmscore.toggle-plugin'));
        $this->assertDatabaseHas('cms_plugins', [
            'id' => 'lcmscore.toggle-plugin',
            'state' => 'DISABLED',
            'is_active' => false,
        ]);
    }

    public function test_cannot_activate_uninstalled_plugin(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Cannot activate plugin 'lcmscore.non-installed': Plugin is not installed.");

        $this->manager->activate('lcmscore.non-installed');
    }

    public function test_cannot_perform_forbidden_transition(): void
    {
        $this->createPluginOnDisk('lcmscore.bad-transition', 'Bad Transition', '1.0.0');

        $record = $this->manager->install('lcmscore.bad-transition');

        // Cannot deactivate an INSTALLED plugin (must be ACTIVE first)
        $this->expectException(InvalidStateTransitionException::class);
        $this->expectExceptionMessage("cannot transition from state 'INSTALLED' to 'DISABLED'");

        $this->manager->deactivate('lcmscore.bad-transition');
    }

    public function test_unhandled_boot_exception_quarantines_plugin_to_error_state(): void
    {
        $this->createPluginOnDisk(
            id: 'lcmscore.failing-plugin',
            name: 'Failing Plugin',
            version: '1.0.0',
            providers: [DummyFailingServiceProvider::class]
        );

        // Directly store as ACTIVE to simulate an active plugin that fails during app boot
        $plugin = PluginRecord::create([
            'id' => 'lcmscore.failing-plugin',
            'name' => 'Failing Plugin',
            'version' => '1.0.0',
            'state' => PluginState::ACTIVE,
            'is_active' => true,
            'manifest' => [
                'id' => 'lcmscore.failing-plugin',
                'name' => 'Failing Plugin',
                'version' => '1.0.0',
                'providers' => [DummyFailingServiceProvider::class],
            ],
        ]);

        // Boot should catch the fatal exception, quarantine the plugin and NOT rethrow
        $this->manager->boot();

        $fresh = $plugin->fresh();
        $this->assertNotNull($fresh);
        $this->assertEquals(PluginState::ERROR, $fresh->state);
        $this->assertFalse($fresh->is_active);
        $this->assertStringContainsString('Intentional boot crash', (string) $fresh->error_message);
    }

    public function test_safe_mode_prevents_active_plugins_from_booting(): void
    {
        config(['cms.safe_mode.enabled' => true]);

        $this->createPluginOnDisk(
            id: 'lcmscore.safe-sample',
            name: 'Safe Sample',
            version: '1.0.0',
            providers: [DummyLifecycleServiceProvider::class]
        );

        PluginRecord::create([
            'id' => 'lcmscore.safe-sample',
            'name' => 'Safe Sample',
            'version' => '1.0.0',
            'state' => PluginState::ACTIVE,
            'is_active' => true,
            'manifest' => [
                'id' => 'lcmscore.safe-sample',
                'name' => 'Safe Sample',
                'version' => '1.0.0',
                'providers' => [DummyLifecycleServiceProvider::class],
            ],
        ]);

        $this->manager->boot();

        // Under Safe Mode, dummy provider must NOT have been executed
        $this->assertNull(config('cms.test_dummy_loaded'));
    }

    /**
     * @param  list<string>  $providers
     */
    private function createPluginOnDisk(string $id, string $name, string $version, array $providers = []): void
    {
        $slug = explode('.', $id)[1] ?? $id;
        $dir = $this->tempPluginsDir.'/'.$slug;
        mkdir($dir, 0777, true);

        file_put_contents($dir.'/plugin.json', json_encode([
            'id' => $id,
            'name' => $name,
            'version' => $version,
            'providers' => $providers,
        ]));
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
