<?php

declare(strict_types=1);

namespace Plugins\Blocks\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Plugins\Blocks\Models\Block;

class AdminBlockController extends Controller
{
    /**
     * Display a listing of blocks.
     */
    public function index(Request $request): View
    {
        $query = Block::query()->orderBy('order')->latest();

        $search = $request->query('search');
        if (is_string($search) && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('type', 'like', $term);
            });
        }

        $type = $request->query('type');
        if (is_string($type) && trim($type) !== '' && $type !== 'all') {
            $query->where('type', $type);
        }

        $blocks = $query->paginate(15)->withQueryString();

        $types = [
            'hero' => 'Bannière Héroïque (Hero)',
            'features' => 'Grille de Fonctionnalités',
            'cta' => 'Appel à l\'Action (CTA)',
            'faq' => 'Foire Aux Questions (FAQ)',
            'testimonials' => 'Témoignages Clients',
            'pricing' => 'Tableau de Tarifs',
            'custom_html' => 'Code HTML / CSS Sur-mesure',
        ];

        return view('blocks::admin.index', compact('blocks', 'types', 'search', 'type'));
    }

    /**
     * Show form to create a new block.
     */
    public function create(): View
    {
        $types = [
            'hero' => 'Bannière Héroïque (Hero)',
            'features' => 'Grille de Fonctionnalités (Services)',
            'cta' => 'Bannière d\'Appel à l\'Action (CTA)',
            'faq' => 'Foire Aux Questions (FAQ)',
            'testimonials' => 'Témoignages & Avis Clients',
            'pricing' => 'Tableau de Prix (Pricing)',
            'custom_html' => 'Code HTML / CSS Libre',
        ];

        return view('blocks::admin.form', [
            'block' => new Block(['type' => 'hero', 'is_active' => true, 'order' => 0]),
            'types' => $types,
            'isEdit' => false,
        ]);
    }

    /**
     * Store a newly created block.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:plugin_blocks,slug'],
            'type' => ['required', 'string', 'in:hero,features,cta,faq,testimonials,pricing,custom_html'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'content' => ['nullable'],
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $originalSlug = $slug;
        $counter = 1;
        while (Block::query()->where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter++;
        }

        $contentData = $this->normalizeContent($request, $validated['type']);

        Block::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'type' => $validated['type'],
            'content' => $contentData,
            'is_active' => $request->boolean('is_active', true),
            'order' => (int) ($validated['order'] ?? 0),
        ]);

        return redirect('/admin/blocks')->with('success', 'Bloc de contenu créé avec succès.');
    }

    /**
     * Show form to edit an existing block.
     */
    public function edit(int $id): View
    {
        /** @var Block $block */
        $block = Block::query()->findOrFail($id);

        $types = [
            'hero' => 'Bannière Héroïque (Hero)',
            'features' => 'Grille de Fonctionnalités (Services)',
            'cta' => 'Bannière d\'Appel à l\'Action (CTA)',
            'faq' => 'Foire Aux Questions (FAQ)',
            'testimonials' => 'Témoignages & Avis Clients',
            'pricing' => 'Tableau de Prix (Pricing)',
            'custom_html' => 'Code HTML / CSS Libre',
        ];

        return view('blocks::admin.form', [
            'block' => $block,
            'types' => $types,
            'isEdit' => true,
        ]);
    }

    /**
     * Update an existing block.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var Block $block */
        $block = Block::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:plugin_blocks,slug,'.$id],
            'type' => ['required', 'string', 'in:hero,features,cta,faq,testimonials,pricing,custom_html'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'content' => ['nullable'],
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $contentData = $this->normalizeContent($request, $validated['type']);

        $block->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'type' => $validated['type'],
            'content' => $contentData,
            'is_active' => $request->boolean('is_active'),
            'order' => (int) ($validated['order'] ?? 0),
        ]);

        return redirect('/admin/blocks')->with('success', 'Bloc de contenu mis à jour avec succès.');
    }

    /**
     * Delete an existing block.
     */
    public function destroy(int $id): RedirectResponse
    {
        $block = Block::query()->findOrFail($id);
        $block->delete();

        return redirect('/admin/blocks')->with('success', 'Bloc supprimé avec succès.');
    }

    /**
     * Toggle active state of a block via AJAX or POST.
     */
    public function toggle(int $id): JsonResponse|RedirectResponse
    {
        /** @var Block $block */
        $block = Block::query()->findOrFail($id);
        $block->is_active = ! $block->is_active;
        $block->save();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'is_active' => $block->is_active]);
        }

        return redirect('/admin/blocks')->with('success', 'Statut du bloc actualisé.');
    }

    /**
     * Preview a block rendered standalone.
     */
    public function preview(int $id): Response
    {
        /** @var Block $block */
        $block = Block::query()->findOrFail($id);

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>Aperçu — '.$block->name.'</title>'
            .'<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
            .'<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">'
            .'</head><body class="bg-light py-4">'
            .'<div class="container">'
            .'<div class="mb-3 text-muted small"><a href="/admin/blocks">&larr; Retour à l\'administration</a></div>'
            .$block->render()
            .'</div></body></html>';

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Normalize submitted form content based on block type.
     *
     * @return array<string, mixed>
     */
    private function normalizeContent(Request $request, string $type): array
    {
        $raw = $request->input('content');

        if (is_string($raw)) {
            /** @var array<string, mixed>|null $decoded */
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (is_array($raw)) {
            return $raw;
        }

        // Fallback: extract specific fields per type from request
        return match ($type) {
            'hero' => [
                'badge' => (string) $request->input('hero_badge', ''),
                'title' => (string) $request->input('hero_title', ''),
                'subtitle' => (string) $request->input('hero_subtitle', ''),
                'primary_btn_text' => (string) $request->input('hero_btn_primary_text', ''),
                'primary_btn_url' => (string) $request->input('hero_btn_primary_url', ''),
                'secondary_btn_text' => (string) $request->input('hero_btn_secondary_text', ''),
                'secondary_btn_url' => (string) $request->input('hero_btn_secondary_url', ''),
                'background_image' => (string) $request->input('hero_bg_image', ''),
                'style' => (string) $request->input('hero_style', 'dark'),
            ],
            'cta' => [
                'title' => (string) $request->input('cta_title', ''),
                'description' => (string) $request->input('cta_description', ''),
                'button_text' => (string) $request->input('cta_button_text', ''),
                'button_url' => (string) $request->input('cta_button_url', ''),
                'background_color' => (string) $request->input('cta_bg_color', '#2563eb'),
            ],
            'custom_html' => [
                'html' => (string) $request->input('custom_html', ''),
            ],
            default => [],
        };
    }
}
