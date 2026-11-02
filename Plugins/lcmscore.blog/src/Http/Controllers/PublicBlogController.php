<?php

declare(strict_types=1);

namespace Plugins\Blog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Plugins\Blog\Models\Category;
use Plugins\Blog\Models\Post;
use Plugins\Blog\Models\Tag;

class PublicBlogController extends Controller
{
    /**
     * Display public blog index page with articles, search, and category listing.
     */
    public function index(Request $request): View
    {
        $query = Post::queryPublished()->with(['category', 'author', 'tags'])->latest('published_at');

        $search = $request->query('search');
        if (is_string($search) && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('title', 'like', $term)
                    ->orWhere('excerpt', 'like', $term)
                    ->orWhere('content', 'like', $term);
            });
        }

        $posts = $query->paginate(9)->withQueryString();

        $featuredPosts = Post::queryFeatured(Post::queryPublished())
            ->with(['category', 'author'])
            ->latest('published_at')
            ->take(3)
            ->get();

        $categories = Category::query()
            ->whereHas('posts', function (Builder $q): void {
                $q->where('status', 'published')
                    ->where(function (Builder $sub): void {
                        $sub->whereNull('published_at')
                            ->orWhere('published_at', '<=', now());
                    });
            })
            ->withCount(['posts' => function (Builder $q): void {
                $q->where('status', 'published')
                    ->where(function (Builder $sub): void {
                        $sub->whereNull('published_at')
                            ->orWhere('published_at', '<=', now());
                    });
            }])
            ->orderBy('name')
            ->get();

        $tags = Tag::query()
            ->whereHas('posts', function (Builder $q): void {
                $q->where('status', 'published')
                    ->where(function (Builder $sub): void {
                        $sub->whereNull('published_at')
                            ->orWhere('published_at', '<=', now());
                    });
            })
            ->withCount(['posts' => function (Builder $q): void {
                $q->where('status', 'published')
                    ->where(function (Builder $sub): void {
                        $sub->whereNull('published_at')
                            ->orWhere('published_at', '<=', now());
                    });
            }])
            ->orderBy('name')
            ->get();

        return view('blog::public.index', compact(
            'posts',
            'featuredPosts',
            'categories',
            'tags',
            'search'
        ));
    }

    /**
     * Display a single post.
     */
    public function show(string $slug): View
    {
        /** @var Post $post */
        $post = Post::queryPublished()
            ->with(['category', 'author', 'tags'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment view count quietly
        Post::query()->where('id', $post->id)->increment('views_count');

        // Related posts in same category or matching tags
        $relatedPosts = Post::queryPublished()
            ->where('id', '!=', $post->id)
            ->where(function (Builder $q) use ($post): void {
                if ($post->category_id !== null) {
                    $q->where('category_id', $post->category_id);
                }
            })
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('blog::public.show', compact('post', 'relatedPosts'));
    }

    /**
     * Display posts belonging to a category.
     */
    public function category(string $slug): View
    {
        /** @var Category $category */
        $category = Category::query()->where('slug', $slug)->firstOrFail();

        $posts = Post::queryPublished()
            ->where('category_id', $category->id)
            ->with(['author', 'tags'])
            ->latest('published_at')
            ->paginate(9);

        $categories = Category::query()
            ->withCount('posts')
            ->orderBy('name')
            ->get();

        return view('blog::public.index', [
            'posts' => $posts,
            'currentCategory' => $category,
            'featuredPosts' => collect(),
            'categories' => $categories,
            'tags' => Tag::query()->withCount('posts')->get(),
            'search' => null,
        ]);
    }

    /**
     * Display posts associated with a tag.
     */
    public function tag(string $slug): View
    {
        /** @var Tag $tag */
        $tag = Tag::query()->where('slug', $slug)->firstOrFail();

        $posts = Post::queryPublished()
            ->whereHas('tags', function (Builder $q) use ($tag): void {
                $q->where('plugin_blog_tags.id', $tag->id);
            })
            ->with(['category', 'author', 'tags'])
            ->latest('published_at')
            ->paginate(9);

        $categories = Category::query()
            ->withCount('posts')
            ->orderBy('name')
            ->get();

        return view('blog::public.index', [
            'posts' => $posts,
            'currentTag' => $tag,
            'featuredPosts' => collect(),
            'categories' => $categories,
            'tags' => Tag::query()->withCount('posts')->get(),
            'search' => null,
        ]);
    }

    /**
     * Generate standard RSS 2.0 XML feed of latest articles.
     */
    public function feed(): Response
    {
        $posts = Post::queryPublished()
            ->with(['category', 'author'])
            ->latest('published_at')
            ->take(25)
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .view('blog::public.feed', compact('posts'))->render();

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=utf-8',
        ]);
    }
}
