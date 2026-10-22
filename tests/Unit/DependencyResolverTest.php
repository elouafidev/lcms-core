<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Contracts\PluginInterface;
use App\Core\DependencyResolver\DependencyResolver;
use App\Core\DependencyResolver\Exceptions\CircularDependencyException;
use App\Core\DependencyResolver\Exceptions\MissingDependencyException;
use App\Core\Lifecycle\PluginMigrationManager;
use App\Core\Lifecycle\PluginState;
use App\Core\PluginDiscovery\Autoload\PluginClassLoader;
use App\Core\PluginDiscovery\DTO\DiscoveredPlugin;
use App\Core\PluginDiscovery\DTO\PluginManifest;
use App\Core\PluginDiscovery\ManifestValidator;
use App\Core\PluginDiscovery\PluginDiscovery;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DependencyResolverTest extends TestCase
{
    use RefreshDatabase;

    private DependencyResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new DependencyResolver;
    }

    public function test_resolves_empty_plugins_collection(): void
    {
        $result = $this->resolver->resolve([]);
        $this->assertSame([], $result);
    }

    public function test_resolves_independent_plugins_in_deterministic_order(): void
    {
        $pluginZ = $this->createPlugin('lcmscore.zeta');
        $pluginA = $this->createPlugin('lcmscore.alpha');
        $pluginM = $this->createPlugin('lcmscore.mu');

        $resolved = $this->resolver->resolve([$pluginZ, $pluginA, $pluginM]);

        $ids = array_map(fn (PluginInterface $p): string => $p->getManifest()->getId(), $resolved);
        $this->assertSame(['lcmscore.alpha', 'lcmscore.mu', 'lcmscore.zeta'], $ids);
    }

    public function test_resolves_linear_dependency_chain(): void
    {
        // A depends on B, B depends on C, C has no dependencies
        $pluginC = $this->createPlugin('lcmscore.plugin-c', []);
        $pluginB = $this->createPlugin('lcmscore.plugin-b', ['lcmscore.plugin-c' => '^1.0']);
        $pluginA = $this->createPlugin('lcmscore.plugin-a', ['lcmscore.plugin-b' => '^1.0']);

        // Feed in shuffled/arbitrary order: [A, C, B]
        $resolved = $this->resolver->resolve([$pluginA, $pluginC, $pluginB]);

        $ids = array_map(fn (PluginInterface $p): string => $p->getManifest()->getId(), $resolved);

        // Required topological order: C must be loaded before B, and B before A
        $this->assertSame(['lcmscore.plugin-c', 'lcmscore.plugin-b', 'lcmscore.plugin-a'], $ids);
    }

    public function test_resolves_diamond_dependency_graph(): void
    {
        // Graph:
        //       A
        //      / \
        //     B   C
        //      \ /
        //       D
        $pluginA = $this->createPlugin('lcmscore.plugin-a', []);
        $pluginB = $this->createPlugin('lcmscore.plugin-b', ['lcmscore.plugin-a' => '^1.0']);
        $pluginC = $this->createPlugin('lcmscore.plugin-c', ['lcmscore.plugin-a' => '^1.0']);
        $pluginD = $this->createPlugin('lcmscore.plugin-d', [
            'lcmscore.plugin-b' => '^1.0',
            'lcmscore.plugin-c' => '^1.0',
        ]);

        $resolved = $this->resolver->resolve([$pluginD, $pluginB, $pluginA, $pluginC]);
        $ids = array_map(fn (PluginInterface $p): string => $p->getManifest()->getId(), $resolved);

        // A must be first, D must be last
        $this->assertSame('lcmscore.plugin-a', $ids[0]);
        $this->assertSame('lcmscore.plugin-d', $ids[3]);
        $this->assertTrue(in_array($ids[1], ['lcmscore.plugin-b', 'lcmscore.plugin-c'], true));
        $this->assertTrue(in_array($ids[2], ['lcmscore.plugin-b', 'lcmscore.plugin-c'], true));
    }

    public function test_throws_missing_dependency_exception_when_required_dependency_is_not_present(): void
    {
        $pluginA = $this->createPlugin('lcmscore.plugin-a', ['lcmscore.non-existent' => '^2.0']);

        $this->expectException(MissingDependencyException::class);
        $this->expectExceptionMessage("Plugin 'lcmscore.plugin-a' requires missing dependency 'lcmscore.non-existent'");

        $this->resolver->resolve([$pluginA], checkMissing: true);
    }

    public function test_allows_missing_dependencies_when_check_missing_is_disabled(): void
    {
        $pluginA = $this->createPlugin('lcmscore.plugin-a', ['lcmscore.non-existent' => '^2.0']);

        $resolved = $this->resolver->resolve([$pluginA], checkMissing: false);

        $this->assertCount(1, $resolved);
        $this->assertSame('lcmscore.plugin-a', $resolved[0]->getManifest()->getId());
    }

    public function test_throws_circular_dependency_exception_on_direct_mutual_dependency(): void
    {
        // A -> B and B -> A
        $pluginA = $this->createPlugin('lcmscore.plugin-a', ['lcmscore.plugin-b' => '*']);
        $pluginB = $this->createPlugin('lcmscore.plugin-b', ['lcmscore.plugin-a' => '*']);

        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $this->resolver->resolve([$pluginA, $pluginB]);
    }

    public function test_throws_circular_dependency_exception_on_indirect_cyclic_dependency(): void
    {
        // A -> B -> C -> A
        $pluginA = $this->createPlugin('lcmscore.plugin-a', ['lcmscore.plugin-b' => '*']);
        $pluginB = $this->createPlugin('lcmscore.plugin-b', ['lcmscore.plugin-c' => '*']);
        $pluginC = $this->createPlugin('lcmscore.plugin-c', ['lcmscore.plugin-a' => '*']);

        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $this->resolver->resolve([$pluginA, $pluginB, $pluginC]);
    }

    public function test_throws_circular_dependency_exception_on_self_dependency(): void
    {
        $pluginA = $this->createPlugin('lcmscore.self-loop', ['lcmscore.self-loop' => '*']);

        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $this->resolver->resolve([$pluginA]);
    }

    public function test_plugin_manager_prevents_activation_when_circular_dependency_is_detected(): void
    {
        $tempDir = sys_get_temp_dir().'/cms_cycle_test_'.uniqid();
        mkdir($tempDir, 0777, true);
        config(['cms.plugins.path' => $tempDir]);

        $this->createPluginDir($tempDir, 'lcmscore.first-plugin', ['lcmscore.second-plugin' => '*']);
        $this->createPluginDir($tempDir, 'lcmscore.second-plugin', ['lcmscore.first-plugin' => '*']);

        $discovery = new PluginDiscovery(new ManifestValidator, new PluginClassLoader);
        $registry = new PluginRegistry($discovery);
        $manager = new PluginManager($registry, new PluginMigrationManager, $this->app, null, $this->resolver);

        // Install both plugins
        $manager->install('lcmscore.first-plugin');
        $manager->install('lcmscore.second-plugin');

        // Activation of first plugin must fail because of the cycle
        try {
            $manager->activate('lcmscore.first-plugin');
            $this->fail('Expected CircularDependencyException was not thrown during activation.');
        } catch (CircularDependencyException $e) {
            $this->assertStringContainsString('Circular dependency detected', $e->getMessage());
        }

        // Plugin state must remain INSTALLED, not ACTIVE
        $record = $registry->find('lcmscore.first-plugin');
        $this->assertNotNull($record);
        $this->assertSame(PluginState::INSTALLED, $record->state);
        $this->assertFalse($record->is_active);

        $this->deleteDirectory($tempDir);
    }

    /**
     * @param  array<string, string>  $dependencies
     */
    private function createPlugin(string $id, array $dependencies = []): PluginInterface
    {
        $manifest = new PluginManifest(
            id: $id,
            name: ucfirst($id),
            version: '1.0.0',
            dependencies: $dependencies
        );

        return new DiscoveredPlugin($manifest, '/tmp/'.$id);
    }

    /**
     * @param  array<string, string>  $dependencies
     */
    private function createPluginDir(string $baseDir, string $id, array $dependencies = []): void
    {
        $dir = $baseDir.'/'.$id;
        mkdir($dir, 0777, true);

        $manifest = [
            'id' => $id,
            'name' => ucfirst($id),
            'version' => '1.0.0',
            'description' => 'Test plugin with dependencies',
            'dependencies' => $dependencies,
            'autoload' => ['psr-4' => ['Plugins\\Test\\' => 'src/']],
            'providers' => [],
        ];

        file_put_contents(
            $dir.'/plugin.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
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
