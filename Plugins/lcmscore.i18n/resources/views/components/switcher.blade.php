@php
    $languageManager = app(\Plugins\I18n\Services\LanguageManager::class);
    $activeLanguages = $languageManager->getActiveLanguages();
    $currentLocale = $languageManager->getCurrentLocale();
    $currentLang = $activeLanguages->firstWhere('code', $currentLocale) ?? $languageManager->getDefaultLanguage();
@endphp

@if($activeLanguages->count() > 1)
    <div class="dropdown d-inline-block lcms-language-switcher">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill px-3 d-inline-flex align-items-center gap-1 shadow-xs"
                type="button" id="languageSwitcherDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            @if($currentLang && $currentLang->flag)
                <span class="fs-6 me-1">{{ $currentLang->flag }}</span>
            @endif
            <span class="fw-semibold text-uppercase">{{ $currentLang ? $currentLang->code : $currentLocale }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 border-0 py-1" aria-labelledby="languageSwitcherDropdown">
            @foreach($activeLanguages as $lang)
                <li>
                    <a class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3 {{ $lang->code === $currentLocale ? 'active fw-bold' : '' }}"
                       href="{{ url('/lang/' . $lang->code) }}">
                        <span class="d-flex align-items-center gap-2">
                            @if($lang->flag)
                                <span>{{ $lang->flag }}</span>
                            @endif
                            <span>{{ $lang->name }}</span>
                        </span>
                        <span class="badge {{ $lang->code === $currentLocale ? 'bg-light text-dark' : 'bg-secondary-subtle text-secondary' }} rounded-pill font-monospace" style="font-size: 0.75rem;">
                            {{ strtoupper($lang->code) }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
