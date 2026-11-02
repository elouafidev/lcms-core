<?php

declare(strict_types=1);

namespace Plugins\Blog\Http\Controllers;

use Carbon\Carbon;
use Core\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Plugins\Blog\Models\Category;
use Plugins\Blog\Models\Post;
use Plugins\Blog\Models\Tag;

class AdminPostController extends Controller
{
    /**
     * Display a listing of blog posts with search and filters.
     */
    public function index(Request $request): View
    {
        $query = Post::query()->with(['category', 'author', 'tags'])->latest();

        $search = $request->query('search');
        if (is_string($search) && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('title', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('content', 'like', $term);
            });
        }

        $status = $request->query('status');
        if (is_string($status) && in_array($status, ['draft', 'published', 'scheduled'], true)) {
            $query->where('status', $status);
        }

        $categoryId = $request->query('category_id');
        if (is_numeric($categoryId)) {
            $query->where('category_id', (int) $categoryId);
        }

        $posts = $query->paginate(15)->withQueryString();

        $categories = Category::query()->orderBy('name')->get();
        $totalCount = Post::query()->count();
        $publishedCount = Post::query()->where('status', 'published')->count();
        $draftCount = Post::query()->where('status', 'draft')->count();

        return view('blog::admin.posts.index', compact(
            'posts',
            'categories',
            'totalCount',
            'publishedCount',
            'draftCount',
            'search',
            'status',
            'categoryId'
        ));
    }

    /**
     * Show form to create a new post.
     */
    public function create(): View
    {
        $categories = Category::query()->orderBy('order')->orderBy('name')->get();
        $authors = User::query()->where('is_active', true)->orderBy('name')->get();
        $tags = Tag::query()->orderBy('name')->get();

        return view('blog::admin.posts.form', [
            'post' => new Post,
            'categories' => $categories,
            'authors' => $authors,
            'tags' => $tags,
            'selectedTagNames' => '',
            'isEdit' => false,
        ]);
    }

    /**
     * Store a newly created post.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:plugin_blog_posts,slug'],
            'category_id' => ['nullable', 'integer', 'exists:plugin_blog_categories,id'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'string', 'in:draft,published,scheduled'],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['title']);

        $originalSlug = $slug;
        $counter = 1;
        while (Post::query()->where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter++;
        }

        $currentUser = $request->user();
        $authorId = ! empty($validated['author_id'])
            ? (int) $validated['author_id']
            : ($currentUser !== null ? (int) $currentUser->id : null);

        $publishedAt = null;
        if (! empty($validated['published_at'])) {
            $publishedAt = Carbon::parse($validated['published_at']);
        } elseif ($validated['status'] === 'published') {
            $publishedAt = now();
        }

        $readingTime = Post::calculateReadingTime($validated['content']);

        /** @var Post $post */
        $post = Post::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'category_id' => ! empty($validated['category_id']) ? (int) $validated['category_id'] : null,
            'author_id' => $authorId > 0 ? $authorId : null,
            'content' => $validated['content'],
            'excerpt' => $validated['excerpt'] ?? null,
            'featured_image' => $validated['featured_image'] ?? null,
            'status' => $validated['status'],
            'published_at' => $publishedAt,
            'reading_time' => $readingTime,
            'is_featured' => $request->boolean('is_featured'),
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);

        $this->syncTags($post, $request->input('tags'));

        return redirect('/admin/blog/posts')->with('success', 'Article créé avec succès.');
    }

    /**
     * Show form to edit an existing post.
     */
    public function edit(int $id): View
    {
        /** @var Post $post */
        $post = Post::query()->with('tags')->findOrFail($id);
        $categories = Category::query()->orderBy('order')->orderBy('name')->get();
        $authors = User::query()->where('is_active', true)->orderBy('name')->get();
        $tags = Tag::query()->orderBy('name')->get();
        $selectedTagNames = $post->tags->pluck('name')->implode(', ');

        return view('blog::admin.posts.form', [
            'post' => $post,
            'categories' => $categories,
            'authors' => $authors,
            'tags' => $tags,
            'selectedTagNames' => $selectedTagNames,
            'isEdit' => true,
        ]);
    }

    /**
     * Update an existing post.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var Post $post */
        $post = Post::query()->findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:plugin_blog_posts,slug,'.$id],
            'category_id' => ['nullable', 'integer', 'exists:plugin_blog_categories,id'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'string', 'in:draft,published,scheduled'],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['title']);

        $publishedAt = null;
        if (! empty($validated['published_at'])) {
            $publishedAt = Carbon::parse($validated['published_at']);
        } elseif ($validated['status'] === 'published') {
            $publishedAt = $post->published_at ?? now();
        }

        $readingTime = Post::calculateReadingTime($validated['content']);

        $post->update([
            'title' => $validated['title'],
            'slug' => $slug,
            'category_id' => ! empty($validated['category_id']) ? (int) $validated['category_id'] : null,
            'author_id' => ! empty($validated['author_id']) ? (int) $validated['author_id'] : $post->author_id,
            'content' => $validated['content'],
            'excerpt' => $validated['excerpt'] ?? null,
            'featured_image' => $validated['featured_image'] ?? null,
            'status' => $validated['status'],
            'published_at' => $publishedAt,
            'reading_time' => $readingTime,
            'is_featured' => $request->boolean('is_featured'),
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);

        $this->syncTags($post, $request->input('tags'));

        return redirect('/admin/blog/posts')->with('success', 'Article mis à jour avec succès.');
    }

    /**
     * Delete an existing post.
     */
    public function destroy(int $id): RedirectResponse
    {
        $post = Post::query()->findOrFail($id);
        $post->delete();

        return redirect('/admin/blog/posts')->with('success', 'Article supprimé avec succès.');
    }

    /**
     * Synchronize tags from comma-separated string or list.
     */
    private function syncTags(Post $post, mixed $rawTags): void
    {
        if (! is_string($rawTags) || trim($rawTags) === '') {
            $post->tags()->sync([]);

            return;
        }

        $tagNames = array_filter(array_map('trim', explode(',', $rawTags)));
        $tagIds = [];

        foreach ($tagNames as $name) {
            $slug = Str::slug($name);
            /** @var Tag $tag */
            $tag = Tag::query()->firstOrCreate(['slug' => $slug], ['name' => $name]);
            $tagIds[] = $tag->id;
        }

        $post->tags()->sync($tagIds);
    }
}
