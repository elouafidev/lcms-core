<?php

declare(strict_types=1);

namespace Tests\Feature\Themes;

use App\Core\Admin\NavigationRegistry;
use App\Core\Themes\ThemeAssetManager;
use App\Core\Themes\ThemeDiscovery;
use App\Core\Themes\ThemeManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminThemeTest extends TestCase
{
    use RefreshDatabase;

    private ThemeManager $themeManager;

    private ThemeDiscovery $discovery;

    private ThemeAssetManager $assetManager;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cms.plugins.path' => base_path('platform/plugins')]);
        config(['cms.themes.path' => base_path('platform/themes')]);

        $this->themeManager = $this->app->make(ThemeManager::class);
        $this->discovery = $this->themeManager->getDiscovery();
        $this->assetManager = $this->themeManager->getAssetManager();
    }

    protected function tearDown(): void
    {
        if ($this->themeManager->hasActiveTheme()) {
            $this->themeManager->deactivate();
        }

        $this->assetManager->clean('default-modern');

        parent::tearDown();
    }

    public function test_guest_is_redirected_to_login_for_admin_themes_routes(): void
    {
        $this->get(route('admin.themes.index'))
            ->assertRedirect(route('login'));

        $this->post(route('admin.themes.activate', 'default-modern'))
            ->assertRedirect(route('login'));

        $this->post(route('admin.themes.deactivate'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_admin_can_view_themes_index_page(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.themes.index'));

        $response->assertOk();
        $response->assertViewIs('core::admin.themes.index');
        $response->assertSee('Gestion des Thèmes CMS');
        $response->assertSee('default-modern');
        $response->assertSee('Default Modern');
        $response->assertSee('Disponible');
        $response->assertSee('Activer');
    }

    public function test_admin_can_activate_theme_via_http(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.themes.activate', 'default-modern'));

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');

        $this->assertSame('default-modern', $this->themeManager->getActiveThemeId());
        $this->assertTrue($this->assetManager->hasPublished('default-modern'));

        $indexResponse = $this->actingAs($admin)->get(route('admin.themes.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Actif');
        $indexResponse->assertSee('Désactiver');
    }

    public function test_admin_can_deactivate_theme_via_http(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();

        $this->themeManager->activate('default-modern');
        $this->assertSame('default-modern', $this->themeManager->getActiveThemeId());

        $response = $this->actingAs($admin)->post(route('admin.themes.deactivate'));

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');

        $this->assertNull($this->themeManager->getActiveThemeId());

        $indexResponse = $this->actingAs($admin)->get(route('admin.themes.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Disponible');
        $indexResponse->assertSee('Activer');
    }

    public function test_activating_invalid_theme_via_http_returns_error_flash(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.themes.activate', 'non-existent-theme-xyz'));

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('error');
    }

    public function test_artisan_theme_list_command_displays_discovered_themes(): void
    {
        $this->artisan('cms:theme:list')
            ->expectsOutputToContain('default-modern')
            ->assertSuccessful();

        $this->themeManager->activate('default-modern');

        $this->artisan('cms:theme:list')
            ->expectsOutputToContain('Actif')
            ->assertSuccessful();
    }

    public function test_artisan_theme_activate_command_activates_theme_and_publishes_assets(): void
    {
        $this->artisan('cms:theme:activate', ['id' => 'default-modern'])
            ->expectsOutputToContain("Theme 'default-modern'")
            ->expectsOutputToContain('public/vendor/themes/default-modern')
            ->assertSuccessful();

        $this->assertSame('default-modern', $this->themeManager->getActiveThemeId());
        $this->assertTrue($this->assetManager->hasPublished('default-modern'));
    }

    public function test_artisan_theme_activate_fails_for_unknown_theme(): void
    {
        $this->artisan('cms:theme:activate', ['id' => 'non-existent-theme-xyz'])
            ->expectsOutputToContain("Failed to activate theme 'non-existent-theme-xyz'")
            ->assertFailed();
    }

    public function test_navigation_registry_registers_themes_management_and_sidebar_renders_it(): void
    {
        $navRegistry = $this->app->make(NavigationRegistry::class);

        $this->assertTrue($navRegistry->has('themes-management'));
        $item = $navRegistry->get('themes-management');
        $this->assertNotNull($item);
        $this->assertSame('Gestion des Thèmes', $item->getLabel());
        $this->assertSame('/admin/themes', $item->getUrl());

        /** @var User $admin */
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.plugins.index'));
        $response->assertOk();
        $response->assertSee('Gestion des Thèmes');
        $response->assertSee(route('admin.themes.index'));
    }
}
