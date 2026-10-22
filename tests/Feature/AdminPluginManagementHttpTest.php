<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPluginManagementHttpTest extends TestCase
{
    use RefreshDatabase;

    private string $tempPluginsDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPluginsDir = sys_get_temp_dir().'/cms_admin_http_'.uniqid();
        mkdir($this->tempPluginsDir, 0777, true);
        config(['cms.plugins.path' => $this->tempPluginsDir]);

        $this->createTestPlugin('lcmscore.sample-plugin');
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

    public function test_guest_is_redirected_to_login_when_accessing_admin_plugins(): void
    {
        $response = $this->get('/admin/plugins');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_admin_plugins_dashboard(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/plugins');

        $response->assertOk();
        $response->assertSee('Gestion des Extensions CMS');
        $response->assertSee('lcmscore.sample-plugin');
    }

    public function test_authenticated_user_can_enable_discovered_plugin(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/plugins/lcmscore.sample-plugin/enable');

        $response->assertRedirect(route('admin.plugins.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cms_plugins', [
            'id' => 'lcmscore.sample-plugin',
            'is_active' => 1,
            'state' => 'ACTIVE',
        ]);
    }

    public function test_authenticated_user_can_disable_active_plugin(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/plugins/lcmscore.sample-plugin/enable');

        $response = $this->actingAs($user)->post('/admin/plugins/lcmscore.sample-plugin/disable');

        $response->assertRedirect(route('admin.plugins.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cms_plugins', [
            'id' => 'lcmscore.sample-plugin',
            'is_active' => 0,
            'state' => 'DISABLED',
        ]);
    }

    public function test_authenticated_user_can_uninstall_plugin(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/plugins/lcmscore.sample-plugin/enable');

        $response = $this->actingAs($user)->post('/admin/plugins/lcmscore.sample-plugin/uninstall', [
            'purge_data' => 1,
        ]);

        $response->assertRedirect(route('admin.plugins.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('cms_plugins', [
            'id' => 'lcmscore.sample-plugin',
        ]);
    }

    public function test_unauthenticated_post_actions_are_redirected_to_login(): void
    {
        $response = $this->post('/admin/plugins/lcmscore.sample-plugin/enable');

        $response->assertRedirect('/login');
    }

    public function test_safe_mode_warning_is_displayed_when_safe_mode_sentinel_file_is_present(): void
    {
        $sentinel = storage_path('framework/cms_safe_mode');
        $dir = dirname($sentinel);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($sentinel, 'active');

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/plugins');

        $response->assertOk();
        $response->assertSee('MODE DE SECOURS (SAFE MODE) ACTIF');
    }

    public function test_delete_button_is_visible_on_management_and_detail_views_when_plugin_is_uninstalled(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        // 1. On index view (/admin/plugins)
        $responseIndex = $this->actingAs($user)->get('/admin/plugins');
        $responseIndex->assertOk();
        $responseIndex->assertSee(route('admin.plugins.delete', 'lcmscore.sample-plugin'));
        $responseIndex->assertSee('Supprimer');

        // 2. On detail view (/admin/plugins/{id})
        $responseDetail = $this->actingAs($user)->get('/admin/plugins/lcmscore.sample-plugin');
        $responseDetail->assertOk();
        $responseDetail->assertSee(route('admin.plugins.delete', 'lcmscore.sample-plugin'));
        $responseDetail->assertSee('Supprimer définitivement');
    }

    public function test_authenticated_user_can_delete_uninstalled_plugin_from_filesystem(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $pluginPath = $this->tempPluginsDir.'/lcmscore.sample-plugin';
        $this->assertDirectoryExists($pluginPath);

        $response = $this->actingAs($user)->post(route('admin.plugins.delete', 'lcmscore.sample-plugin'));

        $response->assertRedirect(route('admin.plugins.index'));
        $response->assertSessionHas('success');
        $this->assertDirectoryDoesNotExist($pluginPath);
    }

    public function test_cannot_delete_plugin_while_it_is_still_installed_or_active(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        // Enable plugin first
        $this->actingAs($user)->post('/admin/plugins/lcmscore.sample-plugin/enable');

        $pluginPath = $this->tempPluginsDir.'/lcmscore.sample-plugin';
        $this->assertDirectoryExists($pluginPath);

        // Attempt deletion while active/installed
        $response = $this->actingAs($user)->post(route('admin.plugins.delete', 'lcmscore.sample-plugin'));

        $response->assertSessionHas('error');
        $this->assertDirectoryExists($pluginPath);
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
