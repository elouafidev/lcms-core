<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\PluginManager\PluginManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUnifiedLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private PluginManager $pluginManager;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cms.plugins.path' => base_path('platform/plugins')]);
        config(['cms.themes.path' => base_path('platform/themes')]);

        /** @var User $admin */
        $admin = User::factory()->create();
        $this->admin = $admin;

        $this->pluginManager = $this->app->make(PluginManager::class);

        // Ensure plugins are active so their admin routes are mounted
        foreach (['acme.menus', 'acme.pages', 'acme.seo', 'acme.media-library'] as $pluginId) {
            if ($this->pluginManager->getRegistry()->getDiscovered($pluginId) !== null) {
                if (! $this->pluginManager->getRegistry()->isInstalled($pluginId)) {
                    $this->pluginManager->install($pluginId);
                }
                if (! $this->pluginManager->getRegistry()->isActive($pluginId)) {
                    $this->pluginManager->activate($pluginId);
                }
            }
        }
    }

    public function test_admin_sidebar_is_displayed_on_core_plugin_management(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.plugins.index'));

        $response->assertOk();
        $response->assertSee('admin-sidebar', false);
        $response->assertSee('Gestion des Plugins');
        $response->assertSee('Gestion des Thèmes');
    }

    public function test_admin_sidebar_is_displayed_on_core_theme_management(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.themes.index'));

        $response->assertOk();
        $response->assertSee('admin-sidebar', false);
        $response->assertSee('Gestion des Plugins');
        $response->assertSee('Gestion des Thèmes');
    }

    public function test_admin_sidebar_is_displayed_on_navigation_menus_plugin_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/menus');

        $response->assertOk();
        $response->assertSee('admin-sidebar', false);
        $response->assertSee('Menus de Navigation');
        $response->assertSee('Gestion des Plugins');
        $response->assertSee('Gestion des Thèmes');
    }

    public function test_admin_sidebar_is_displayed_on_static_pages_plugin_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/pages');

        $response->assertOk();
        $response->assertSee('admin-sidebar', false);
        $response->assertSee('Pages Statiques');
        $response->assertSee('Gestion des Plugins');
    }

    public function test_admin_sidebar_is_displayed_on_seo_plugin_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/seo');

        $response->assertOk();
        $response->assertSee('admin-sidebar', false);
        $response->assertSee('Configuration SEO');
        $response->assertSee('Gestion des Plugins');
    }

    public function test_admin_sidebar_is_displayed_on_media_library_plugin_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/media');

        $response->assertOk();
        $response->assertSee('admin-sidebar', false);
        $response->assertSee('Médiathèque');
        $response->assertSee('Gestion des Plugins');
    }
}
