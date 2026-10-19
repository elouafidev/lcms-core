<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPluginDetailTest extends TestCase
{
    use RefreshDatabase;

    private string $tempPluginsDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPluginsDir = sys_get_temp_dir().'/cms_admin_detail_'.uniqid();
        mkdir($this->tempPluginsDir, 0777, true);
        config(['cms.plugins.path' => $this->tempPluginsDir]);

        // Create base plugin
        $this->createTestPlugin('acme.base-plugin', [
            'id' => 'acme.base-plugin',
            'name' => 'Acme Base Plugin',
            'version' => '1.2.0',
            'description' => 'A base foundation plugin for testing.',
            'author' => [
                'name' => 'Acme Corp',
                'email' => 'dev@acme.test',
                'homepage' => 'https://acme.test',
            ],
            'compatibility' => [
                'php' => '^8.2',
                'lcms' => '^1.0',
            ],
            'providers' => [],
            'dependencies' => [],
            'permissions' => [
                ['key' => 'base.view', 'label' => 'View Base Data'],
            ],
            'routes' => [
                'web' => 'routes/web.php',
            ],
        ]);

        // Create dependent plugin that depends on acme.base-plugin
        $this->createTestPlugin('acme.dependent-plugin', [
            'id' => 'acme.dependent-plugin',
            'name' => 'Acme Dependent Plugin',
            'version' => '2.0.0',
            'description' => 'An extension requiring the base plugin.',
            'author' => ['name' => 'Partner Team'],
            'providers' => [],
            'dependencies' => [
                'acme.base-plugin' => '^1.0',
            ],
        ]);

        // Create plugin with missing dependency to test resolution display
        $this->createTestPlugin('acme.broken-deps-plugin', [
            'id' => 'acme.broken-deps-plugin',
            'name' => 'Acme Broken Deps Plugin',
            'version' => '1.0.0',
            'description' => 'An extension with a missing dependency.',
            'author' => ['name' => 'Dev Team'],
            'providers' => [],
            'dependencies' => [
                'acme.base-plugin' => '^1.0',
                'acme.missing-plugin' => '^1.0',
            ],
        ]);
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

    public function test_guest_is_redirected_to_login_when_accessing_plugin_detail(): void
    {
        $response = $this->get('/admin/plugins/acme.base-plugin');
        $response->assertRedirect('/login');
    }

    public function test_returns_404_for_non_existent_plugin(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/plugins/non.existent-plugin');
        $response->assertNotFound();
    }

    public function test_admin_can_view_plugin_details_and_metadata(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/plugins/acme.base-plugin');

        $response->assertOk();
        $response->assertSee('Acme Base Plugin');
        $response->assertSee('acme.base-plugin');
        $response->assertSee('1.2.0');
        $response->assertSee('A base foundation plugin for testing.');
        $response->assertSee('Acme Corp');
        $response->assertSee('dev@acme.test');
        $response->assertSee('https://acme.test');
        $response->assertSee('php');
        $response->assertSee('^8.2');
        $response->assertSee('base.view');
        $response->assertSee('View Base Data');
        $response->assertSee('Retour à la liste des extensions');
        $response->assertSee('admin-sidebar');
    }

    public function test_shows_direct_dependencies_and_their_status(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/plugins/acme.broken-deps-plugin');

        $response->assertOk();
        $response->assertSee('acme.base-plugin');
        $response->assertSee('acme.missing-plugin');
        $response->assertSee('Manquante / Inexistante');
        $response->assertSee('Disponible non installée');
    }

    public function test_shows_reverse_dependents_and_warns_on_deactivation_when_active(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        // Activate base plugin and dependent plugin
        $this->actingAs($user)->post('/admin/plugins/acme.base-plugin/enable');
        $this->actingAs($user)->post('/admin/plugins/acme.dependent-plugin/enable');

        // Check base plugin show page
        $response = $this->actingAs($user)->get('/admin/plugins/acme.base-plugin');
        $response->assertOk();
        $response->assertSee('Acme Dependent Plugin');
        $response->assertSee('Blocage : l\'extension est active', escape: false);

        // Attempting to deactivate base-plugin without force should fail due to reverse dependency
        $disableAttempt = $this->actingAs($user)->post('/admin/plugins/acme.base-plugin/disable');
        $disableAttempt->assertSessionHas('error');
        $this->assertDatabaseHas('cms_plugins', [
            'id' => 'acme.base-plugin',
            'is_active' => 1,
            'state' => 'ACTIVE',
        ]);

        // Deactivating with force=1 should succeed
        $disableForced = $this->actingAs($user)->post('/admin/plugins/acme.base-plugin/disable', [
            'force' => 1,
        ]);
        $disableForced->assertSessionHas('success');
        $this->assertDatabaseHas('cms_plugins', [
            'id' => 'acme.base-plugin',
            'is_active' => 0,
            'state' => 'DISABLED',
        ]);
    }

    public function test_plugin_name_in_index_links_to_detail_page(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/plugins');

        $response->assertOk();
        $response->assertSee(route('admin.plugins.show', 'acme.base-plugin'));
        $response->assertSee(route('admin.plugins.show', 'acme.dependent-plugin'));
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function createTestPlugin(string $id, array $manifest): void
    {
        $pluginDir = $this->tempPluginsDir.'/'.$id;
        mkdir($pluginDir, 0777, true);

        if (! isset($manifest['autoload'])) {
            $manifest['autoload'] = [
                'psr-4' => [
                    'TestPlugin\\'.str_replace('.', '', $id).'\\' => 'src/',
                ],
            ];
        }

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
