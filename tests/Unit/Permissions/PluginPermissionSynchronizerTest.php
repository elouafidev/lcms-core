<?php

declare(strict_types=1);

namespace Tests\Unit\Permissions;

use Core\Permissions\Services\PluginPermissionSynchronizer;
use Core\PluginDiscovery\DTO\PluginManifest;
use Core\RBAC\Models\Permission;
use Core\RBAC\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginPermissionSynchronizerTest extends TestCase
{
    use RefreshDatabase;

    private PluginPermissionSynchronizer $synchronizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->synchronizer = app(PluginPermissionSynchronizer::class);
    }

    public function test_sync_plugin_persists_declared_permissions_in_database(): void
    {
        $manifest = new PluginManifest(
            id: 'lcmscore.test-plugin',
            name: 'Test Plugin',
            version: '1.0.0',
            permissions: [
                [
                    'key' => 'test_plugin.manage',
                    'label' => 'Gérer le plugin de test',
                    'description' => 'Permission de gestion',
                    'group' => 'Test Plugin',
                ],
                [
                    'key' => 'test_plugin.view',
                    'label' => 'Consulter le plugin de test',
                ],
            ]
        );

        $this->synchronizer->syncPlugin($manifest);

        $this->assertDatabaseHas('permissions', [
            'slug' => 'test_plugin.manage',
            'name' => 'Gérer le plugin de test',
            'group' => 'Test Plugin',
            'plugin_id' => 'lcmscore.test-plugin',
            'description' => 'Permission de gestion',
        ]);

        $this->assertDatabaseHas('permissions', [
            'slug' => 'test_plugin.view',
            'name' => 'Consulter le plugin de test',
            'group' => 'Test Plugin',
            'plugin_id' => 'lcmscore.test-plugin',
        ]);
    }

    public function test_sync_plugin_does_not_overwrite_core_system_permissions(): void
    {
        // Core permission 'settings.site.manage' has plugin_id = null
        $corePermission = Permission::where('slug', 'settings.site.manage')->first();
        $this->assertNotNull($corePermission);
        $this->assertNull($corePermission->plugin_id);
        $originalName = $corePermission->name;

        $maliciousManifest = new PluginManifest(
            id: 'lcmscore.malicious',
            name: 'Malicious Plugin',
            version: '1.0.0',
            permissions: [
                [
                    'key' => 'settings.site.manage',
                    'label' => 'Hacked Name',
                ],
            ]
        );

        $this->synchronizer->syncPlugin($maliciousManifest);

        $corePermission->refresh();
        $this->assertSame($originalName, $corePermission->name);
        $this->assertNull($corePermission->plugin_id);
    }

    public function test_sync_plugin_removes_old_permissions_no_longer_in_manifest(): void
    {
        $v1Manifest = new PluginManifest(
            id: 'lcmscore.demo',
            name: 'Demo Plugin',
            version: '1.0.0',
            permissions: [
                ['key' => 'demo.perm1', 'label' => 'Perm 1'],
                ['key' => 'demo.perm2', 'label' => 'Perm 2'],
            ]
        );

        $this->synchronizer->syncPlugin($v1Manifest);
        $this->assertDatabaseHas('permissions', ['slug' => 'demo.perm1']);
        $this->assertDatabaseHas('permissions', ['slug' => 'demo.perm2']);

        // v2 removes demo.perm2
        $v2Manifest = new PluginManifest(
            id: 'lcmscore.demo',
            name: 'Demo Plugin',
            version: '2.0.0',
            permissions: [
                ['key' => 'demo.perm1', 'label' => 'Perm 1'],
            ]
        );

        $this->synchronizer->syncPlugin($v2Manifest);
        $this->assertDatabaseHas('permissions', ['slug' => 'demo.perm1']);
        $this->assertDatabaseMissing('permissions', ['slug' => 'demo.perm2']);
    }

    public function test_revoke_plugin_deletes_permissions_and_cascades_from_roles(): void
    {
        $manifest = new PluginManifest(
            id: 'lcmscore.custom',
            name: 'Custom Plugin',
            version: '1.0.0',
            permissions: [
                ['key' => 'custom.action', 'label' => 'Custom Action'],
            ]
        );

        $this->synchronizer->syncPlugin($manifest);
        $permission = Permission::where('slug', 'custom.action')->first();
        $this->assertNotNull($permission);

        // Assign permission to a custom role
        /** @var Role $role */
        $role = Role::create([
            'name' => 'Custom Role',
            'slug' => 'custom_role',
            'is_system' => false,
        ]);
        $role->permissions()->attach($permission->id);

        $this->assertDatabaseHas('permission_role', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);

        // Revoke plugin permissions
        $count = $this->synchronizer->revokePlugin('lcmscore.custom');
        $this->assertSame(1, $count);

        $this->assertDatabaseMissing('permissions', ['slug' => 'custom.action']);
        $this->assertDatabaseMissing('permission_role', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
    }

    public function test_clean_orphans_removes_permissions_for_unlisted_plugins(): void
    {
        Permission::create([
            'name' => 'Orphan Action',
            'slug' => 'orphan.action',
            'group' => 'Orphan',
            'plugin_id' => 'lcmscore.deleted_plugin',
        ]);

        $this->assertDatabaseHas('permissions', ['slug' => 'orphan.action']);

        // Clean orphans with known active plugins list not containing deleted_plugin
        $deletedCount = $this->synchronizer->cleanOrphans(['lcmscore.pages', 'lcmscore.menus']);
        $this->assertGreaterThanOrEqual(1, $deletedCount);

        $this->assertDatabaseMissing('permissions', ['slug' => 'orphan.action']);
    }
}
