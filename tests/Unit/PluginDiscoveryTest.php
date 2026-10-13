<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\PluginDiscovery\Autoload\PluginClassLoader;
use App\Core\PluginDiscovery\DTO\DiscoveredPlugin;
use App\Core\PluginDiscovery\ManifestValidator;
use App\Core\PluginDiscovery\PluginDiscovery;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PluginDiscoveryTest extends TestCase
{
    private string $tempPluginsDir;

    private PluginClassLoader $classLoader;

    private PluginDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPluginsDir = sys_get_temp_dir().'/cms_test_plugins_'.uniqid();
        mkdir($this->tempPluginsDir, 0777, true);

        $this->classLoader = new PluginClassLoader;
        $validator = new ManifestValidator;
        $this->discovery = new PluginDiscovery($validator, $this->classLoader);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempPluginsDir);
        parent::tearDown();
    }

    public function test_returns_empty_collection_when_directory_does_not_exist(): void
    {
        $result = $this->discovery->discover($this->tempPluginsDir.'/non_existent');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_discovers_valid_plugin_and_registers_psr4_mapping(): void
    {
        $pluginDir = $this->tempPluginsDir.'/demo-hello';
        mkdir($pluginDir.'/src', 0777, true);

        file_put_contents($pluginDir.'/plugin.json', json_encode([
            'id' => 'acme.demo-hello',
            'name' => 'Acme Demo Hello',
            'version' => '1.0.0',
            'autoload' => [
                'psr-4' => [
                    'Plugins\\DemoHello\\' => 'src/',
                ],
            ],
        ]));

        $discovered = $this->discovery->discover($this->tempPluginsDir);

        $this->assertTrue($discovered->has('acme.demo-hello'));

        /** @var DiscoveredPlugin $plugin */
        $plugin = $discovered->get('acme.demo-hello');
        $this->assertInstanceOf(DiscoveredPlugin::class, $plugin);
        $this->assertEquals('acme.demo-hello', $plugin->getManifest()->getId());
        $this->assertEquals('AVAILABLE', $plugin->getState());
        $this->assertFalse($plugin->isActive());
        $this->assertEquals(str_replace('\\', '/', $pluginDir), $plugin->getPath());

        // Verify PSR-4 registration
        $registered = $this->classLoader->getRegisteredPrefixes();
        $this->assertArrayHasKey('Plugins\\DemoHello\\', $registered);
        $this->assertStringContainsString('/src/', $registered['Plugins\\DemoHello\\'][0]);
    }

    public function test_skips_invalid_plugin_manifests_without_crashing(): void
    {
        // 1. Valid plugin
        $validDir = $this->tempPluginsDir.'/valid-plugin';
        mkdir($validDir, 0777, true);
        file_put_contents($validDir.'/plugin.json', json_encode([
            'id' => 'acme.valid-plugin',
            'name' => 'Valid Plugin',
            'version' => '1.0.0',
        ]));

        // 2. Corrupted JSON plugin
        $corruptDir = $this->tempPluginsDir.'/corrupt-plugin';
        mkdir($corruptDir, 0777, true);
        file_put_contents($corruptDir.'/plugin.json', '{invalid json syntax}');

        // 3. Path traversal attempt plugin
        $traversalDir = $this->tempPluginsDir.'/traversal-plugin';
        mkdir($traversalDir, 0777, true);
        file_put_contents($traversalDir.'/plugin.json', json_encode([
            'id' => 'acme.traversal-plugin',
            'name' => 'Traversal Plugin',
            'version' => '1.0.0',
            'routes' => [
                'web' => '../sensitive.php',
            ],
        ]));

        $discovered = $this->discovery->discover($this->tempPluginsDir);

        $this->assertCount(1, $discovered);
        $this->assertTrue($discovered->has('acme.valid-plugin'));
        $this->assertFalse($discovered->has('acme.traversal-plugin'));
    }

    public function test_discovers_nested_vendor_structure_and_skips_empty_folders(): void
    {
        // Empty folder
        mkdir($this->tempPluginsDir.'/empty-folder', 0777, true);

        // Nested vendor folder: platform/plugins/acme/nested-tool/plugin.json
        $nestedPluginDir = $this->tempPluginsDir.'/acme/nested-tool';
        mkdir($nestedPluginDir, 0777, true);
        file_put_contents($nestedPluginDir.'/plugin.json', json_encode([
            'id' => 'acme.nested-tool',
            'name' => 'Acme Nested Tool',
            'version' => '2.0.0',
        ]));

        $discovered = $this->discovery->discover($this->tempPluginsDir);

        $this->assertCount(1, $discovered);
        $this->assertTrue($discovered->has('acme.nested-tool'));
    }

    public function test_dynamic_autoloading_instantiates_class_from_discovered_plugin(): void
    {
        $pluginDir = $this->tempPluginsDir.'/autoload-test';
        mkdir($pluginDir.'/src', 0777, true);

        file_put_contents($pluginDir.'/plugin.json', json_encode([
            'id' => 'test.autoload-sample',
            'name' => 'Autoload Sample',
            'version' => '1.0.0',
            'autoload' => [
                'psr-4' => [
                    'Plugins\\TestAutoloadSample\\' => 'src/',
                ],
            ],
        ]));

        // Create a dummy PHP class inside the plugin's src folder
        $classContent = <<<'PHP'
<?php

declare(strict_types=1);

namespace Plugins\TestAutoloadSample;

class Greeter
{
    public function greet(): string
    {
        return 'Hello from discovered plugin!';
    }
}
PHP;
        file_put_contents($pluginDir.'/src/Greeter.php', $classContent);

        $this->discovery->discover($this->tempPluginsDir);

        // Instantiate dynamically via autoloading
        $className = 'Plugins\\TestAutoloadSample\\Greeter';
        $this->assertTrue(class_exists($className), "Expected class {$className} to be autoloadable.");

        $instance = new $className;
        $this->assertEquals('Hello from discovered plugin!', $instance->greet());
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
