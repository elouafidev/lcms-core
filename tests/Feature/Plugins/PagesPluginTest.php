<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Core\Admin\NavigationRegistry;
use App\Core\Lifecycle\PluginState;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Plugins\Pages\Models\Page;
use Tests\TestCase;

class PagesPluginTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $pluginManager;

    private PluginRegistry $pluginRegistry;

    private NavigationRegistry $navigationRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginManager = $this->app->make(PluginManager::class);
        $this->pluginRegistry = $this->app->make(PluginRegistry::class);
        $this->navigationRegistry = $this->app->make(NavigationRegistry::class);

        // Ensure plugins path points to native platform/plugins directory
        config(['cms.plugins.path' => base_path('platform/plugins')]);
    }

    public function test_plugin_is_discoverable_and_inactive_initially(): void
    {
        $discovered = $this->pluginRegistry->getDiscovered('acme.pages');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_pages'));

        // Public route should not be available
        $this->get('/page/about-us')->assertNotFound();
    }

    public function test_install_and_activation_creates_table_and_navigation(): void
    {
        $this->pluginManager->install('acme.pages');
        $this->pluginManager->activate('acme.pages');

        $this->assertTrue(Schema::hasTable('plugin_pages'));

        // Navigation registry check
        $this->assertTrue($this->navigationRegistry->has('pages-management'));
        $item = $this->navigationRegistry->get('pages-management');
        $this->assertNotNull($item);
        $this->assertSame('Pages Statiques', $item->getLabel());
        $this->assertSame('/admin/pages', $item->getUrl());
    }

    public function test_published_page_returns_200_with_rendered_content(): void
    {
        $this->pluginManager->install('acme.pages');
        $this->pluginManager->activate('acme.pages');

        Page::query()->create([
            'title' => 'À Propos de Nous',
            'slug' => 'about-us',
            'content' => '<p>Bienvenue sur notre site!</p>',
            'status' => 'published',
            'is_published' => true,
        ]);

        $response = $this->get('/page/about-us');

        $response->assertOk();
        $response->assertSee('À Propos de Nous');
        $response->assertSee('Bienvenue sur notre site!');
    }

    public function test_draft_page_returns_404_on_public_route(): void
    {
        $this->pluginManager->install('acme.pages');
        $this->pluginManager->activate('acme.pages');

        Page::query()->create([
            'title' => 'Page Brouillon',
            'slug' => 'draft-preview',
            'content' => '<p>Contenu secret en cours de rédaction</p>',
            'status' => 'draft',
            'is_published' => false,
        ]);

        $response = $this->get('/page/draft-preview');

        $response->assertNotFound();
    }

    public function test_non_existent_page_slug_returns_404(): void
    {
        $this->pluginManager->install('acme.pages');
        $this->pluginManager->activate('acme.pages');

        $response = $this->get('/page/non-existent-page-slug');

        $response->assertNotFound();
    }

    public function test_guest_is_redirected_to_login_on_admin_pages(): void
    {
        $this->pluginManager->install('acme.pages');
        $this->pluginManager->activate('acme.pages');

        $response = $this->get('/admin/pages');
        $response->assertRedirect('/login');

        $responsePost = $this->post('/admin/pages', ['title' => 'Test']);
        $responsePost->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_admin_pages_list(): void
    {
        $this->pluginManager->install('acme.pages');
        $this->pluginManager->activate('acme.pages');

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/pages');

        $response->assertOk();
        $response->assertSee('Pages Statiques');
    }

    public function test_authenticated_user_can_create_page_via_http(): void
    {
        $this->pluginManager->install('acme.pages');
        $this->pluginManager->activate('acme.pages');

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/pages', [
            'title' => 'Nos Services',
            'slug' => 'nos-services',
            'content' => 'Découvrez toutes nos offres de services informatiques.',
            'status' => 'published',
            'is_published' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.title', 'Nos Services');
        $response->assertJsonPath('data.slug', 'nos-services');

        $this->assertDatabaseHas('plugin_pages', [
            'title' => 'Nos Services',
            'slug' => 'nos-services',
            'status' => 'published',
            'is_published' => true,
        ]);

        // Verify public route is accessible immediately
        $publicResponse = $this->get('/page/nos-services');
        $publicResponse->assertOk();
        $publicResponse->assertSee('Nos Services');
    }

    public function test_authenticated_user_can_delete_page_via_http(): void
    {
        $this->pluginManager->install('acme.pages');
        $this->pluginManager->activate('acme.pages');

        /** @var Page $page */
        $page = Page::query()->create([
            'title' => 'Page Temporaire',
            'slug' => 'temp-page',
            'content' => 'Bientôt effacée',
            'status' => 'published',
            'is_published' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson('/admin/pages/'.$page->id);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('plugin_pages', ['id' => $page->id]);
    }

    public function test_deactivation_hides_routes_and_navigation_while_preserving_database_data(): void
    {
        $this->pluginManager->install('acme.pages');
        $this->pluginManager->activate('acme.pages');

        Page::query()->create([
            'title' => 'Politique de Confidentialité',
            'slug' => 'privacy-policy',
            'content' => 'Texte de confidentialité',
            'status' => 'published',
            'is_published' => true,
        ]);

        // Verify public route works before deactivation
        $this->get('/page/privacy-policy')->assertOk();

        // Deactivate plugin
        $this->pluginManager->deactivate('acme.pages');

        // Navigation should be hidden
        $this->assertFalse($this->navigationRegistry->has('pages-management'));

        // Public route should be unavailable (404)
        $this->get('/page/privacy-policy')->assertNotFound();

        // Data must be strictly preserved
        $this->assertTrue(Schema::hasTable('plugin_pages'));
        $this->assertDatabaseHas('plugin_pages', [
            'slug' => 'privacy-policy',
            'title' => 'Politique de Confidentialité',
        ]);
    }

    public function test_uninstallation_with_purge_drops_table_and_cleans_record(): void
    {
        $this->pluginManager->install('acme.pages');
        $this->pluginManager->activate('acme.pages');

        $this->assertTrue(Schema::hasTable('plugin_pages'));

        $this->pluginManager->uninstall('acme.pages', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_pages'));
        $this->assertNull($this->pluginRegistry->find('acme.pages'));
    }
}
