<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtisanPluginCommandsTest extends TestCase
{
    use RefreshDatabase;

    private string $tempPluginsDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPluginsDir = sys_get_temp_dir().'/cms_artisan_test_'.uniqid();
        mkdir($this->tempPluginsDir, 0777, true);
        config(['cms.plugins.path' => $this->tempPluginsDir]);

        $this->createTestPlugin('acme.sample-plugin');
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempPluginsDir);

        $sentinel = storage_path('framework/cms_safe_mode');
        if (file_exists($sentinel)) {
            unlink($sentinel);
        }

        parent::tearDown();
    }

    public function test_cms_plugin_list_command_displays_plugins_table(): void
    {
        $this->artisan('cms:plugin:list')
            ->expectsOutputToContain('Name')
            ->expectsOutputToContain('acme.sample-plugin')
            ->assertSuccessful();
    }

    public function test_cms_plugin_enable_command_installs_and_activates_plugin(): void
    {
        $this->artisan('cms:plugin:enable', ['id' => 'acme.sample-plugin'])
            ->expectsOutputToContain("Plugin 'acme.sample-plugin' has been enabled successfully.")
            ->assertSuccessful();

        $this->assertDatabaseHas('cms_plugins', [
            'id' => 'acme.sample-plugin',
            'is_active' => 1,
            'state' => 'ACTIVE',
        ]);
    }

    public function test_cms_plugin_disable_command_deactivates_active_plugin(): void
    {
        $this->artisan('cms:plugin:enable', ['id' => 'acme.sample-plugin'])
            ->assertSuccessful();

        $this->artisan('cms:plugin:disable', ['id' => 'acme.sample-plugin'])
            ->expectsOutputToContain("Plugin 'acme.sample-plugin' has been disabled successfully.")
            ->assertSuccessful();

        $this->assertDatabaseHas('cms_plugins', [
            'id' => 'acme.sample-plugin',
            'is_active' => 0,
            'state' => 'DISABLED',
        ]);
    }

    public function test_cms_plugin_enable_command_fails_for_unknown_plugin(): void
    {
        $this->artisan('cms:plugin:enable', ['id' => 'unknown.nonexistent-plugin'])
            ->expectsOutputToContain("Plugin 'unknown.nonexistent-plugin' not found.")
            ->assertFailed();
    }

    public function test_cms_plugin_disable_command_fails_for_uninstalled_plugin(): void
    {
        $this->artisan('cms:plugin:disable', ['id' => 'not.installed-plugin'])
            ->expectsOutputToContain("Plugin 'not.installed-plugin' is not installed.")
            ->assertFailed();
    }

    public function test_cms_recovery_safe_mode_on_off_and_status(): void
    {
        $sentinel = storage_path('framework/cms_safe_mode');

        // Initially inactive
        $this->artisan('cms:recovery:safe-mode')
            ->expectsOutputToContain('Emergency Safe Mode is currently INACTIVE.')
            ->assertSuccessful();

        // Enable safe mode
        $this->artisan('cms:recovery:safe-mode', ['--on' => true])
            ->expectsOutputToContain('Emergency Safe Mode is now ENABLED.')
            ->assertSuccessful();

        $this->assertFileExists($sentinel);

        // Status should now be active
        $this->artisan('cms:recovery:safe-mode')
            ->expectsOutputToContain('Emergency Safe Mode is currently ACTIVE.')
            ->assertSuccessful();

        // Disable safe mode
        $this->artisan('cms:recovery:safe-mode', ['--off' => true])
            ->expectsOutputToContain('Emergency Safe Mode is now DISABLED.')
            ->assertSuccessful();

        $this->assertFileDoesNotExist($sentinel);

        // Status should now be inactive again
        $this->artisan('cms:recovery:safe-mode')
            ->expectsOutputToContain('Emergency Safe Mode is currently INACTIVE.')
            ->assertSuccessful();
    }

    private function createTestPlugin(string $id): void
    {
        $pluginDir = $this->tempPluginsDir.'/'.$id;
        mkdir($pluginDir, 0777, true);

        $manifest = [
            'id' => $id,
            'name' => 'Sample Plugin',
            'version' => '1.0.0',
            'description' => 'A test sample plugin',
            'autoload' => [
                'psr-4' => [
                    'Plugins\\Sample\\' => 'src/',
                ],
            ],
            'providers' => [],
        ];

        file_put_contents(
            $pluginDir.'/plugin.json',
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
