<?php

declare(strict_types=1);

namespace Plugins\Blog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Plugins\Blog\Models\Tag;

class AdminTagController extends Controller
{
    /**
     * Display tags management page.
     */
    public function index(): View
    {
        $tags = Tag::query()
            ->withCount('posts')
            ->orderBy('name', 'asc')
            ->get();

        return view('blog::admin.tags.index', compact('tags'));
    }

    /**
     * Store a newly created tag.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:plugin_blog_tags,slug'],
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $originalSlug = $slug;
        $counter = 1;
        while (Tag::query()->where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter++;
        }

        Tag::create([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        return redirect('/admin/blog/tags')->with('success', 'Étiquette créée avec succès.');
    }

    /**
     * Delete a tag.
     */
    public function destroy(int $id): RedirectResponse
    {
        $tag = Tag::query()->findOrFail($id);
        $tag->delete();

        return redirect('/admin/blog/tags')->with('success', 'Étiquette supprimée avec succès.');
    }
}
