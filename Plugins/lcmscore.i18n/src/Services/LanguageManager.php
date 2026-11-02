<?php

declare(strict_types=1);

namespace Plugins\I18n\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Plugins\I18n\Models\Language;
use Plugins\I18n\Models\Translation;

class LanguageManager
{
    /**
     * @var Collection<int, Language>|null
     */
    private ?Collection $cachedActive = null;

    /**
     * Retrieve all active languages.
     *
     * @return Collection<int, Language>
     */
    public function getActiveLanguages(): Collection
    {
        if ($this->cachedActive !== null) {
            return $this->cachedActive;
        }

        if (! Schema::hasTable('plugin_i18n_languages')) {
            return new Collection;
        }

        $this->cachedActive = Language::getActive();

        return $this->cachedActive;
    }

    /**
     * Retrieve default language.
     */
    public function getDefaultLanguage(): ?Language
    {
        if (! Schema::hasTable('plugin_i18n_languages')) {
            return null;
        }

        return Language::getDefault();
    }

    /**
     * Get current active locale code (e.g. 'fr', 'en').
     */
    public function getCurrentLocale(): string
    {
        $appLocale = App::getLocale();

        return $appLocale !== '' ? $appLocale : 'fr';
    }

    /**
     * Switch current locale in application and session.
     */
    public function setLocale(string $code): bool
    {
        $activeLanguages = $this->getActiveLanguages();
        $matched = $activeLanguages->firstWhere('code', $code);

        if ($matched === null) {
            return false;
        }

        App::setLocale($code);

        session(['cms_locale' => $code]);

        return true;
    }

    /**
     * Translate an entity field or dictionary key.
     */
    public function translate(string $type, ?int $id, string $field, ?string $fallback = null, ?string $locale = null): ?string
    {
        $targetLocale = $locale ?? $this->getCurrentLocale();

        $translated = Translation::getFor($type, $id, $targetLocale, $field);
        if ($translated !== null && trim($translated) !== '') {
            return $translated;
        }

        // If not found in target locale and target locale is not default, try default language
        $defaultLang = $this->getDefaultLanguage();
        if ($defaultLang !== null && $defaultLang->code !== $targetLocale) {
            $defaultTranslation = Translation::getFor($type, $id, $defaultLang->code, $field);
            if ($defaultTranslation !== null && trim($defaultTranslation) !== '') {
                return $defaultTranslation;
            }
        }

        return $fallback;
    }

    /**
     * Clear cached active languages.
     */
    public function clearCache(): void
    {
        $this->cachedActive = null;
    }
}
