<?php

declare(strict_types=1);

namespace Plugins\I18n\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\I18n\Models\Language;
use Plugins\I18n\Services\LanguageManager;

class AdminLanguageController extends Controller
{
    public function __construct(
        private LanguageManager $languageManager
    ) {}

    /**
     * Display languages listing and management page.
     */
    public function index(): View
    {
        $languages = Language::query()
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        $presets = [
            'fr' => ['name' => 'Français', 'code' => 'fr', 'locale' => 'fr_FR', 'flag' => '🇫🇷', 'rtl' => false],
            'en' => ['name' => 'English', 'code' => 'en', 'locale' => 'en_US', 'flag' => '🇬🇧', 'rtl' => false],
            'es' => ['name' => 'Español', 'code' => 'es', 'locale' => 'es_ES', 'flag' => '🇪🇸', 'rtl' => false],
            'de' => ['name' => 'Deutsch', 'code' => 'de', 'locale' => 'de_DE', 'flag' => '🇩🇪', 'rtl' => false],
            'it' => ['name' => 'Italiano', 'code' => 'it', 'locale' => 'it_IT', 'flag' => '🇮🇹', 'rtl' => false],
            'pt' => ['name' => 'Português', 'code' => 'pt', 'locale' => 'pt_PT', 'flag' => '🇵🇹', 'rtl' => false],
            'ar' => ['name' => 'العربية (Arabe)', 'code' => 'ar', 'locale' => 'ar_SA', 'flag' => '🇸🇦', 'rtl' => true],
            'nl' => ['name' => 'Nederlands', 'code' => 'nl', 'locale' => 'nl_NL', 'flag' => '🇳🇱', 'rtl' => false],
        ];

        return view('i18n::admin.languages.index', compact('languages', 'presets'));
    }

    /**
     * Store a new language.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:10', 'unique:plugin_i18n_languages,code'],
            'locale' => ['required', 'string', 'max:20'],
            'flag' => ['nullable', 'string', 'max:20'],
            'is_rtl' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $hasAny = Language::query()->exists();
        $isDefault = ! $hasAny || $request->boolean('is_default');

        if ($isDefault) {
            Language::query()->update(['is_default' => false]);
        }

        Language::create([
            'name' => $validated['name'],
            'code' => strtolower($validated['code']),
            'locale' => $validated['locale'],
            'flag' => $validated['flag'] ?? null,
            'is_default' => $isDefault,
            'is_active' => true,
            'is_rtl' => $request->boolean('is_rtl'),
            'order' => (int) ($validated['order'] ?? 0),
        ]);

        $this->languageManager->clearCache();

        return redirect('/admin/i18n/languages')->with('success', 'Langue ajoutée avec succès.');
    }

    /**
     * Update an existing language.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var Language $language */
        $language = Language::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:10', 'unique:plugin_i18n_languages,code,'.$id],
            'locale' => ['required', 'string', 'max:20'],
            'flag' => ['nullable', 'string', 'max:20'],
            'is_rtl' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $language->update([
            'name' => $validated['name'],
            'code' => strtolower($validated['code']),
            'locale' => $validated['locale'],
            'flag' => $validated['flag'] ?? null,
            'is_rtl' => $request->boolean('is_rtl'),
            'order' => (int) ($validated['order'] ?? 0),
        ]);

        $this->languageManager->clearCache();

        return redirect('/admin/i18n/languages')->with('success', 'Langue mise à jour avec succès.');
    }

    /**
     * Set a language as system default.
     */
    public function makeDefault(int $id): RedirectResponse
    {
        /** @var Language $language */
        $language = Language::query()->findOrFail($id);
        $language->makeDefault();

        $this->languageManager->clearCache();

        return redirect('/admin/i18n/languages')->with('success', "'{$language->name}' est désormais la langue par défaut du site.");
    }

    /**
     * Toggle active state of a language.
     */
    public function toggle(int $id): RedirectResponse
    {
        /** @var Language $language */
        $language = Language::query()->findOrFail($id);

        if ($language->is_default) {
            return redirect('/admin/i18n/languages')->with('error', 'Impossible de désactiver la langue par défaut du site.');
        }

        $language->is_active = ! $language->is_active;
        $language->save();

        $this->languageManager->clearCache();

        return redirect('/admin/i18n/languages')->with('success', 'Statut de la langue actualisé.');
    }

    /**
     * Delete a language.
     */
    public function destroy(int $id): RedirectResponse
    {
        /** @var Language $language */
        $language = Language::query()->findOrFail($id);

        if ($language->is_default) {
            return redirect('/admin/i18n/languages')->with('error', 'Impossible de supprimer la langue par défaut.');
        }

        $language->delete();
        $this->languageManager->clearCache();

        return redirect('/admin/i18n/languages')->with('success', 'Langue supprimée avec succès.');
    }
}
