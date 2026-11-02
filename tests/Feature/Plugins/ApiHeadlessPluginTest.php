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
use Illuminate\Support\Facades\Schema;
use Plugins\ApiHeadless\Models\ApiToken;
use Plugins\Blocks\Models\Block;
use Plugins\Blog\Models\Category;
use Plugins\Blog\Models\Post;
use Plugins\Pages\Models\Page;
use Tests\TestCase;

class ApiHeadlessPluginTest extends TestCase
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
        $discovered = $this->pluginRegistry->getDiscovered('lcmscore.api-headless');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_api_tokens'));
        $this->assertFalse($this->navigationRegistry->has('api-headless-management'));

        // Public API endpoint should be 404 before activation
        $this->getJson('/api/v1/info')->assertNotFound();
    }

    public function test_install_and_activation_creates_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.api-headless');
        $this->pluginManager->activate('lcmscore.api-headless');

        $this->assertTrue(Schema::hasTable('plugin_api_tokens'));

        // Navigation check
        $this->assertTrue($this->navigationRegistry->has('api-headless-management'));
        $item = $this->navigationRegistry->get('api-headless-management');
        $this->assertNotNull($item);
        $this->assertSame('API REST & Headless', $item->getLabel());
        $this->assertSame('/admin/api-tokens', $item->getUrl());

        // Permissions check
        $this->assertTrue($this->permissionRegistry->has('api.tokens.manage'));
    }

    public function test_public_info_endpoint_returns_json_metadata(): void
    {
        $this->pluginManager->install('lcmscore.api-headless');
        $this->pluginManager->activate('lcmscore.api-headless');

        $response = $this->getJson('/api/v1/info');
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'api_version' => 'v1',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'version',
                    'api_version',
                    'features' => ['pages', 'blog', 'menus', 'blocks'],
                ],
            ]);
    }

    public function test_pages_and_page_slug_endpoints(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        $this->pluginManager->install('lcmscore.api-headless');
        $this->pluginManager->activate('lcmscore.api-headless');

        // Create published page and draft page
        Page::query()->create([
            'title' => 'Découvrir LCMS Headless',
            'slug' => 'decouvrir-lcms-headless',
            'content' => 'Documentation API complète.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Page::query()->create([
            'title' => 'Page Brouillon Interne',
            'slug' => 'page-brouillon-interne',
            'content' => 'Brouillon confidentiel.',
            'status' => 'draft',
        ]);

        // 1. List published pages
        $listResponse = $this->getJson('/api/v1/pages');
        $listResponse->assertOk()
            ->assertJsonFragment(['slug' => 'decouvrir-lcms-headless'])
            ->assertJsonMissing(['slug' => 'page-brouillon-interne']);

        // 2. Get single published page
        $singleResponse = $this->getJson('/api/v1/pages/decouvrir-lcms-headless');
        $singleResponse->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Découvrir LCMS Headless',
                    'slug' => 'decouvrir-lcms-headless',
                ],
            ]);

        // 3. Draft page returns 404
        $this->getJson('/api/v1/pages/page-brouillon-interne')
            ->assertNotFound();
    }

    public function test_posts_and_post_slug_endpoints(): void
    {
        $this->pluginManager->install('lcmscore.blog');
        $this->pluginManager->activate('lcmscore.blog');

        $this->pluginManager->install('lcmscore.api-headless');
        $this->pluginManager->activate('lcmscore.api-headless');

        /** @var User $author */
        $author = User::factory()->create();

        /** @var Category $category */
        $category = Category::query()->create([
            'name' => 'Technologies',
            'slug' => 'technologies',
        ]);

        Post::query()->create([
            'title' => 'Le Futur du CMS Headless avec Laravel',
            'slug' => 'futur-cms-headless-laravel',
            'excerpt' => 'Analyse des architectures découplées.',
            'content' => 'Contenu détaillé sur Next.js et LCMS Core.',
            'status' => 'published',
            'author_id' => $author->id,
            'category_id' => $category->id,
            'published_at' => now(),
        ]);

        // 1. List posts
        $listResponse = $this->getJson('/api/v1/posts');
        $listResponse->assertOk()
            ->assertJsonFragment(['slug' => 'futur-cms-headless-laravel']);

        // 2. Filter by category
        $catResponse = $this->getJson('/api/v1/posts?category=technologies');
        $catResponse->assertOk()
            ->assertJsonFragment(['slug' => 'futur-cms-headless-laravel']);

        // 3. Get single post
        $singleResponse = $this->getJson('/api/v1/posts/futur-cms-headless-laravel');
        $singleResponse->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'slug' => 'futur-cms-headless-laravel',
                    'title' => 'Le Futur du CMS Headless avec Laravel',
                ],
            ]);
    }

    public function test_blocks_endpoint(): void
    {
        $this->pluginManager->install('lcmscore.blocks');
        $this->pluginManager->activate('lcmscore.blocks');

        $this->pluginManager->install('lcmscore.api-headless');
        $this->pluginManager->activate('lcmscore.api-headless');

        Block::query()->create([
            'name' => 'Bannière API',
            'slug' => 'banniere-api',
            'type' => 'hero',
            'is_active' => true,
            'order' => 1,
            'content' => [
                'title' => 'Prêt pour l\'univers Headless',
            ],
        ]);

        $response = $this->getJson('/api/v1/blocks/banniere-api');
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'slug' => 'banniere-api',
                    'type' => 'hero',
                ],
            ]);
    }

    public function test_protected_auth_verify_requires_valid_bearer_token(): void
    {
        $this->pluginManager->install('lcmscore.api-headless');
        $this->pluginManager->activate('lcmscore.api-headless');

        // 1. No token -> 401
        $this->getJson('/api/v1/auth/verify')
            ->assertStatus(401)
            ->assertJson(['success' => false]);

        // 2. Invalid token -> 401
        $this->withToken('lcms_invalid_token_12345')
            ->getJson('/api/v1/auth/verify')
            ->assertStatus(401)
            ->assertJson(['success' => false]);

        // 3. Valid token -> 200
        $generated = ApiToken::generate('Mobile Client Flutter', ['*'], 30);
        $plainToken = $generated['plainTextToken'];

        $response = $this->withToken($plainToken)->getJson('/api/v1/auth/verify');
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Jeton API valide et actif.',
            ]);

        $generated['token']->refresh();
        $this->assertNotNull($generated['token']->last_used_at);
    }

    public function test_expired_token_is_rejected(): void
    {
        $this->pluginManager->install('lcmscore.api-headless');
        $this->pluginManager->activate('lcmscore.api-headless');

        $generated = ApiToken::generate('Old Token');
        $token = $generated['token'];
        $token->expires_at = now()->subDays(5);
        $token->save();

        $this->withToken($generated['plainTextToken'])
            ->getJson('/api/v1/auth/verify')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Jeton API invalide, révoqué ou expiré.',
            ]);
    }

    public function test_admin_can_view_tokens_dashboard_and_generate_new_token(): void
    {
        $this->pluginManager->install('lcmscore.api-headless');
        $this->pluginManager->activate('lcmscore.api-headless');

        /** @var User $admin */
        $admin = User::factory()->create();

        // Guest is redirected
        $this->get('/admin/api-tokens')->assertRedirect('/login');

        // Admin can view index and documentation tab
        $this->actingAs($admin)
            ->get('/admin/api-tokens')
            ->assertOk()
            ->assertSee('API REST &amp; Mode Headless', false)
            ->assertSee('Total Jetons');

        $this->actingAs($admin)
            ->get('/admin/api-tokens?tab=docs')
            ->assertOk()
            ->assertSee('Spécification REST v1');

        // Admin generates new token
        $response = $this->actingAs($admin)
            ->post('/admin/api-tokens', [
                'name' => 'NextJS Frontend Client',
                'days_valid' => '90',
                'abilities' => ['*'],
            ]);

        $response->assertRedirect('/admin/api-tokens')
            ->assertSessionHas('new_plain_token');

        $this->assertDatabaseHas('plugin_api_tokens', [
            'name' => 'NextJS Frontend Client',
        ]);
    }

    public function test_admin_can_revoke_token(): void
    {
        $this->pluginManager->install('lcmscore.api-headless');
        $this->pluginManager->activate('lcmscore.api-headless');

        /** @var User $admin */
        $admin = User::factory()->create();

        $generated = ApiToken::generate('Token to Revoke');
        $tokenId = $generated['token']->id;

        $this->actingAs($admin)
            ->delete('/admin/api-tokens/'.$tokenId)
            ->assertRedirect('/admin/api-tokens')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('plugin_api_tokens', [
            'id' => $tokenId,
        ]);
    }

    public function test_uninstall_with_purge_drops_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.api-headless');
        $this->pluginManager->activate('lcmscore.api-headless');

        $this->assertTrue(Schema::hasTable('plugin_api_tokens'));
        $this->assertTrue($this->navigationRegistry->has('api-headless-management'));

        $this->pluginManager->deactivate('lcmscore.api-headless');
        $this->pluginManager->uninstall('lcmscore.api-headless', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_api_tokens'));
        $this->assertFalse($this->navigationRegistry->has('api-headless-management'));
    }
}
