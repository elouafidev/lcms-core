<?php

declare(strict_types=1);

namespace Plugins\ApiHeadless\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Plugins\Blocks\Models\Block;
use Plugins\Blog\Models\Post;
use Plugins\Menus\Models\Menu;
use Plugins\Pages\Models\Page;

class ApiContentController extends Controller
{
    /**
     * Return public CMS metadata and API capabilities.
     */
    public function info(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'name' => config('app.name', 'LCMS Core'),
                'version' => '1.0.0',
                'api_version' => 'v1',
                'features' => [
                    'pages' => class_exists(Page::class) && Schema::hasTable('plugin_pages'),
                    'blog' => class_exists(Post::class) && Schema::hasTable('plugin_blog_posts'),
                    'menus' => class_exists(Menu::class) && Schema::hasTable('plugin_menus'),
                    'blocks' => class_exists(Block::class) && Schema::hasTable('plugin_blocks'),
                ],
            ],
        ]);
    }

    /**
     * List published pages with search and pagination.
     */
    public function pages(Request $request): JsonResponse
    {
        if (! class_exists(Page::class) || ! Schema::hasTable('plugin_pages')) {
            return response()->json(['success' => false, 'message' => 'Module Pages non disponible.', 'data' => []], 404);
        }

        $rawSearch = $request->query('search');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';

        $query = Page::query()->where('status', 'published')->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            });
        }

        $pages = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $pages->items(),
            'meta' => [
                'current_page' => $pages->currentPage(),
                'last_page' => $pages->lastPage(),
                'total' => $pages->total(),
            ],
        ]);
    }

    /**
     * Return detailed published page by slug.
     */
    public function page(string $slug): JsonResponse
    {
        if (! class_exists(Page::class) || ! Schema::hasTable('plugin_pages')) {
            return response()->json(['success' => false, 'message' => 'Module Pages non disponible.'], 404);
        }

        /** @var Page|null $page */
        $page = Page::query()->where('slug', $slug)->where('status', 'published')->first();

        if ($page === null) {
            return response()->json(['success' => false, 'message' => 'Page introuvable ou non publiée.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $page,
        ]);
    }

    /**
     * List published blog posts with category and tag filters.
     */
    public function posts(Request $request): JsonResponse
    {
        if (! class_exists(Post::class) || ! Schema::hasTable('plugin_blog_posts')) {
            return response()->json(['success' => false, 'message' => 'Module Blog non disponible.', 'data' => []], 404);
        }

        $rawSearch = $request->query('search');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';

        $query = Post::queryPublished()->with(['category', 'tags']);

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('excerpt', 'like', '%'.$search.'%')
                    ->orWhere('content', 'like', '%'.$search.'%');
            });
        }

        $rawCat = $request->query('category');
        if (is_string($rawCat) && trim($rawCat) !== '') {
            $catSlug = trim($rawCat);
            $query->whereHas('category', function ($q) use ($catSlug): void {
                $q->where('slug', $catSlug);
            });
        }

        $posts = $query->latest('published_at')->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * Return single published blog post by slug.
     */
    public function post(string $slug): JsonResponse
    {
        if (! class_exists(Post::class) || ! Schema::hasTable('plugin_blog_posts')) {
            return response()->json(['success' => false, 'message' => 'Module Blog non disponible.'], 404);
        }

        /** @var Post|null $post */
        $post = Post::queryPublished()->with(['category', 'tags', 'author'])->where('slug', $slug)->first();

        if ($post === null) {
            return response()->json(['success' => false, 'message' => 'Article introuvable ou non publié.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $post,
        ]);
    }

    /**
     * Return hierarchical menu items by menu slug.
     */
    public function menu(string $slug): JsonResponse
    {
        if (! class_exists(Menu::class) || ! Schema::hasTable('plugin_menus')) {
            return response()->json(['success' => false, 'message' => 'Module Menus non disponible.'], 404);
        }

        /** @var Menu|null $menu */
        $menu = Menu::query()->with('items')->where('slug', $slug)->first();

        if ($menu === null) {
            return response()->json(['success' => false, 'message' => 'Menu introuvable.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $menu,
        ]);
    }

    /**
     * Return content block JSON data by block slug.
     */
    public function block(string $slug): JsonResponse
    {
        if (! class_exists(Block::class) || ! Schema::hasTable('plugin_blocks')) {
            return response()->json(['success' => false, 'message' => 'Module Blocs non disponible.'], 404);
        }

        /** @var Block|null $block */
        $block = Block::query()->where('slug', $slug)->where('is_active', true)->first();

        if ($block === null) {
            return response()->json(['success' => false, 'message' => 'Bloc introuvable ou inactif.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $block,
        ]);
    }
}
