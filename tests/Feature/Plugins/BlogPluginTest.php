<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use Core\Admin\NavigationRegistry;
use Core\Hooks\HookManager;
use Core\Lifecycle\PluginState;
use Core\Models\User;
use Core\Permissions\PermissionRegistry;
use Core\PluginManager\PluginManager;
use Core\PluginRegistry\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Plugins\Blog\Models\Category;
use Plugins\Blog\Models\Post;
use Plugins\Blog\Models\Tag;
use Tests\TestCase;

class BlogPluginTest extends TestCase
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
        $discovered = $this->pluginRegistry->getDiscovered('lcmscore.blog');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_blog_posts'));
        $this->assertFalse(Schema::hasTable('plugin_blog_categories'));
        $this->assertFalse(Schema::hasTable('plugin_blog_tags'));
        $this->assertFalse(Schema::hasTable('plugin_blog_post_tag'));

        // Public blog route should be 404 before activation
        $this->get('/blog')->assertNotFound();
    }

    public function test_install_and_activation_creates_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.blog');
        $this->pluginManager->activate('lcmscore.blog');

        $this->assertTrue(Schema::hasTable('plugin_blog_categories'));
        $this->assertTrue(Schema::hasTable('plugin_blog_tags'));
        $this->assertTrue(Schema::hasTable('plugin_blog_posts'));
        $this->assertTrue(Schema::hasTable('plugin_blog_post_tag'));

        // Navigation check
        $this->assertTrue($this->navigationRegistry->has('blog-management'));
        $item = $this->navigationRegistry->get('blog-management');
        $this->assertNotNull($item);
        $this->assertSame('Blog & Actualités', $item->getLabel());
        $this->assertSame('/admin/blog/posts', $item->getUrl());
        $this->assertCount(4, $item->getChildren());

        // Permissions check
        $this->assertTrue($this->permissionRegistry->has('blog.posts.manage'));
        $this->assertTrue($this->permissionRegistry->has('blog.categories.manage'));
        $this->assertTrue($this->permissionRegistry->has('blog.tags.manage'));
    }

    public function test_admin_can_create_and_manage_categories(): void
    {
        $this->pluginManager->install('lcmscore.blog');
        $this->pluginManager->activate('lcmscore.blog');

        /** @var User $user */
        $user = User::factory()->create();

        // Index view
        $this->actingAs($user)->get('/admin/blog/categories')->assertOk();

        // Create category
        $response = $this->actingAs($user)->post('/admin/blog/categories', [
            'name' => 'Technologies & Laravel',
            'slug' => 'tech-laravel',
            'description' => 'Articles sur Laravel et PHP',
            'color' => '#10b981',
            'order' => 1,
        ]);

        $response->assertRedirect('/admin/blog/categories');
        $this->assertDatabaseHas('plugin_blog_categories', [
            'name' => 'Technologies & Laravel',
            'slug' => 'tech-laravel',
            'color' => '#10b981',
        ]);

        /** @var Category $category */
        $category = Category::query()->where('slug', 'tech-laravel')->firstOrFail();

        // Delete category
        $deleteResponse = $this->actingAs($user)->delete('/admin/blog/categories/'.$category->id);
        $deleteResponse->assertRedirect('/admin/blog/categories');
        $this->assertDatabaseMissing('plugin_blog_categories', ['id' => $category->id]);
    }

    public function test_admin_can_create_and_manage_tags(): void
    {
        $this->pluginManager->install('lcmscore.blog');
        $this->pluginManager->activate('lcmscore.blog');

        /** @var User $user */
        $user = User::factory()->create();

        // Index view
        $this->actingAs($user)->get('/admin/blog/tags')->assertOk();

        // Create tag
        $response = $this->actingAs($user)->post('/admin/blog/tags', [
            'name' => 'Tutoriel',
            'slug' => 'tutoriel',
        ]);

        $response->assertRedirect('/admin/blog/tags');
        $this->assertDatabaseHas('plugin_blog_tags', [
            'name' => 'Tutoriel',
            'slug' => 'tutoriel',
        ]);

        /** @var Tag $tag */
        $tag = Tag::query()->where('slug', 'tutoriel')->firstOrFail();

        // Delete tag
        $deleteResponse = $this->actingAs($user)->delete('/admin/blog/tags/'.$tag->id);
        $deleteResponse->assertRedirect('/admin/blog/tags');
        $this->assertDatabaseMissing('plugin_blog_tags', ['id' => $tag->id]);
    }

    public function test_admin_can_create_and_update_post_with_auto_reading_time_and_tags(): void
    {
        $this->pluginManager->install('lcmscore.blog');
        $this->pluginManager->activate('lcmscore.blog');

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Category $category */
        $category = Category::create([
            'name' => 'Guides',
            'slug' => 'guides',
        ]);

        // Create form view
        $this->actingAs($user)->get('/admin/blog/posts/create')->assertOk();

        // 450 words of dummy content to test reading time calculation (~3 minutes)
        $words = implode(' ', array_fill(0, 450, 'laravel'));

        $response = $this->actingAs($user)->post('/admin/blog/posts', [
            'title' => 'Découvrir LCMS Core',
            'slug' => 'decouvrir-lcms-core',
            'category_id' => $category->id,
            'author_id' => $user->id,
            'content' => $words,
            'excerpt' => 'Un guide complet pour bien débuter avec LCMS Core.',
            'status' => 'published',
            'is_featured' => true,
            'tags' => 'cms, modulaire, open-source',
            'meta_title' => 'Découvrir LCMS Core — Guide Officiel',
            'meta_description' => 'Toutes les étapes pour installer et étendre LCMS Core.',
        ]);

        $response->assertRedirect('/admin/blog/posts');

        $this->assertDatabaseHas('plugin_blog_posts', [
            'title' => 'Découvrir LCMS Core',
            'slug' => 'decouvrir-lcms-core',
            'status' => 'published',
            'is_featured' => 1,
            'reading_time' => 3, // 450 / 200 = 2.25 => ceil 3
        ]);

        /** @var Post $post */
        $post = Post::query()->where('slug', 'decouvrir-lcms-core')->firstOrFail();
        $this->assertCount(3, $post->tags);
        $this->assertTrue($post->tags->pluck('slug')->contains('modulaire'));

        // Edit form view
        $this->actingAs($user)->get('/admin/blog/posts/'.$post->id.'/edit')->assertOk();

        // Update post
        $updateResponse = $this->actingAs($user)->put('/admin/blog/posts/'.$post->id, [
            'title' => 'Découvrir LCMS Core v2',
            'slug' => 'decouvrir-lcms-core-v2',
            'category_id' => $category->id,
            'content' => 'Contenu court mis à jour.',
            'status' => 'published',
            'tags' => 'cms, php',
        ]);

        $updateResponse->assertRedirect('/admin/blog/posts');
        $this->assertDatabaseHas('plugin_blog_posts', [
            'id' => $post->id,
            'title' => 'Découvrir LCMS Core v2',
            'slug' => 'decouvrir-lcms-core-v2',
            'reading_time' => 1,
        ]);

        $post->refresh();
        $this->assertCount(2, $post->tags);
        $this->assertTrue($post->tags->pluck('slug')->contains('php'));
    }

    public function test_public_user_can_view_published_posts_and_view_count_increments(): void
    {
        $this->pluginManager->install('lcmscore.blog');
        $this->pluginManager->activate('lcmscore.blog');

        /** @var Category $category */
        $category = Category::create(['name' => 'Actualité', 'slug' => 'actualite']);

        /** @var Post $publishedPost */
        $publishedPost = Post::create([
            'title' => 'Article Public',
            'slug' => 'article-public',
            'content' => '<p>Bienvenue sur notre blog.</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'category_id' => $category->id,
            'views_count' => 0,
        ]);

        /** @var Post $draftPost */
        $draftPost = Post::create([
            'title' => 'Article Secret Brouillon',
            'slug' => 'article-secret-brouillon',
            'content' => '<p>Confidentiel.</p>',
            'status' => 'draft',
        ]);

        // Public index shows published post, hides draft
        $indexResponse = $this->get('/blog');
        $indexResponse->assertOk();
        $indexResponse->assertSee('Article Public');
        $indexResponse->assertDontSee('Article Secret Brouillon');

        // View single published post
        $postResponse = $this->get('/blog/article-public');
        $postResponse->assertOk();
        $postResponse->assertSee('Bienvenue sur notre blog');

        // Views count should have incremented to 1
        $publishedPost->refresh();
        $this->assertSame(1, $publishedPost->views_count);

        // Draft post should be 404 for public visitors
        $this->get('/blog/article-secret-brouillon')->assertNotFound();
    }

    public function test_public_user_can_filter_by_category_and_tag(): void
    {
        $this->pluginManager->install('lcmscore.blog');
        $this->pluginManager->activate('lcmscore.blog');

        /** @var Category $catNews */
        $catNews = Category::create(['name' => 'News', 'slug' => 'news']);
        /** @var Category $catTutorials */
        $catTutorials = Category::create(['name' => 'Tutos', 'slug' => 'tutos']);

        /** @var Tag $tagLaravel */
        $tagLaravel = Tag::create(['name' => 'Laravel', 'slug' => 'laravel']);

        /** @var Post $post1 */
        $post1 = Post::create([
            'title' => 'News Article',
            'slug' => 'news-article',
            'content' => 'Texte news',
            'status' => 'published',
            'published_at' => now(),
            'category_id' => $catNews->id,
        ]);
        $post1->tags()->sync([$tagLaravel->id]);

        /** @var Post $post2 */
        $post2 = Post::create([
            'title' => 'Tutorial Article',
            'slug' => 'tutorial-article',
            'content' => 'Texte tutoriel',
            'status' => 'published',
            'published_at' => now(),
            'category_id' => $catTutorials->id,
        ]);

        // Category filter
        $catResponse = $this->get('/blog/category/news');
        $catResponse->assertOk();
        $catResponse->assertSee('News Article');
        $catResponse->assertDontSee('Tutorial Article');

        // Tag filter
        $tagResponse = $this->get('/blog/tag/laravel');
        $tagResponse->assertOk();
        $tagResponse->assertSee('News Article');
        $tagResponse->assertDontSee('Tutorial Article');
    }

    public function test_rss_feed_is_valid_xml(): void
    {
        $this->pluginManager->install('lcmscore.blog');
        $this->pluginManager->activate('lcmscore.blog');

        Post::create([
            'title' => 'Premier Article RSS',
            'slug' => 'premier-article-rss',
            'content' => '<p>Contenu pour le flux de syndication.</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get('/blog/feed.xml');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');

        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('<rss version="2.0"', $content);
        $this->assertStringContainsString('<title><![CDATA[Premier Article RSS]]></title>', $content);
        $this->assertStringContainsString('/blog/premier-article-rss', $content);
    }

    public function test_cms_head_hook_injects_rss_discovery(): void
    {
        $this->pluginManager->install('lcmscore.blog');
        $this->pluginManager->activate('lcmscore.blog');

        /** @var HookManager $hookManager */
        $hookManager = $this->app->make(HookManager::class);

        $filteredHead = (string) $hookManager->applyFilter('cms.head', '<meta charset="utf-8">');

        $this->assertStringContainsString('type="application/rss+xml"', $filteredHead);
        $this->assertStringContainsString('/blog/feed.xml', $filteredHead);
    }

    public function test_uninstall_with_purge_drops_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.blog');
        $this->pluginManager->activate('lcmscore.blog');

        $this->assertTrue(Schema::hasTable('plugin_blog_posts'));

        // Uninstall with purgeData = true
        $this->pluginManager->uninstall('lcmscore.blog', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_blog_posts'));
        $this->assertFalse(Schema::hasTable('plugin_blog_categories'));
        $this->assertFalse(Schema::hasTable('plugin_blog_tags'));
        $this->assertFalse(Schema::hasTable('plugin_blog_post_tag'));

        $this->assertFalse($this->navigationRegistry->has('blog-management'));
        $this->assertFalse($this->permissionRegistry->has('blog.posts.manage'));
    }
}
