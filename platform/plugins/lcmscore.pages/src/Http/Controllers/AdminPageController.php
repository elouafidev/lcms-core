<?php

declare(strict_types=1);

namespace Plugins\Pages\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Plugins\Pages\Models\Page;

class AdminPageController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        /** @var Collection<int, Page> $pages */
        $pages = Page::query()->latest('id')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $pages,
            ]);
        }

        return view('pages::admin.index', [
            'pages' => $pages,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        /** @var array{title: string, slug?: ?string, content?: ?string, status?: ?string, is_published?: ?bool} $validated */
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:plugin_pages,slug'],
            'content' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,published'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $status = $validated['status'] ?? (isset($validated['is_published']) && $validated['is_published'] ? 'published' : 'draft');
        $isPublished = $status === 'published';

        $slugInput = $validated['slug'] ?? null;
        $slug = is_string($slugInput) && $slugInput !== ''
            ? Str::slug($slugInput)
            : Str::slug($validated['title']);

        /** @var Page $page */
        $page = Page::query()->create([
            'title' => $validated['title'],
            'slug' => $slug,
            'content' => $validated['content'] ?? null,
            'status' => $status,
            'is_published' => $isPublished,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Page created successfully.',
                'data' => $page,
            ], 201);
        }

        return redirect()->back()->with('success', __('Page created successfully.'));
    }

    public function show(int|string $id): JsonResponse
    {
        /** @var Page|null $page */
        $page = is_numeric($id)
            ? Page::query()->find((int) $id)
            : Page::query()->where('slug', (string) $id)->first();

        if ($page === null) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $page,
        ]);
    }

    public function destroy(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        /** @var Page|null $page */
        $page = is_numeric($id)
            ? Page::query()->find((int) $id)
            : Page::query()->where('slug', (string) $id)->first();

        if ($page === null) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page not found.',
                ], 404);
            }

            return redirect()->back()->withErrors(__('Page not found.'));
        }

        $page->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Page deleted successfully.',
            ]);
        }

        return redirect()->back()->with('success', __('Page deleted successfully.'));
    }
}
