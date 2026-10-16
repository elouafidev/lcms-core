<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Assets\PluginAssetManager;
use App\Core\Lifecycle\PluginMigrationManager;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginAssetManagerTest extends TestCase
{
    use RefreshDatabase;

    private Filesystem $files;

    private string $testPublicPath;

    private string $testPluginsBasePath;

    private string $testPluginPath;

    private PluginAssetManager $assetManager;

    private PluginRegistry $pluginRegistry;

    private PluginManager $pluginManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->testPublicPath = storage_path('framework/testing/public_vendor_plugins');
        $this->testPluginsBasePath = storage_path('framework/testing/plugins');
        $this->testPluginPath = $this->testPluginsBasePath.'/acme.assets-demo';

        config(['cms.plugins.path' => $this->testPluginsBasePath]);

        if ($this->files->isDirectory($this->testPublicPath)) {
            $this->files->deleteDirectory($this->testPublicPath);
        }
        if ($this->files->isDirectory($this->testPluginsBasePath)) {
            $this->files->deleteDirectory($this->testPluginsBasePath);
        }

        $this->files->makeDirectory($this->testPublicPath, 0755, true);
        $this->files->makeDirectory($this->testPluginPath.'/resources/assets/css', 0755, true);
        $this->files->makeDirectory($this->testPluginPath.'/resources/assets/img', 0755, true);

        $this->files->put($this->testPluginPath.'/resources/assets/css/style.css', 'body { background: #fff; }');
        $this->files->put($this->testPluginPath.'/resources/assets/img/icon.svg', '<svg></svg>');

        $manifestContent = json_encode([
            'id' => 'acme.assets-demo',
            'name' => 'Assets Demo Plugin',
            'version' => '1.0.0',
            'description' => 'Demo plugin with assets',
            'providers' => [],
            'dependencies' => [],
        ], JSON_THROW_ON_ERROR);
        $this->files->put($this->testPluginPath.'/plugin.json', $manifestContent);

        $this->assetManager = new PluginAssetManager(
            files: $this->files,
            publicBasePath: $this->testPublicPath
        );

        $this->app->instance(PluginAssetManager::class, $this->assetManager);

        $this->pluginRegistry = $this->app->make(PluginRegistry::class);
        $this->pluginManager = new PluginManager(
            registry: $this->pluginRegistry,
            migrationManager: $this->app->make(PluginMigrationManager::class),
            app: $this->app,
            assetManager: $this->assetManager
        );
        $this->app->instance(PluginManager::class, $this->pluginManager);
    }

    protected function tearDown(): void
    {
        if ($this->files->isDirectory($this->testPublicPath)) {
            $this->files->deleteDirectory($this->testPublicPath);
        }
        if ($this->files->isDirectory($this->testPluginsBasePath)) {
            $this->files->deleteDirectory($this->testPluginsBasePath);
        }

        parent::tearDown();
    }

    public function test_publish_copies_assets_to_destination_directory(): void
    {
        $result = $this->assetManager->publish('acme.assets-demo', $this->testPluginPath);

        $this->assertTrue($result);
        $this->assertTrue($this->assetManager->hasPublished('acme.assets-demo'));

        $publishedCss = $this->assetManager->getPublishedPath('acme.assets-demo', 'css/style.css');
        $publishedImg = $this->assetManager->getPublishedPath('acme.assets-demo', 'img/icon.svg');

        $this->assertFileExists($publishedCss);
        $this->assertFileExists($publishedImg);
        $this->assertSame('body { background: #fff; }', file_get_contents($publishedCss));
    }

    public function test_publish_with_force_overwrites_existing_published_files(): void
    {
        $this->assetManager->publish('acme.assets-demo', $this->testPluginPath);

        $publishedCss = $this->assetManager->getPublishedPath('acme.assets-demo', 'css/style.css');
        $this->files->put($publishedCss, '/* modified */');
        $this->assertSame('/* modified */', file_get_contents($publishedCss));

        $this->assetManager->publish('acme.assets-demo', $this->testPluginPath, force: true);
        $this->assertSame('body { background: #fff; }', file_get_contents($publishedCss));
    }

    public function test_publish_returns_false_when_resources_assets_does_not_exist(): void
    {
        $emptyPath = storage_path('framework/testing/empty_plugin');
        $this->files->makeDirectory($emptyPath, 0755, true);

        $result = $this->assetManager->publish('acme.empty', $emptyPath);

        $this->assertFalse($result);
        $this->assertFalse($this->assetManager->hasPublished('acme.empty'));

        $this->files->deleteDirectory($emptyPath);
    }

    public function test_url_generates_valid_absolute_asset_url(): void
    {
        $staticUrl = PluginAssetManager::url('acme.assets-demo', 'css/style.css');
        $instanceUrl = $this->assetManager->url('acme.assets-demo', 'css/style.css');

        $this->assertStringContainsString('/vendor/plugins/acme.assets-demo/css/style.css', $staticUrl);
        $this->assertSame($staticUrl, $instanceUrl);
    }

    public function test_clean_removes_published_assets(): void
    {
        $this->assetManager->publish('acme.assets-demo', $this->testPluginPath);
        $this->assertTrue($this->assetManager->hasPublished('acme.assets-demo'));

        $cleanResult = $this->assetManager->clean('acme.assets-demo');

        $this->assertTrue($cleanResult);
        $this->assertFalse($this->assetManager->hasPublished('acme.assets-demo'));
        $this->assertDirectoryDoesNotExist($this->assetManager->getPublishedPath('acme.assets-demo'));
    }

    public function test_plugin_activation_automatically_publishes_assets(): void
    {
        $this->pluginManager->install('acme.assets-demo');

        $this->assertFalse($this->assetManager->hasPublished('acme.assets-demo'));

        $this->pluginManager->activate('acme.assets-demo');

        $this->assertTrue($this->assetManager->hasPublished('acme.assets-demo'));
        $this->assertFileExists($this->assetManager->getPublishedPath('acme.assets-demo', 'css/style.css'));
    }

    public function test_plugin_uninstall_with_purge_cleans_published_assets(): void
    {
        $this->pluginManager->install('acme.assets-demo');
        $this->pluginManager->activate('acme.assets-demo');
        $this->assertTrue($this->assetManager->hasPublished('acme.assets-demo'));

        $this->pluginManager->uninstall('acme.assets-demo', purgeData: true);

        $this->assertFalse($this->assetManager->hasPublished('acme.assets-demo'));
        $this->assertNull($this->pluginRegistry->find('acme.assets-demo'));
    }

    public function test_plugin_uninstall_without_purge_preserves_published_assets(): void
    {
        $this->pluginManager->install('acme.assets-demo');
        $this->pluginManager->activate('acme.assets-demo');
        $this->assertTrue($this->assetManager->hasPublished('acme.assets-demo'));

        $this->pluginManager->uninstall('acme.assets-demo', purgeData: false);

        $this->assertTrue($this->assetManager->hasPublished('acme.assets-demo'));
        $this->assertNull($this->pluginRegistry->find('acme.assets-demo'));
    }

    public function test_artisan_publish_assets_command_publishes_specified_plugin(): void
    {
        $this->artisan('cms:plugin:publish-assets', ['id' => 'acme.assets-demo'])
            ->expectsOutputToContain('Assets for plugin \'acme.assets-demo\' published successfully')
            ->assertSuccessful();

        $this->assertTrue($this->assetManager->hasPublished('acme.assets-demo'));
    }

    public function test_artisan_publish_assets_command_with_all_flag(): void
    {
        $this->pluginManager->install('acme.assets-demo');
        $this->pluginManager->activate('acme.assets-demo');

        $this->artisan('cms:plugin:publish-assets', ['--all' => true])
            ->expectsOutputToContain('Published assets for 1 active plugin(s)')
            ->assertSuccessful();

        $this->assertTrue($this->assetManager->hasPublished('acme.assets-demo'));
    }

    public function test_artisan_publish_assets_command_fails_without_arguments_or_all(): void
    {
        $this->artisan('cms:plugin:publish-assets')
            ->expectsOutputToContain('Please specify a plugin identifier or use the --all option.')
            ->assertFailed();
    }

    public function test_artisan_publish_assets_command_fails_for_unknown_plugin(): void
    {
        $this->artisan('cms:plugin:publish-assets', ['id' => 'unknown.plugin'])
            ->expectsOutputToContain("Plugin 'unknown.plugin' not found.")
            ->assertFailed();
    }
}
