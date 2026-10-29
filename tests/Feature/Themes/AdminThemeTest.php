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

        $tempThemePath = base_path('platform/themes/temp-delete-theme');
        if (is_dir($tempThemePath)) {
            $this->deleteDirectory($tempThemePath);
        }

        parent::tearDown();
    }

    public function test_guest_is_redirected_to_login_for_admin_themes_routes(): void
    {
        $this->get(route('admin.themes.index'))
            ->assertRedirect(route('login'));

        $this->post(route('admin.themes.upload'))
            ->assertRedirect(route('login'));

        $this->post(route('admin.themes.activate', 'default-modern'))
            ->assertRedirect(route('login'));

        $this->post(route('admin.themes.deactivate'))
            ->assertRedirect(route('login'));

        $this->post(route('admin.themes.delete', 'default-modern'))
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
        $response->assertSee('Importer un thème (.zip)');
        $response->assertSee(route('admin.themes.upload'));
        $response->assertSee('default-modern');
        $response->assertSee('Default Modern');
        $response->assertSee('Disponible');
        $response->assertSee('Activer');
        $response->assertSee('Supprimer');
        $response->assertSee(route('admin.themes.delete', 'default-modern'));
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
        $indexResponse->assertDontSee(route('admin.themes.delete', 'default-modern'));
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

    public function test_admin_can_delete_inactive_theme_via_http(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();

        $tempThemePath = base_path('platform/themes/temp-delete-theme');
        mkdir($tempThemePath, 0777, true);
        file_put_contents($tempThemePath.'/theme.json', (string) json_encode([
            'id' => 'temp-delete-theme',
            'name' => 'Temp Delete Theme',
            'version' => '1.0.0',
        ]));

        $this->discovery->discover();
        $this->assertTrue($this->discovery->has('temp-delete-theme'));

        $response = $this->actingAs($admin)->post(route('admin.themes.delete', 'temp-delete-theme'));

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');

        $this->assertDirectoryDoesNotExist($tempThemePath);
        $this->assertFalse($this->discovery->has('temp-delete-theme'));
    }

    public function test_admin_cannot_delete_active_theme(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();

        $this->themeManager->activate('default-modern');
        $this->assertSame('default-modern', $this->themeManager->getActiveThemeId());

        $themePath = base_path('platform/themes/default-modern');
        $this->assertDirectoryExists($themePath);

        $response = $this->actingAs($admin)->post(route('admin.themes.delete', 'default-modern'));

        $response->assertSessionHas('error');
        $this->assertDirectoryExists($themePath);
    }

    public function test_deleting_non_existent_theme_returns_error(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.themes.delete', 'non-existent-theme-xyz'));

        $response->assertSessionHas('error');
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

    public function test_admin_can_publish_theme_assets_via_http(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.themes.publish-assets', 'default-modern'));

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');
        $this->assertTrue($this->assetManager->hasPublished('default-modern'));
    }

    public function test_admin_can_publish_all_theme_assets_via_http(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.themes.publish-all-assets'));

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');
        $this->assertTrue($this->assetManager->hasPublished('default-modern'));
    }

    public function test_theme_details_modal_and_actions_are_rendered_in_index(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.themes.index'));
        $response->assertOk();
        $response->assertSee('themeModal-default-modern');
        $response->assertSee(route('admin.themes.publish-assets', 'default-modern'));
        $response->assertSee(route('admin.themes.publish-all-assets'));
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
