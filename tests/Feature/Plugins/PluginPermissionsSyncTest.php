<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use Core\Models\User;
use Core\PluginManager\PluginManager;
use Core\RBAC\Models\Permission;
use Core\RBAC\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginPermissionsSyncTest extends TestCase
{
    use RefreshDatabase;

    private string $tempPluginsDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->tempPluginsDir = sys_get_temp_dir().'/cms_perm_test_'.uniqid();
        mkdir($this->tempPluginsDir, 0777, true);
        config(['cms.plugins.path' => $this->tempPluginsDir]);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempPluginsDir);
        parent::tearDown();
    }

    public function test_activating_plugin_synchronizes_manifest_permissions_into_database(): void
    {
        $this->createTestPlugin('lcmscore.blog', [
            ['key' => 'blog.create', 'label' => 'Créer des articles', 'group' => 'Blog'],
            ['key' => 'blog.delete', 'label' => 'Supprimer des articles', 'group' => 'Blog'],
        ]);

        /** @var PluginManager $manager */
        $manager = app(PluginManager::class);
        $manager->install('lcmscore.blog');
        $manager->activate('lcmscore.blog');

        $this->assertDatabaseHas('permissions', [
            'slug' => 'blog.create',
            'name' => 'Créer des articles',
            'group' => 'Blog',
            'plugin_id' => 'lcmscore.blog',
        ]);

        $this->assertDatabaseHas('permissions', [
            'slug' => 'blog.delete',
            'name' => 'Supprimer des articles',
            'group' => 'Blog',
            'plugin_id' => 'lcmscore.blog',
        ]);
    }

    public function test_deactivating_plugin_removes_permissions_and_cleans_roles(): void
    {
        $this->createTestPlugin('lcmscore.shop', [
            ['key' => 'shop.orders', 'label' => 'Gérer les commandes', 'group' => 'Boutique'],
        ]);

        /** @var PluginManager $manager */
        $manager = app(PluginManager::class);
        $manager->install('lcmscore.shop');
        $manager->activate('lcmscore.shop');

        $perm = Permission::where('slug', 'shop.orders')->first();
        $this->assertNotNull($perm);

        /** @var Role $role */
        $role = Role::create([
            'name' => 'Commerçant',
            'slug' => 'merchant',
            'is_system' => false,
        ]);
        $role->permissions()->attach($perm->id);

        $this->assertDatabaseHas('permission_role', [
            'role_id' => $role->id,
            'permission_id' => $perm->id,
        ]);

        // Deactivate plugin
        $manager->deactivate('lcmscore.shop');

        // Permission and role assignment should be purged
        $this->assertDatabaseMissing('permissions', ['slug' => 'shop.orders']);
        $this->assertDatabaseMissing('permission_role', [
            'role_id' => $role->id,
            'permission_id' => $perm->id,
        ]);
    }

    public function test_role_admin_views_display_plugin_permissions_and_clean_orphans(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        // 1. Create a plugin permission
        Permission::create([
            'name' => 'Gérer la météo',
            'slug' => 'weather.manage',
            'group' => 'Météo',
            'plugin_id' => 'lcmscore.weather',
        ]);

        // Create an orphan permission for a deleted plugin
        Permission::create([
            'name' => 'Ancien plugin supprimé',
            'slug' => 'ghost.perm',
            'group' => 'Ghost',
            'plugin_id' => 'lcmscore.ghost_plugin',
        ]);

        // 2. Visit role create view
        $response = $this->actingAs($user)->get('/admin/roles/create');
        $response->assertOk();

        // Orphan permission should be purged by cleanOrphans()
        $this->assertDatabaseMissing('permissions', ['slug' => 'ghost.perm']);

        // Active plugin permission should still be there if plugin is active
        // But since lcmscore.weather is not active in this test, it was also cleaned as orphan!
        $this->assertDatabaseMissing('permissions', ['slug' => 'weather.manage']);
    }

    public function test_plugin_permissions_appear_in_role_create_when_plugin_is_active(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        // Create active plugin record and directory
        $this->createTestPlugin('lcmscore.faq', [
            ['key' => 'faq.manage', 'label' => 'Gérer la FAQ', 'group' => 'Foire aux Questions'],
        ]);

        /** @var PluginManager $manager */
        $manager = app(PluginManager::class);
        $manager->install('lcmscore.faq');
        $manager->activate('lcmscore.faq');

        $response = $this->actingAs($user)->get('/admin/roles/create');
        $response->assertOk();
        $response->assertSee('Gérer la FAQ');
        $response->assertSee('FOIRE AUX QUESTIONS');
        $response->assertSee('faq.manage');
    }

    /**
     * @param  list<array{key: string, label: string, group?: string}>  $permissions
     */
    private function createTestPlugin(string $id, array $permissions = []): void
    {
        $dir = $this->tempPluginsDir.'/'.$id;
        mkdir($dir, 0777, true);

        $manifest = [
            'id' => $id,
            'name' => ucfirst(str_replace('lcmscore.', '', $id)),
            'version' => '1.0.0',
            'description' => 'Test plugin',
            'permissions' => $permissions,
        ];

        file_put_contents($dir.'/plugin.json', json_encode($manifest, JSON_PRETTY_PRINT));
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
