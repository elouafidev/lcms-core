<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Admin\NavigationItem;
use App\Core\Admin\NavigationRegistry;
use App\Core\Lifecycle\PluginState;
use App\Core\Permissions\PermissionRegistry;
use App\Core\PluginDiscovery\DTO\PluginManifest;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\Models\PluginRecord;
use App\Core\PluginRegistry\PluginRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationRegistryTest extends TestCase
{
    use RefreshDatabase;

    private NavigationRegistry $navigationRegistry;

    private PermissionRegistry $permissionRegistry;

    private PluginRegistry $pluginRegistry;

    private PluginManager $pluginManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->navigationRegistry = $this->app->make(NavigationRegistry::class);
        $this->permissionRegistry = $this->app->make(PermissionRegistry::class);
        $this->pluginRegistry = $this->app->make(PluginRegistry::class);
        $this->pluginManager = $this->app->make(PluginManager::class);

        $this->navigationRegistry->flush();
        $this->permissionRegistry->flush();
    }

    public function test_navigation_registry_registers_and_sorts_items_by_priority(): void
    {
        $item1 = new NavigationItem(
            id: 'settings',
            label: 'General Settings',
            url: '/admin/settings',
            priority: 100
        );

        $item2 = NavigationItem::make(
            id: 'analytics',
            label: 'Analytics Dashboard',
            url: '/admin/analytics',
            priority: 20
        );

        $this->navigationRegistry->register($item1);
        $this->navigationRegistry->register($item2);

        $items = $this->navigationRegistry->getItems();

        $this->assertCount(2, $items);
        $this->assertSame('analytics', $items[0]->getId());
        $this->assertSame('settings', $items[1]->getId());
    }

    public function test_navigation_items_of_inactive_plugins_are_automatically_filtered_out(): void
    {
        // 1. Create one active plugin and one disabled plugin record in database
        PluginRecord::create([
            'id' => 'acme.active',
            'name' => 'Active Extension',
            'version' => '1.0.0',
            'state' => PluginState::ACTIVE,
            'is_active' => true,
            'manifest' => ['id' => 'acme.active', 'name' => 'Active Extension', 'version' => '1.0.0'],
        ]);

        PluginRecord::create([
            'id' => 'acme.inactive',
            'name' => 'Inactive Extension',
            'version' => '1.0.0',
            'state' => PluginState::DISABLED,
            'is_active' => false,
            'manifest' => ['id' => 'acme.inactive', 'name' => 'Inactive Extension', 'version' => '1.0.0'],
        ]);

        // 2. Register items belonging to each plugin
        $this->navigationRegistry->registerItem(
            id: 'active.menu',
            label: 'Active Module',
            url: '/admin/active',
            icon: '🚀',
            priority: 10,
            pluginId: 'acme.active'
        );

        $this->navigationRegistry->registerItem(
            id: 'inactive.menu',
            label: 'Inactive Module',
            url: '/admin/inactive',
            icon: '💤',
            priority: 20,
            pluginId: 'acme.inactive'
        );

        // 3. getItems() must only return the item belonging to the ACTIVE plugin
        $items = $this->navigationRegistry->getItems();

        $this->assertCount(1, $items);
        $this->assertSame('active.menu', $items[0]->getId());
        $this->assertSame('🚀', $items[0]->getIcon());
        $this->assertSame('Active Module', $items[0]->getLabel());
    }

    public function test_plugin_deactivation_purges_navigation_items_and_permissions(): void
    {
        $plugin = PluginRecord::create([
            'id' => 'acme.shop',
            'name' => 'E-Commerce Shop',
            'version' => '1.0.0',
            'state' => PluginState::ACTIVE,
            'is_active' => true,
            'manifest' => [
                'id' => 'acme.shop',
                'name' => 'E-Commerce Shop',
                'version' => '1.0.0',
                'permissions' => [
                    ['key' => 'shop.orders.view', 'label' => 'View Orders'],
                    ['key' => 'shop.orders.manage', 'label' => 'Manage Orders'],
                ],
            ],
        ]);

        // Register navigation and permissions
        $this->navigationRegistry->registerItem(
            id: 'shop.orders',
            label: 'Shop Orders',
            url: '/admin/shop/orders',
            icon: '🛒',
            pluginId: 'acme.shop'
        );

        $this->permissionRegistry->registerFromManifest($plugin->getManifest());

        $this->assertTrue($this->navigationRegistry->has('shop.orders'));
        $this->assertTrue($this->permissionRegistry->has('shop.orders.view'));
        $this->assertTrue($this->permissionRegistry->has('shop.orders.manage'));

        // Deactivate plugin
        $this->pluginManager->deactivate('acme.shop');

        // Verify items were purged
        $this->assertFalse($this->navigationRegistry->has('shop.orders'));
        $this->assertFalse($this->permissionRegistry->has('shop.orders.view'));
        $this->assertFalse($this->permissionRegistry->has('shop.orders.manage'));
    }

    public function test_permission_registry_crud_and_filtering(): void
    {
        $manifest = new PluginManifest(
            id: 'acme.blog',
            name: 'Blog Plugin',
            version: '1.2.0',
            description: 'A blogging system',
            author: ['name' => 'Acme'],
            compatibility: ['cms' => '^1.0'],
            providers: [],
            dependencies: [],
            conflicts: [],
            permissions: [
                ['key' => 'blog.posts.create', 'label' => 'Create Blog Posts'],
                ['key' => 'blog.posts.edit', 'label' => 'Edit Blog Posts'],
            ],
            routes: [],
            autoload: [],
            rawAttributes: []
        );

        $this->permissionRegistry->registerFromManifest($manifest);

        $this->assertTrue($this->permissionRegistry->has('blog.posts.create'));
        $this->assertTrue($this->permissionRegistry->has('blog.posts.edit'));
        $this->assertFalse($this->permissionRegistry->has('unknown.permission'));

        $perm = $this->permissionRegistry->get('blog.posts.create');
        $this->assertNotNull($perm);
        $this->assertSame('Create Blog Posts', $perm['label']);
        $this->assertSame('acme.blog', $perm['plugin_id']);

        $blogPerms = $this->permissionRegistry->getByPlugin('acme.blog');
        $this->assertCount(2, $blogPerms);

        $this->permissionRegistry->unregister('blog.posts.create');
        $this->assertFalse($this->permissionRegistry->has('blog.posts.create'));
        $this->assertTrue($this->permissionRegistry->has('blog.posts.edit'));
    }

    public function test_sidebar_displays_active_plugin_navigation_item_and_hides_when_disabled(): void
    {
        // 1. Prepare active plugin
        $record = PluginRecord::create([
            'id' => 'acme.crm',
            'name' => 'CRM Plugin',
            'version' => '1.0.0',
            'state' => PluginState::ACTIVE,
            'is_active' => true,
            'manifest' => ['id' => 'acme.crm', 'name' => 'CRM Plugin', 'version' => '1.0.0'],
        ]);

        // Register navigation item for CRM
        $this->navigationRegistry->registerItem(
            id: 'crm.leads',
            label: 'Prospects CRM',
            url: '/admin/crm/leads',
            icon: '👥',
            priority: 15,
            pluginId: 'acme.crm'
        );

        /** @var User $admin */
        $admin = User::factory()->create();

        // 2. Visit /admin: sidebar must display the CRM navigation item
        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('CMS Control');
        $response->assertSee('Gestion des Plugins');
        $response->assertSee('Prospects CRM');
        $response->assertSee('/admin/crm/leads');
        $response->assertSee('👥');

        // 3. Deactivate the plugin
        $this->pluginManager->deactivate('acme.crm');

        // 4. Visit /admin again: CRM navigation item must now be hidden
        $responseAfter = $this->actingAs($admin)->get('/admin');

        $responseAfter->assertOk();
        $responseAfter->assertDontSee('Prospects CRM');
        $responseAfter->assertDontSee('/admin/crm/leads');
    }
}
