<?php

declare(strict_types=1);

namespace Plugins\I18n\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\I18n\Models\Language;
use Plugins\I18n\Models\Translation;

class AdminTranslationController extends Controller
{
    /**
     * Display translations management page.
     */
    public function index(Request $request): View
    {
        $query = Translation::query()->latest();

        $locale = $request->query('locale');
        if (is_string($locale) && trim($locale) !== '' && $locale !== 'all') {
            $query->where('locale', $locale);
        }

        $type = $request->query('type');
        if (is_string($type) && trim($type) !== '' && $type !== 'all') {
            $query->where('translatable_type', $type);
        }

        $search = $request->query('search');
        if (is_string($search) && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('field', 'like', $term)
                    ->orWhere('value', 'like', $term);
            });
        }

        $translations = $query->paginate(20)->withQueryString();
        $languages = Language::query()->where('is_active', true)->orderBy('name')->get();

        $types = [
            'ui' => 'Chaîne d\'Interface (UI)',
            'page' => 'Page Statique',
            'post' => 'Article de Blog',
            'block' => 'Bloc de Contenu',
        ];

        return view('i18n::admin.translations.index', compact('translations', 'languages', 'types', 'locale', 'type', 'search'));
    }

    /**
     * Store or update a translation.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'translatable_type' => ['required', 'string', 'max:150'],
            'translatable_id' => ['nullable', 'integer'],
            'locale' => ['required', 'string', 'max:10', 'exists:plugin_i18n_languages,code'],
            'field' => ['required', 'string', 'max:100'],
            'value' => ['required', 'string'],
        ]);

        $transId = ! empty($validated['translatable_id']) ? (int) $validated['translatable_id'] : null;

        Translation::setFor(
            type: $validated['translatable_type'],
            id: $transId,
            locale: $validated['locale'],
            field: $validated['field'],
            value: $validated['value']
        );

        return redirect('/admin/i18n/translations')->with('success', 'Traduction enregistrée avec succès.');
    }

    /**
     * Delete a translation.
     */
    public function destroy(int $id): RedirectResponse
    {
        $translation = Translation::query()->findOrFail($id);
        $translation->delete();

        return redirect('/admin/i18n/translations')->with('success', 'Traduction supprimée avec succès.');
    }
}
