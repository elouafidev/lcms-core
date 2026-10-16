<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Core\Admin\NavigationRegistry;
use App\Core\Hooks\HookManager;
use App\Core\Lifecycle\PluginState;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Plugins\Menus\Models\Menu;
use Plugins\Menus\Models\MenuItem;
use Plugins\Menus\Services\MenuRenderer;
use Tests\TestCase;

class MenusPluginTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $pluginManager;

    private PluginRegistry $pluginRegistry;

    private NavigationRegistry $navigationRegistry;

    private HookManager $hookManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginManager = $this->app->make(PluginManager::class);
        $this->pluginRegistry = $this->app->make(PluginRegistry::class);
        $this->navigationRegistry = $this->app->make(NavigationRegistry::class);
        $this->hookManager = $this->app->make(HookManager::class);

        // Ensure plugins path points to native platform/plugins directory
        config(['cms.plugins.path' => base_path('platform/plugins')]);
    }

    public function test_plugin_is_discoverable_and_inactive_initially(): void
    {
        $discovered = $this->pluginRegistry->getDiscovered('acme.menus');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_menus'));
        $this->assertFalse(Schema::hasTable('plugin_menu_items'));

        /** @var MenuRenderer $renderer */
        $renderer = $this->app->make(MenuRenderer::class);
        $this->assertSame('', $renderer->render('main-nav'));
    }

    public function test_install_and_activation_creates_tables_and_navigation(): void
    {
        $this->pluginManager->install('acme.menus');
        $this->pluginManager->activate('acme.menus');

        $this->assertTrue(Schema::hasTable('plugin_menus'));
        $this->assertTrue(Schema::hasTable('plugin_menu_items'));

        // Navigation registry check
        $this->assertTrue($this->navigationRegistry->has('menus-management'));
        $item = $this->navigationRegistry->get('menus-management');
        $this->assertNotNull($item);
        $this->assertSame('Menus de Navigation', $item->getLabel());
        $this->assertSame('/admin/menus', $item->getUrl());
    }

    public function test_menu_renderer_renders_hierarchical_html_for_active_menu(): void
    {
        $this->pluginManager->install('acme.menus');
        $this->pluginManager->activate('acme.menus');

        /** @var Menu $menu */
        $menu = Menu::query()->create([
            'name' => 'Menu Principal',
            'slug' => 'main-nav',
            'description' => 'Navigation principale du header',
            'is_active' => true,
        ]);

        MenuItem::query()->create([
            'menu_id' => $menu->id,
            'title' => 'Accueil',
            'url' => '/',
            'order' => 10,
            'is_active' => true,
        ]);

        MenuItem::query()->create([
            'menu_id' => $menu->id,
            'title' => 'Services',
            'url' => '/services',
            'order' => 20,
            'is_active' => true,
        ]);

        MenuItem::query()->create([
            'menu_id' => $menu->id,
            'title' => 'Contact',
            'url' => '/contact',
            'order' => 30,
            'is_active' => true,
        ]);

        /** @var MenuRenderer $renderer */
        $renderer = $this->app->make(MenuRenderer::class);
        $html = $renderer->render('main-nav');

        $this->assertStringContainsString('<nav class="menu-nav menu-main-nav">', $html);
        $this->assertStringContainsString('<ul class="menu-list">', $html);
        $this->assertStringContainsString('<a href="/" target="_self">Accueil</a>', $html);
        $this->assertStringContainsString('<a href="/services" target="_self">Services</a>', $html);
        $this->assertStringContainsString('<a href="/contact" target="_self">Contact</a>', $html);
    }

    public function test_menu_renderer_handles_nested_menu_items(): void
    {
        $this->pluginManager->install('acme.menus');
        $this->pluginManager->activate('acme.menus');

        /** @var Menu $menu */
        $menu = Menu::query()->create([
            'name' => 'Header Nav',
            'slug' => 'header-nav',
            'is_active' => true,
        ]);

        /** @var MenuItem $parent */
        $parent = MenuItem::query()->create([
            'menu_id' => $menu->id,
            'title' => 'Nos Solutions',
            'url' => '/solutions',
            'order' => 10,
            'is_active' => true,
        ]);

        /** @var MenuItem $child */
        $child = MenuItem::query()->create([
            'menu_id' => $menu->id,
            'parent_id' => $parent->id,
            'title' => 'Développement Cloud',
            'url' => '/solutions/cloud',
            'order' => 10,
            'is_active' => true,
        ]);

        MenuItem::query()->create([
            'menu_id' => $menu->id,
            'parent_id' => $child->id,
            'title' => 'Architectures Kubernetes',
            'url' => '/solutions/cloud/k8s',
            'order' => 10,
            'is_active' => true,
        ]);

        /** @var MenuRenderer $renderer */
        $renderer = $this->app->make(MenuRenderer::class);
        $html = $renderer->render('header-nav');

        $this->assertStringContainsString('menu-item has-children', $html);
        $this->assertStringContainsString('<a href="/solutions" target="_self">Nos Solutions</a>', $html);
        $this->assertStringContainsString('<ul class="sub-menu">', $html);
        $this->assertStringContainsString('<a href="/solutions/cloud" target="_self">Développement Cloud</a>', $html);
        $this->assertStringContainsString('<a href="/solutions/cloud/k8s" target="_self">Architectures Kubernetes</a>', $html);
    }

    public function test_menu_renderer_respects_custom_options(): void
    {
        $this->pluginManager->install('acme.menus');
        $this->pluginManager->activate('acme.menus');

        Menu::query()->create([
            'name' => 'Custom Nav',
            'slug' => 'custom-nav',
            'is_active' => true,
        ]);

        /** @var MenuRenderer $renderer */
        $renderer = $this->app->make(MenuRenderer::class);
        $html = $renderer->render('custom-nav', [
            'class' => 'navbar navbar-expand-lg bg-light',
            'id' => 'site-main-navbar',
        ]);

        $this->assertStringContainsString('id="site-main-navbar"', $html);
        $this->assertStringContainsString('class="navbar navbar-expand-lg bg-light"', $html);
    }

    public function test_menu_renderer_returns_empty_string_for_non_existent_or_inactive_menu(): void
    {
        $this->pluginManager->install('acme.menus');
        $this->pluginManager->activate('acme.menus');

        Menu::query()->create([
            'name' => 'Désactivé',
            'slug' => 'disabled-menu',
            'is_active' => false,
        ]);

        /** @var MenuRenderer $renderer */
        $renderer = $this->app->make(MenuRenderer::class);

        $this->assertSame('', $renderer->render('does-not-exist'));
        $this->assertSame('', $renderer->render('disabled-menu'));
    }

    public function test_menu_renderer_returns_empty_string_when_plugin_is_deactivated(): void
    {
        $this->pluginManager->install('acme.menus');
        $this->pluginManager->activate('acme.menus');

        Menu::query()->create([
            'name' => 'Menu Actif',
            'slug' => 'active-menu',
            'is_active' => true,
        ]);

        /** @var MenuRenderer $renderer */
        $renderer = $this->app->make(MenuRenderer::class);
        $this->assertNotEmpty($renderer->render('active-menu'));

        // Deactivate plugin
        $this->pluginManager->deactivate('acme.menus');

        $this->assertSame('', $renderer->render('active-menu'));
    }

    public function test_menu_can_be_rendered_via_hook_filter(): void
    {
        $this->pluginManager->install('acme.menus');
        $this->pluginManager->activate('acme.menus');

        $menu = Menu::query()->create([
            'name' => 'Top Bar',
            'slug' => 'top-bar',
            'is_active' => true,
        ]);

        MenuItem::query()->create([
            'menu_id' => $menu->id,
            'title' => 'Blog',
            'url' => '/blog',
            'is_active' => true,
        ]);

        /** @var string $output */
        $output = $this->hookManager->applyFilter('cms.menu', '', 'top-bar');

        $this->assertStringContainsString('<a href="/blog" target="_self">Blog</a>', $output);
    }

    public function test_hook_filter_menu_rendered_allows_intercepting_markup(): void
    {
        $this->pluginManager->install('acme.menus');
        $this->pluginManager->activate('acme.menus');

        Menu::query()->create([
            'name' => 'Filter Test',
            'slug' => 'filter-test',
            'is_active' => true,
        ]);

        $this->hookManager->addFilter('menu.rendered', function (string $html, string $slug): string {
            return $html.'<!-- intercepted: '.$slug.' -->';
        });

        /** @var MenuRenderer $renderer */
        $renderer = $this->app->make(MenuRenderer::class);
        $html = $renderer->render('filter-test');

        $this->assertStringContainsString('<!-- intercepted: filter-test -->', $html);
    }

    public function test_guest_is_redirected_to_login_on_admin_menus(): void
    {
        $this->pluginManager->install('acme.menus');
        $this->pluginManager->activate('acme.menus');

        $response = $this->get('/admin/menus');
        $response->assertRedirect('/login');

        $responsePost = $this->post('/admin/menus', ['name' => 'Test']);
        $responsePost->assertRedirect('/login');
    }

    public function test_authenticated_user_can_create_menu_and_items_via_http(): void
    {
        $this->pluginManager->install('acme.menus');
        $this->pluginManager->activate('acme.menus');

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/menus', [
            'name' => 'Footer Navigation',
            'slug' => 'footer-nav',
            'description' => 'Navigation de bas de page',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.slug', 'footer-nav');

        $this->assertDatabaseHas('plugin_menus', [
            'slug' => 'footer-nav',
            'name' => 'Footer Navigation',
        ]);

        /** @var Menu $createdMenu */
        $createdMenu = Menu::query()->where('slug', 'footer-nav')->firstOrFail();

        $itemResponse = $this->actingAs($user)->postJson('/admin/menus/'.$createdMenu->id.'/items', [
            'title' => 'Mentions Légales',
            'url' => '/legal',
            'order' => 1,
            'is_active' => true,
        ]);

        $itemResponse->assertStatus(201);
        $itemResponse->assertJsonPath('success', true);
        $itemResponse->assertJsonPath('data.title', 'Mentions Légales');

        $this->assertDatabaseHas('plugin_menu_items', [
            'menu_id' => $createdMenu->id,
            'title' => 'Mentions Légales',
            'url' => '/legal',
        ]);
    }

    public function test_authenticated_user_can_delete_menu_and_cascade_items(): void
    {
        $this->pluginManager->install('acme.menus');
        $this->pluginManager->activate('acme.menus');

        /** @var Menu $menu */
        $menu = Menu::query()->create([
            'name' => 'Menu Éphémère',
            'slug' => 'temp-menu',
            'is_active' => true,
        ]);

        /** @var MenuItem $item */
        $item = MenuItem::query()->create([
            'menu_id' => $menu->id,
            'title' => 'Lien Éphémère',
            'url' => '/temp',
            'is_active' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson('/admin/menus/'.$menu->id);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseMissing('plugin_menus', ['id' => $menu->id]);
        $this->assertDatabaseMissing('plugin_menu_items', ['id' => $item->id]);
    }

    public function test_deactivation_hides_routes_and_navigation_while_preserving_database_data(): void
    {
        $this->pluginManager->install('acme.menus');
        $this->pluginManager->activate('acme.menus');

        /** @var Menu $menu */
        $menu = Menu::query()->create([
            'name' => 'Preserved Menu',
            'slug' => 'preserved-nav',
            'is_active' => true,
        ]);

        MenuItem::query()->create([
            'menu_id' => $menu->id,
            'title' => 'Preserved Item',
            'url' => '/preserved',
            'is_active' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        // Verify admin access works before deactivation
        $this->actingAs($user)->get('/admin/menus')->assertOk();

        // Deactivate plugin
        $this->pluginManager->deactivate('acme.menus');

        // Navigation should be hidden
        $this->assertFalse($this->navigationRegistry->has('menus-management'));

        // Admin route should return 404
        $this->actingAs($user)->get('/admin/menus')->assertNotFound();

        // Database data must be strictly preserved
        $this->assertTrue(Schema::hasTable('plugin_menus'));
        $this->assertTrue(Schema::hasTable('plugin_menu_items'));
        $this->assertDatabaseHas('plugin_menus', ['slug' => 'preserved-nav']);
        $this->assertDatabaseHas('plugin_menu_items', ['title' => 'Preserved Item']);
    }

    public function test_uninstallation_with_purge_drops_tables_and_cleans_record(): void
    {
        $this->pluginManager->install('acme.menus');
        $this->pluginManager->activate('acme.menus');

        $this->assertTrue(Schema::hasTable('plugin_menus'));
        $this->assertTrue(Schema::hasTable('plugin_menu_items'));

        $this->pluginManager->uninstall('acme.menus', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_menus'));
        $this->assertFalse(Schema::hasTable('plugin_menu_items'));
        $this->assertNull($this->pluginRegistry->find('acme.menus'));
    }
}
