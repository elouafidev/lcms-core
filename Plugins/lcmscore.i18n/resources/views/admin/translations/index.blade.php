@extends('core::admin.layouts.app')

@section('title', __('Dictionnaire & Traductions de Contenu — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="/admin/i18n/languages" class="btn btn-sm btn-outline-secondary rounded-circle" title="{{ __('Retour aux langues') }}">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0">{{ __('Traductions & Dictionnaire') }}</h1>
            </div>
            <p class="text-muted small mb-0 mt-1">
                {{ __('Gérez les traductions des champs d\'entités (Pages, Articles) et les chaînes de texte personnalisées') }}
            </p>
        </div>
        <div>
            <a href="/admin/i18n/languages" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-globe me-1"></i> {{ __('Gérer les langues') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left: Add/Update Translation Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-dark">
                        <i class="bi bi-plus-circle text-primary me-1"></i> {{ __('Ajouter une traduction') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="/admin/i18n/translations" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="transType" class="form-label fw-semibold small text-dark mb-1">
                                {{ __('Type d\'élément') }} <span class="text-danger">*</span>
                            </label>
                            <select id="transType" name="translatable_type" required class="form-select form-select-sm">
                                @foreach($types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="transId" class="form-label fw-semibold small text-secondary mb-1">
                                {{ __('ID Entité (Optionnel si chaîne générale)') }}
                            </label>
                            <input type="number" id="transId" name="translatable_id" class="form-control form-control-sm" placeholder="Ex: 1">
                        </div>

                        <div class="mb-3">
                            <label for="transLocale" class="form-label fw-semibold small text-dark mb-1">
                                {{ __('Langue cible') }} <span class="text-danger">*</span>
                            </label>
                            <select id="transLocale" name="locale" required class="form-select form-select-sm">
                                @foreach($languages as $lang)
                                    <option value="{{ $lang->code }}">{{ $lang->flag }} {{ $lang->name }} ({{ strtoupper($lang->code) }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="transField" class="form-label fw-semibold small text-dark mb-1">
                                {{ __('Clé / Nom du champ') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="transField" name="field" required class="form-control form-control-sm font-monospace" placeholder="Ex: title, content, welcome_message">
                        </div>

                        <div class="mb-3">
                            <label for="transValue" class="form-label fw-semibold small text-dark mb-1">
                                {{ __('Texte traduit') }} <span class="text-danger">*</span>
                            </label>
                            <textarea id="transValue" name="value" rows="4" required class="form-control" placeholder="{{ __('Saisissez la traduction...') }}"></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-semibold rounded-pill">
                                <i class="bi bi-save me-1"></i> {{ __('Enregistrer la traduction') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Translations Table -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-3">
                    <form action="/admin/i18n/translations" method="GET" class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control border-start-0" placeholder="{{ __('Rechercher par clé ou texte...') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="locale" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all">{{ __('Toutes les langues') }}</option>
                                @foreach($languages as $lang)
                                    <option value="{{ $lang->code }}" {{ ($locale ?? '') === $lang->code ? 'selected' : '' }}>
                                        {{ $lang->flag }} {{ $lang->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all">{{ __('Tous les types') }}</option>
                                @foreach($types as $k => $l)
                                    <option value="{{ $k }}" {{ ($type ?? '') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card card-outline card-secondary shadow-sm rounded-4 border-0 bg-white">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-secondary">
                        <i class="bi bi-list-columns me-1"></i> {{ __('Traductions enregistrées') }} ({{ $translations->total() }})
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light fs-7 text-uppercase text-muted">
                                <tr>
                                    <th style="width: 12%;">{{ __('Langue') }}</th>
                                    <th style="width: 15%;">{{ __('Type / ID') }}</th>
                                    <th style="width: 20%;">{{ __('Clé / Champ') }}</th>
                                    <th style="width: 43%;">{{ __('Texte traduit') }}</th>
                                    <th style="width: 10%;" class="text-end pe-3">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($translations as $trans)
                                    <tr>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace">{{ strtoupper($trans->locale) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary border rounded-pill">{{ $types[$trans->translatable_type] ?? $trans->translatable_type }}</span>
                                            @if($trans->translatable_id)
                                                <span class="small text-muted font-monospace d-block">#{{ $trans->translatable_id }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <code class="text-primary small font-monospace">{{ $trans->field }}</code>
                                        </td>
                                        <td>
                                            <div class="small text-dark text-truncate" style="max-width: 280px;" title="{{ $trans->value }}">
                                                {{ $trans->value }}
                                            </div>
                                        </td>
                                        <td class="text-end pe-3">
                                            <form action="/admin/i18n/translations/{{ $trans->id }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Supprimer cette traduction ?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger p-1" title="{{ __('Supprimer') }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-translate fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                            {{ __('Aucune traduction enregistrée.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($translations->hasPages())
                    <div class="card-footer bg-white border-top py-3">
                        {{ $translations->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
