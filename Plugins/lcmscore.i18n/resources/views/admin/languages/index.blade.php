@extends('core::admin.layouts.app')

@section('title', __('Gestion des Langues & Multilinguisme — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Langues & Multilinguisme') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Gérez les langues actives de votre CMS, définissez la langue par défaut et configurez le support RTL') }}
            </p>
        </div>
        <div>
            <a href="/admin/i18n/translations" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-translate"></i> {{ __('Dictionnaire & Traductions') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left: Add Language Form & Presets -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-dark">
                        <i class="bi bi-plus-circle text-primary me-1"></i> {{ __('Ajouter une langue') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <!-- 1-Click Presets -->
                    <div class="mb-4 pb-3 border-bottom">
                        <label class="form-label small fw-bold text-uppercase text-muted mb-2">
                            {{ __('Sélection rapide (Modèles)') }}
                        </label>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($presets as $p)
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 rounded-pill font-monospace" style="font-size: 0.75rem;"
                                        onclick="applyPreset('{{ $p['name'] }}', '{{ $p['code'] }}', '{{ $p['locale'] }}', '{{ $p['flag'] }}', {{ $p['rtl'] ? 'true' : 'false' }})">
                                    {{ $p['flag'] }} {{ strtoupper($p['code']) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <form action="/admin/i18n/languages" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="langName" class="form-label fw-semibold small text-dark mb-1">
                                {{ __('Nom de la langue') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="langName" name="name" required class="form-control" placeholder="{{ __('Ex: English') }}">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="langCode" class="form-label fw-semibold small text-dark mb-1">
                                    {{ __('Code ISO (2 car.)') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="langCode" name="code" required maxlength="10" class="form-control font-monospace" placeholder="en">
                            </div>
                            <div class="col-6">
                                <label for="langLocale" class="form-label fw-semibold small text-secondary mb-1">
                                    {{ __('Locale système') }}
                                </label>
                                <input type="text" id="langLocale" name="locale" required class="form-control font-monospace" placeholder="en_US">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="langFlag" class="form-label fw-semibold small text-secondary mb-1">
                                    {{ __('Drapeau (Emoji)') }}
                                </label>
                                <input type="text" id="langFlag" name="flag" class="form-control" placeholder="🇬🇧">
                            </div>
                            <div class="col-6">
                                <label for="langOrder" class="form-label fw-semibold small text-secondary mb-1">
                                    {{ __('Ordre de tri') }}
                                </label>
                                <input type="number" id="langOrder" name="order" value="0" min="0" class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="langIsRtl" name="is_rtl" value="1">
                            <label class="form-check-label small fw-semibold text-dark" for="langIsRtl">
                                {{ __('Sens d\'écriture de droite à gauche (RTL)') }}
                            </label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-semibold rounded-pill">
                                <i class="bi bi-save me-1"></i> {{ __('Enregistrer la langue') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Languages List Table -->
        <div class="col-lg-8">
            <div class="card card-outline card-secondary shadow-sm rounded-4 border-0 bg-white">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-secondary">
                        <i class="bi bi-globe me-1"></i> {{ __('Langues enregistrées') }} ({{ $languages->count() }})
                    </h5>
                    <div class="small text-muted font-monospace">
                        &#64;langSwitcher
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light fs-7 text-uppercase text-muted">
                                <tr>
                                    <th style="width: 8%;">{{ __('Drapeau') }}</th>
                                    <th style="width: 25%;">{{ __('Langue') }}</th>
                                    <th style="width: 15%;">{{ __('Code / Locale') }}</th>
                                    <th style="width: 20%;">{{ __('Propriétés') }}</th>
                                    <th style="width: 12%;">{{ __('Statut') }}</th>
                                    <th style="width: 20%;" class="text-end pe-3">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($languages as $language)
                                    <tr>
                                        <td class="fs-4 text-center">
                                            {{ $language->flag ?: '🌐' }}
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $language->name }}</div>
                                            @if($language->is_default)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.75rem;">
                                                    <i class="bi bi-star-fill me-1"></i> {{ __('Par défaut') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace">{{ $language->code }}</span>
                                            <span class="text-muted small d-block font-monospace">{{ $language->locale }}</span>
                                        </td>
                                        <td>
                                            @if($language->is_rtl)
                                                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill" style="font-size: 0.75rem;">RTL</span>
                                            @else
                                                <span class="badge bg-light text-muted border rounded-pill" style="font-size: 0.75rem;">LTR</span>
                                            @endif
                                            <span class="small text-muted ms-1">Ordre : {{ $language->order }}</span>
                                        </td>
                                        <td>
                                            @if($language->is_default)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                                    {{ __('Actif') }}
                                                </span>
                                            @else
                                                <form action="/admin/i18n/languages/{{ $language->id }}/toggle" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="badge {{ $language->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} rounded-pill px-2 py-1 border-0" style="cursor: pointer;">
                                                        <i class="bi {{ $language->is_active ? 'bi-check-circle-fill' : 'bi-dash-circle' }} me-1"></i>
                                                        {{ $language->is_active ? __('Actif') : __('Inactif') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm">
                                                @if(! $language->is_default)
                                                    <form action="/admin/i18n/languages/{{ $language->id }}/default" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-warning text-dark" title="{{ __('Définir comme langue par défaut') }}">
                                                            <i class="bi bi-star"></i>
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-outline-danger" title="{{ __('Supprimer') }}"
                                                            onclick="if(confirm('{{ __('Supprimer cette langue ?') }}')) { document.getElementById('delete-lang-{{ $language->id }}').submit(); }">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <form id="delete-lang-{{ $language->id }}" action="/admin/i18n/languages/{{ $language->id }}" method="POST" class="d-none">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-globe fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                            {{ __('Aucune langue enregistrée.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function applyPreset(name, code, locale, flag, isRtl) {
            document.getElementById('langName').value = name;
            document.getElementById('langCode').value = code;
            document.getElementById('langLocale').value = locale;
            document.getElementById('langFlag').value = flag;
            document.getElementById('langIsRtl').checked = isRtl;
        }
    </script>
@endpush
