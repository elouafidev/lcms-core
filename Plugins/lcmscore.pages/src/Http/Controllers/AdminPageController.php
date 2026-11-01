<?php

declare(strict_types=1);

namespace Plugins\Pages\Http\Controllers;

use Core\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Plugins\Pages\Models\Page;

class AdminPageController extends Controller
{
    /**
     * @return array<string, string>
     */
    public static function getAvailableTemplates(): array
    {
        return [
            'default' => 'Modèle par défaut',
            'full-width' => 'Pleine largeur (sans barre latérale)',
            'landing' => 'Page d\'atterrissage (Landing page)',
            'contact' => 'Page Contact',
        ];
    }

    public function index(Request $request): View|JsonResponse
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Page::query()->with('parent')->latest('id');

        if (is_string($search) && trim($search) !== '') {
            $clean = '%'.trim($search).'%';
            $query->where(function ($q) use ($clean): void {
                $q->where('title', 'LIKE', $clean)
                    ->orWhere('slug', 'LIKE', $clean);
            });
        }

        if (is_string($status) && $status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        /** @var Collection<int, Page> $pages */
        $pages = $query->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $pages,
            ]);
        }

        return view('pages::admin.index', [
            'pages' => $pages,
            'parents' => Page::query()->whereNull('parent_id')->orderBy('title')->get(),
            'templates' => self::getAvailableTemplates(),
            'search' => is_string($search) ? $search : '',
            'status' => is_string($status) ? $status : 'all',
        ]);
    }

    public function create(): View
    {
        $parents = Page::query()->whereNull('parent_id')->orderBy('title')->get();

        return view('pages::admin.form', [
            'page' => new Page(['status' => 'draft', 'template' => 'default', 'is_published' => false]),
            'parents' => $parents,
            'templates' => self::getAvailableTemplates(),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        /** @var array{title: string, slug?: ?string, content?: ?string, status?: ?string, is_published?: ?bool, parent_id?: ?int, template?: ?string, featured_image?: ?string, published_at?: ?string} $validated */
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:plugin_pages,slug'],
            'content' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,published,scheduled'],
            'is_published' => ['nullable', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:plugin_pages,id'],
            'template' => ['nullable', 'string', 'max:100'],
            'featured_image' => ['nullable', 'string', 'max:1000'],
            'published_at' => ['nullable', 'date'],
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
            'parent_id' => isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            'template' => $validated['template'] ?? 'default',
            'featured_image' => $validated['featured_image'] ?? null,
            'published_at' => isset($validated['published_at']) && $validated['published_at'] !== '' ? Carbon::parse($validated['published_at']) : null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Page created successfully.',
                'data' => $page,
            ], 201);
        }

        return redirect()->route('admin.pages.index')->with('success', __('Page created successfully.'));
    }

    public function edit(int|string $id): View
    {
        /** @var Page|null $page */
        $page = is_numeric($id)
            ? Page::query()->find((int) $id)
            : Page::query()->where('slug', (string) $id)->first();

        if ($page === null) {
            abort(404, 'Page introuvable.');
        }

        $parents = Page::query()
            ->where('id', '!=', $page->id)
            ->where(function ($q) use ($page): void {
                $q->whereNull('parent_id')
                    ->orWhere('parent_id', '!=', $page->id);
            })
            ->orderBy('title')
            ->get();

        return view('pages::admin.form', [
            'page' => $page,
            'parents' => $parents,
            'templates' => self::getAvailableTemplates(),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, int|string $id): JsonResponse|RedirectResponse
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

        /** @var array{title: string, slug?: ?string, content?: ?string, status?: ?string, is_published?: ?bool, parent_id?: ?int, template?: ?string, featured_image?: ?string, published_at?: ?string} $validated */
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:plugin_pages,slug,'.$page->id],
            'content' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,published,scheduled'],
            'is_published' => ['nullable', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:plugin_pages,id'],
            'template' => ['nullable', 'string', 'max:100'],
            'featured_image' => ['nullable', 'string', 'max:1000'],
            'published_at' => ['nullable', 'date'],
        ]);

        $status = $validated['status'] ?? (isset($validated['is_published']) && $validated['is_published'] ? 'published' : 'draft');
        $isPublished = $status === 'published';

        $slugInput = $validated['slug'] ?? null;
        $slug = is_string($slugInput) && $slugInput !== ''
            ? Str::slug($slugInput)
            : Str::slug($validated['title']);

        $parentId = isset($validated['parent_id']) && (int) $validated['parent_id'] !== $page->id
            ? (int) $validated['parent_id']
            : null;

        $page->fill([
            'title' => $validated['title'],
            'slug' => $slug,
            'content' => $validated['content'] ?? null,
            'status' => $status,
            'is_published' => $isPublished,
            'parent_id' => $parentId,
            'template' => $validated['template'] ?? 'default',
            'featured_image' => $validated['featured_image'] ?? null,
            'published_at' => isset($validated['published_at']) && $validated['published_at'] !== '' ? Carbon::parse($validated['published_at']) : null,
        ]);

        $page->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Page updated successfully.',
                'data' => $page,
            ]);
        }

        return redirect()->route('admin.pages.index')->with('success', __('Page updated successfully.'));
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
