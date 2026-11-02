<?php

declare(strict_types=1);

namespace Plugins\Blog\Providers;

use Core\Admin\NavigationItem;
use Core\Admin\NavigationRegistry;
use Core\Hooks\HookManager;
use Illuminate\Support\ServiceProvider;

class BlogServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services in the container.
     */
    public function register(): void
    {
        // Container bindings for blog
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $pluginRoot = dirname(__DIR__, 2);

        $viewsPath = $pluginRoot.'/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'blog');
        }

        $routesPath = $pluginRoot.'/routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Register public head hook for RSS feed auto-discovery
        if ($this->app->bound(HookManager::class)) {
            /** @var HookManager $hookManager */
            $hookManager = $this->app->make(HookManager::class);
            $hookManager->addFilter('cms.head', function (string $content): string {
                $feedUrl = url('/blog/feed.xml');
                $linkTag = "\n    <link rel=\"alternate\" type=\"application/rss+xml\" title=\"Flux RSS Blog\" href=\"{$feedUrl}\" />";

                return $content.$linkTag;
            });
        }

        // Register Navigation item with treeview in sidebar
        if ($this->app->bound(NavigationRegistry::class)) {
            /** @var NavigationRegistry $navigation */
            $navigation = $this->app->make(NavigationRegistry::class);
            $navigation->registerItem(
                id: 'blog-management',
                label: 'Blog & Actualités',
                url: '/admin/blog/posts',
                icon: 'bi bi-newspaper',
                priority: 22,
                permission: 'blog.posts.manage',
                pluginId: 'lcmscore.blog',
                children: [
                    NavigationItem::make('blog-posts', 'Tous les articles', '/admin/blog/posts', 'bi bi-card-text', 10, 'blog.posts.manage', 'lcmscore.blog'),
                    NavigationItem::make('blog-posts-create', 'Ajouter un article', '/admin/blog/posts/create', 'bi bi-plus-circle', 20, 'blog.posts.manage', 'lcmscore.blog'),
                    NavigationItem::make('blog-categories', 'Catégories', '/admin/blog/categories', 'bi bi-folder', 30, 'blog.categories.manage', 'lcmscore.blog'),
                    NavigationItem::make('blog-tags', 'Étiquettes', '/admin/blog/tags', 'bi bi-tags', 40, 'blog.tags.manage', 'lcmscore.blog'),
                ]
            );
        }
    }
}
