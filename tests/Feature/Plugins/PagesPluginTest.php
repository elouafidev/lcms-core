<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use Core\Admin\NavigationRegistry;
use Core\Lifecycle\PluginState;
use Core\Models\User;
use Core\PluginManager\PluginManager;
use Core\PluginRegistry\PluginRegistry;
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

        // Ensure plugins path points to native Plugins directory
        config(['cms.plugins.path' => base_path('Plugins')]);
    }

    public function test_plugin_is_discoverable_and_inactive_initially(): void
    {
        $discovered = $this->pluginRegistry->getDiscovered('lcmscore.pages');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_pages'));

        // Public route should not be available
        $this->get('/page/about-us')->assertNotFound();
    }

    public function test_install_and_activation_creates_table_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

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
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

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
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

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
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        $response = $this->get('/page/non-existent-page-slug');

        $response->assertNotFound();
    }

    public function test_guest_is_redirected_to_login_on_admin_pages(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        $response = $this->get('/admin/pages');
        $response->assertRedirect('/login');

        $responsePost = $this->post('/admin/pages', ['title' => 'Test']);
        $responsePost->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_admin_pages_list(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/pages');

        $response->assertOk();
        $response->assertSee('Pages Statiques');
    }

    public function test_authenticated_user_can_create_page_via_http(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

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
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

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
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

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
        $this->pluginManager->deactivate('lcmscore.pages');

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
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        $this->assertTrue(Schema::hasTable('plugin_pages'));

        $this->pluginManager->uninstall('lcmscore.pages', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_pages'));
        $this->assertNull($this->pluginRegistry->find('lcmscore.pages'));
    }

    public function test_authenticated_user_can_access_create_and_edit_page_views(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        /** @var User $user */
        $user = User::factory()->create();

        $createResponse = $this->actingAs($user)->get('/admin/pages/create');
        $createResponse->assertOk();
        $createResponse->assertSee('Créer une nouvelle page');

        /** @var Page $page */
        $page = Page::query()->create([
            'title' => 'Page Test Edition',
            'slug' => 'test-edition',
            'content' => 'Contenu initial',
            'status' => 'draft',
            'template' => 'default',
        ]);

        $editResponse = $this->actingAs($user)->get('/admin/pages/'.$page->id.'/edit');
        $editResponse->assertOk();
        $editResponse->assertSee('Modifier la page');
        $editResponse->assertSee('test-edition');
    }

    public function test_authenticated_user_can_update_page_with_v2_attributes(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Page $parent */
        $parent = Page::query()->create([
            'title' => 'Entreprise',
            'slug' => 'entreprise',
            'status' => 'published',
            'is_published' => true,
        ]);

        /** @var Page $page */
        $page = Page::query()->create([
            'title' => 'Équipe',
            'slug' => 'equipe',
            'status' => 'draft',
            'is_published' => false,
        ]);

        $response = $this->actingAs($user)->putJson('/admin/pages/'.$page->id, [
            'title' => 'Notre Équipe de Direction',
            'slug' => 'notre-equipe',
            'content' => 'Présentation de l\'équipe',
            'status' => 'published',
            'is_published' => true,
            'parent_id' => $parent->id,
            'template' => 'full-width',
            'featured_image' => 'https://example.com/team.jpg',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.title', 'Notre Équipe de Direction');
        $response->assertJsonPath('data.slug', 'notre-equipe');
        $response->assertJsonPath('data.template', 'full-width');
        $response->assertJsonPath('data.parent_id', $parent->id);
        $response->assertJsonPath('data.featured_image', 'https://example.com/team.jpg');

        $this->assertDatabaseHas('plugin_pages', [
            'id' => $page->id,
            'parent_id' => $parent->id,
            'template' => 'full-width',
            'featured_image' => 'https://example.com/team.jpg',
        ]);
    }

    public function test_page_hierarchy_and_scheduled_publishing(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        /** @var Page $parent */
        $parent = Page::query()->create([
            'title' => 'Services',
            'slug' => 'services',
            'status' => 'published',
            'is_published' => true,
        ]);

        /** @var Page $child */
        $child = Page::query()->create([
            'parent_id' => $parent->id,
            'title' => 'Conseil',
            'slug' => 'conseil',
            'status' => 'published',
            'is_published' => true,
            'featured_image' => 'https://example.com/banner.jpg',
        ]);

        $this->assertSame('services/conseil', $child->getFullPath());
        $this->assertSame($parent->id, $child->parent?->id);
        $this->assertTrue($parent->children->contains('id', $child->id));

        // Public front view should display parent link and featured image
        $response = $this->get('/page/conseil');
        $response->assertOk();
        $response->assertSee('Services');
        $response->assertSee('https://example.com/banner.jpg');

        // Test scheduled date in the future (should be 404 for visitors)
        $futurePage = Page::query()->create([
            'title' => 'Futur Événement',
            'slug' => 'futur-event',
            'status' => 'published',
            'is_published' => true,
            'published_at' => now()->addDays(5),
        ]);

        $this->get('/page/futur-event')->assertNotFound();

        // When date is in the past, it should be visible
        $futurePage->published_at = now()->subMinute();
        $futurePage->save();

        $this->get('/page/futur-event')->assertOk();
    }
}
