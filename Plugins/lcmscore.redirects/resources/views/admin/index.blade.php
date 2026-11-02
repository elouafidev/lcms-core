@extends('core::admin.layouts.app')

@section('title', __('Redirections & Détecteur 404 — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Redirections & Détecteur 404') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Préservez votre référencement SEO et résolvez les liens brisés sans toucher au serveur web') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            @if($tab === 'logs404' && $total404 > 0)
                <form action="{{ url('/admin/redirects/404/clear') }}" method="POST" onsubmit="return confirm('{{ __('Vider l\'intégralité du journal des erreurs 404 ?') }}');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                        <i class="bi bi-trash"></i> {{ __('Vider le journal 404') }}
                    </button>
                </form>
            @endif
            <a href="{{ url('/admin/redirects/create') }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-plus-lg"></i> {{ __('Nouvelle Redirection') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- KPI Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Redirections') }}</span>
                    <h3 class="fw-bold mb-0 text-dark">{{ $totalRedirects }}</h3>
                </div>
                <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-signpost-split fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Règles Actives') }}</span>
                    <h3 class="fw-bold mb-0 text-success">{{ $activeRedirects }}</h3>
                </div>
                <div class="bg-success-subtle text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-check-circle fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Clics Redirigés') }}</span>
                    <h3 class="fw-bold mb-0 text-info">{{ $totalHits }}</h3>
                </div>
                <div class="bg-info-subtle text-info rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-arrow-repeat fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Erreurs 404 Détectées') }}</span>
                    <h3 class="fw-bold mb-0 {{ $total404 > 0 ? 'text-danger' : 'text-dark' }}">{{ $total404 }} <small class="text-muted fs-6">({{ $total404Hits }} hits)</small></h3>
                </div>
                <div class="bg-danger-subtle text-danger rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-exclamation-octagon fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <ul class="nav nav-pills small">
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-3 py-1 {{ $tab === 'redirects' ? 'active' : '' }}" href="{{ url('/admin/redirects?tab=redirects'.($search ? '&search='.$search : '')) }}">
                            <i class="bi bi-arrow-left-right me-1"></i> {{ __('Règles de Redirection') }} ({{ $totalRedirects }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-3 py-1 {{ $tab === 'logs404' ? 'active' : '' }}" href="{{ url('/admin/redirects?tab=logs404'.($search ? '&search='.$search : '')) }}">
                            <i class="bi bi-cone-striped me-1"></i> {{ __('Journal des Erreurs 404') }}
                            @if($total404 > 0)
                                <span class="badge bg-danger ms-1">{{ $total404 }}</span>
                            @endif
                        </a>
                    </li>
                </ul>

                <form action="{{ url('/admin/redirects') }}" method="GET" class="d-flex gap-2">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <div class="input-group input-group-sm" style="max-width: 280px;">
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="{{ __('Rechercher une URL...') }}">
                        @if($search)
                            <a href="{{ url('/admin/redirects?tab='.$tab) }}" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
                        @endif
                        <button class="btn btn-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($tab === 'redirects')
        <!-- Redirect Rules Table -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light fs-7 text-uppercase text-muted">
                        <tr>
                            <th style="width: 30%;">{{ __('Chemin Source') }}</th>
                            <th style="width: 30%;">{{ __('Destination Cible') }}</th>
                            <th style="width: 10%;">{{ __('Type') }}</th>
                            <th style="width: 10%;">{{ __('Clics') }}</th>
                            <th style="width: 10%;">{{ __('Statut') }}</th>
                            <th style="width: 10%;" class="text-end pe-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($redirects as $rule)
                            <tr>
                                <td>
                                    <div class="font-monospace fw-semibold text-dark">{{ $rule->source_path }}</div>
                                    @if($rule->description)
                                        <div class="small text-muted">{{ $rule->description }}</div>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ $rule->target_url }}" target="_blank" class="text-decoration-none text-primary font-monospace small">
                                        {{ $rule->target_url }} <i class="bi bi-box-arrow-up-right" style="font-size: 0.7rem;"></i>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge {{ $rule->status_code === 301 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} font-monospace">
                                        {{ $rule->status_code }} {{ $rule->status_code === 301 ? 'Permanent' : 'Temporaire' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border font-monospace">{{ $rule->hit_count }}</span>
                                    @if($rule->last_used_at)
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ $rule->last_used_at->format('d/m/Y') }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($rule->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                            {{ __('Actif') }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2 py-1">
                                            {{ __('Inactif') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <form action="{{ url('/admin/redirects/'.$rule->id.'/toggle') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-secondary" title="{{ $rule->is_active ? __('Désactiver') : __('Activer') }}">
                                                <i class="bi bi-power"></i>
                                            </button>
                                        </form>
                                        <a href="{{ url('/admin/redirects/'.$rule->id.'/edit') }}" class="btn btn-outline-primary" title="{{ __('Modifier') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ url('/admin/redirects/'.$rule->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Supprimer cette règle ?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="{{ __('Supprimer') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-signpost-split fs-1 d-block mb-2 opacity-25"></i>
                                    <p class="mb-0">{{ __('Aucune règle de redirection configurée.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($redirects->hasPages())
                <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                    {{ $redirects->links() }}
                </div>
            @endif
        </div>
    @else
        <!-- 404 Error Logs Table -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light fs-7 text-uppercase text-muted">
                        <tr>
                            <th style="width: 35%;">{{ __('Chemin Introuvable (404)') }}</th>
                            <th style="width: 10%;">{{ __('Occurrences') }}</th>
                            <th style="width: 25%;">{{ __('Référent (Origine)') }}</th>
                            <th style="width: 15%;">{{ __('Dernière visite') }}</th>
                            <th style="width: 15%;" class="text-end pe-3">{{ __('Action Rapide') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs404 as $log)
                            <tr>
                                <td>
                                    <span class="font-monospace fw-semibold text-danger">{{ $log->path }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill font-monospace px-2 py-1">
                                        {{ $log->hits }} {{ $log->hits > 1 ? 'erreurs' : 'erreur' }}
                                    </span>
                                </td>
                                <td>
                                    @if($log->referrer)
                                        <span class="small text-muted font-monospace text-truncate d-inline-block" style="max-width: 250px;" title="{{ $log->referrer }}">
                                            {{ $log->referrer }}
                                        </span>
                                    @else
                                        <span class="small text-muted fst-italic">{{ __('Direct / Inconnu') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small text-muted">
                                        {{ $log->last_hit_at ? $log->last_hit_at->format('d/m/Y H:i') : '—' }}
                                    </div>
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1"
                                            data-bs-toggle="modal" data-bs-target="#resolveModal{{ $log->id }}">
                                        <i class="bi bi-arrow-right-circle"></i> {{ __('Rediriger') }}
                                    </button>

                                    <!-- Quick Modal to Resolve 404 into a 301 Redirect -->
                                    <div class="modal fade text-start" id="resolveModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <form action="{{ url('/admin/redirects/404/'.$log->id.'/convert') }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header border-bottom py-3">
                                                        <h5 class="modal-title fw-bold fs-6">
                                                            <i class="bi bi-signpost me-1 text-primary"></i> {{ __('Créer une redirection 301 pour ce lien 404') }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body py-4">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small">{{ __('Chemin source (404)') }}</label>
                                                            <input type="text" class="form-control bg-light font-monospace" value="{{ $log->path }}" readonly>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small">{{ __('URL de destination') }} <span class="text-danger">*</span></label>
                                                            <input type="text" name="target_url" class="form-control font-monospace" required placeholder="/nouvelle-page ou https://...">
                                                            <div class="form-text small">
                                                                {{ __('Tous les futurs visiteurs de ce lien seront automatiquement redirigés vers cette nouvelle cible.') }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top py-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                                                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-4">{{ __('Créer la redirection') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <form action="{{ url('/admin/redirects/404/'.$log->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Supprimer cette entrée 404 ?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-1" title="{{ __('Supprimer') }}" style="width: 30px; height: 30px;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-check fs-1 d-block mb-2 text-success opacity-50"></i>
                                    <p class="mb-0">{{ __('Aucune erreur 404 détectée ! Votre site est en parfaite santé.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs404->hasPages())
                <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                    {{ $logs404->links() }}
                </div>
            @endif
        </div>
    @endif
@endsection
