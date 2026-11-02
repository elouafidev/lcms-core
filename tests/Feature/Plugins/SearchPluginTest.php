<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use Core\Admin\NavigationRegistry;
use Core\Lifecycle\PluginState;
use Core\Models\User;
use Core\Permissions\PermissionRegistry;
use Core\PluginManager\PluginManager;
use Core\PluginRegistry\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Plugins\Blocks\Models\Block;
use Plugins\Blog\Models\Post;
use Plugins\Pages\Models\Page;
use Plugins\Search\Models\SearchLog;
use Plugins\Search\Services\SearchEngine;
use Tests\TestCase;

class SearchPluginTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $pluginManager;

    private PluginRegistry $pluginRegistry;

    private NavigationRegistry $navigationRegistry;

    private PermissionRegistry $permissionRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginManager = $this->app->make(PluginManager::class);
        $this->pluginRegistry = $this->app->make(PluginRegistry::class);
        $this->navigationRegistry = $this->app->make(NavigationRegistry::class);
        $this->permissionRegistry = $this->app->make(PermissionRegistry::class);

        config(['cms.plugins.path' => base_path('Plugins')]);
    }

    public function test_plugin_is_discoverable_and_inactive_initially(): void
    {
        $discovered = $this->pluginRegistry->getDiscovered('lcmscore.search');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_search_logs'));
        $this->assertFalse($this->navigationRegistry->has('search-management'));

        // Public search route should be 404 before activation
        $this->get('/search')->assertNotFound();
    }

    public function test_install_and_activation_creates_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.search');
        $this->pluginManager->activate('lcmscore.search');

        $this->assertTrue(Schema::hasTable('plugin_search_logs'));

        // Navigation check
        $this->assertTrue($this->navigationRegistry->has('search-management'));
        $item = $this->navigationRegistry->get('search-management');
        $this->assertNotNull($item);
        $this->assertSame('Recherche & Stats', $item->getLabel());
        $this->assertSame('/admin/search', $item->getUrl());

        // Permissions check
        $this->assertTrue($this->permissionRegistry->has('search.manage'));
    }

    public function test_search_engine_searches_pages_posts_and_blocks(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        $this->pluginManager->install('lcmscore.blog');
        $this->pluginManager->activate('lcmscore.blog');

        $this->pluginManager->install('lcmscore.blocks');
        $this->pluginManager->activate('lcmscore.blocks');

        $this->pluginManager->install('lcmscore.search');
        $this->pluginManager->activate('lcmscore.search');

        /** @var User $author */
        $author = User::factory()->create();

        // Create a published page
        Page::query()->create([
            'title' => 'Découvrir notre Framework LCMS',
            'slug' => 'decouvrir-framework-lcms',
            'content' => 'LCMS est un système complet conçu pour créer des applications web modulaires.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Create a published blog post
        Post::query()->create([
            'title' => 'Architecture modulaire dans LCMS',
            'slug' => 'architecture-modulaire-lcms',
            'excerpt' => 'Apprenez comment concevoir des plugins indépendants.',
            'content' => 'Dans cet article nous étudions le Lean Core et les contrats d extension.',
            'status' => 'published',
            'author_id' => $author->id,
            'published_at' => now(),
        ]);

        // Create an active block
        Block::query()->create([
            'name' => 'Bannière Promotionnelle LCMS',
            'slug' => 'banniere-promo-lcms',
            'type' => 'banner',
            'is_active' => true,
            'order' => 1,
            'content' => [
                'title' => 'Profitez de la nouvelle version de LCMS',
                'description' => 'Un système de plugins ultra performant sans couplage fort.',
            ],
        ]);

        /** @var SearchEngine $engine */
        $engine = $this->app->make(SearchEngine::class);

        // Search for 'LCMS'
        $results = $engine->search('LCMS');
        $this->assertCount(3, $results);

        $titles = array_map(fn ($r) => $r->title, $results);
        $this->assertContains('Découvrir notre Framework LCMS', $titles);
        $this->assertContains('Architecture modulaire dans LCMS', $titles);
        $this->assertContains('Bannière Promotionnelle LCMS', $titles);

        // Check snippet highlighting
        $pageResult = collect($results)->firstWhere('type', 'page');
        $this->assertNotNull($pageResult);
        $this->assertStringContainsString('<mark class="bg-warning-subtle text-dark px-1 rounded">LCMS</mark>', $pageResult->snippet);
    }

    public function test_type_filtering_isolates_specific_entity_types(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        $this->pluginManager->install('lcmscore.blog');
        $this->pluginManager->activate('lcmscore.blog');

        $this->pluginManager->install('lcmscore.search');
        $this->pluginManager->activate('lcmscore.search');

        /** @var User $author */
        $author = User::factory()->create();

        Page::query()->create([
            'title' => 'Page Unique Alpha',
            'slug' => 'page-unique-alpha',
            'content' => 'Contenu contenant le mot clé Alpha CMS.',
            'status' => 'published',
        ]);

        Post::query()->create([
            'title' => 'Article Alpha Spécial',
            'slug' => 'article-alpha-special',
            'excerpt' => 'Résumé de l article Alpha.',
            'content' => 'Contenu complet de l article Alpha.',
            'status' => 'published',
            'author_id' => $author->id,
            'published_at' => now(),
        ]);

        /** @var SearchEngine $engine */
        $engine = $this->app->make(SearchEngine::class);

        // Filter only pages
        $pageResults = $engine->search('Alpha', 'page');
        $this->assertCount(1, $pageResults);
        $this->assertSame('Page Unique Alpha', $pageResults[0]->title);
        $this->assertSame('page', $pageResults[0]->type);

        // Filter only blog posts
        $postResults = $engine->search('Alpha', 'post');
        $this->assertCount(1, $postResults);
        $this->assertSame('Article Alpha Spécial', $postResults[0]->title);
        $this->assertSame('post', $postResults[0]->type);
    }

    public function test_public_search_and_live_ajax_endpoints(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        $this->pluginManager->install('lcmscore.search');
        $this->pluginManager->activate('lcmscore.search');

        Page::query()->create([
            'title' => 'Tutoriel Laravel 12',
            'slug' => 'tutoriel-laravel-12',
            'content' => 'Guide étape par étape pour maîtriser Laravel.',
            'status' => 'published',
        ]);

        // GET /search without term
        $this->get('/search')
            ->assertOk()
            ->assertSee('Recherche globale');

        // GET /search with query term
        $this->get('/search?q=Laravel')
            ->assertOk()
            ->assertSee('Tutoriel Laravel 12')
            ->assertSee('1 résultat');

        // GET /search/live with query term
        $liveResponse = $this->getJson('/search/live?q=Laravel');
        $liveResponse->assertOk()
            ->assertJsonStructure([
                'results' => [
                    '*' => ['title', 'url', 'type', 'type_label', 'snippet', 'date'],
                ],
            ])
            ->assertJsonFragment([
                'title' => 'Tutoriel Laravel 12',
                'url' => '/page/tutoriel-laravel-12',
            ]);

        // GET /search/live with empty query
        $this->getJson('/search/live?q=')
            ->assertOk()
            ->assertJson(['results' => []]);
    }

    public function test_search_logging_and_zero_result_detection(): void
    {
        $this->pluginManager->install('lcmscore.search');
        $this->pluginManager->activate('lcmscore.search');

        // Query with 0 results
        $this->get('/search?q=TermIntrouvableXYZ');

        $this->assertDatabaseHas('plugin_search_logs', [
            'query' => 'TermIntrouvableXYZ',
            'results_count' => 0,
        ]);

        $log = SearchLog::query()->where('query', 'TermIntrouvableXYZ')->first();
        $this->assertNotNull($log);
        $this->assertSame(0, $log->results_count);
    }

    public function test_admin_search_analytics_and_clear_history(): void
    {
        $this->pluginManager->install('lcmscore.search');
        $this->pluginManager->activate('lcmscore.search');

        /** @var User $admin */
        $admin = User::factory()->create();

        // Seed some search logs
        SearchLog::record('laravel', 5);
        SearchLog::record('laravel', 5);
        SearchLog::record('cms-manquant', 0);
        SearchLog::record('e-commerce', 0);

        // Guest cannot access admin search
        $this->get('/admin/search')->assertRedirect('/login');

        // Authenticated admin can view dashboard
        $response = $this->actingAs($admin)->get('/admin/search');
        $response->assertOk()
            ->assertSee('Moteur de Recherche &amp; Analytics', false)
            ->assertSee('laravel')
            ->assertSee('cms-manquant')
            ->assertSee('e-commerce')
            ->assertSee('Opportunités de contenu');

        // Admin can clear logs
        $this->actingAs($admin)
            ->post('/admin/search/clear')
            ->assertRedirect('/admin/search')
            ->assertSessionHas('success');

        $this->assertSame(0, SearchLog::query()->count());
    }

    public function test_blade_directive_search_bar_renders(): void
    {
        $this->pluginManager->install('lcmscore.search');
        $this->pluginManager->activate('lcmscore.search');

        $rendered = Blade::render('@searchBar');

        $this->assertStringContainsString('/search', $rendered);
        $this->assertStringContainsString('id="lcmsSearchInput"', $rendered);
        $this->assertStringContainsString('id="lcmsLiveResults"', $rendered);
    }

    public function test_uninstall_with_purge_drops_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.search');
        $this->pluginManager->activate('lcmscore.search');

        $this->assertTrue(Schema::hasTable('plugin_search_logs'));
        $this->assertTrue($this->navigationRegistry->has('search-management'));

        $this->pluginManager->deactivate('lcmscore.search');
        $this->pluginManager->uninstall('lcmscore.search', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_search_logs'));
        $this->assertFalse($this->navigationRegistry->has('search-management'));
    }
}
