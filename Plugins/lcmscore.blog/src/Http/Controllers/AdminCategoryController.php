<?php

declare(strict_types=1);

namespace Plugins\Blog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Plugins\Blog\Models\Category;

class AdminCategoryController extends Controller
{
    /**
     * Display categories management page.
     */
    public function index(): View
    {
        $categories = Category::query()
            ->withCount('posts')
            ->orderBy('order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return view('blog::admin.categories.index', compact('categories'));
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:plugin_blog_categories,slug'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:20'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Check uniqueness if auto-generated
        $originalSlug = $slug;
        $counter = 1;
        while (Category::query()->where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter++;
        }

        Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?: '#0d6efd',
            'order' => (int) ($validated['order'] ?? 0),
        ]);

        return redirect('/admin/blog/categories')->with('success', 'Catégorie créée avec succès.');
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $category = Category::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:plugin_blog_categories,slug,'.$id],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:20'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $category->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?: '#0d6efd',
            'order' => (int) ($validated['order'] ?? 0),
        ]);

        return redirect('/admin/blog/categories')->with('success', 'Catégorie mise à jour avec succès.');
    }

    /**
     * Delete a category.
     */
    public function destroy(int $id): RedirectResponse
    {
        $category = Category::query()->findOrFail($id);
        $category->delete();

        return redirect('/admin/blog/categories')->with('success', 'Catégorie supprimée avec succès.');
    }
}
