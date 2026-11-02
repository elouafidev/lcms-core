@extends('core::admin.layouts.app')

@section('title', __('Statistiques & Moteur de Recherche — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Moteur de Recherche & Analytics') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Analysez les termes recherchés par vos visiteurs et identifiez les contenus manquants') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/search" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-box-arrow-up-right"></i> {{ __('Tester la recherche publique') }}
            </a>
            @if($totalSearches > 0)
                <form action="/admin/search/clear" method="POST" onsubmit="return confirm('{{ __('Effacer l\'ensemble de l\'historique des requêtes ?') }}');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                        <i class="bi bi-trash"></i> {{ __('Vider l\'historique') }}
                    </button>
                </form>
            @endif
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
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Requêtes') }}</span>
                    <h3 class="fw-bold mb-0 text-dark">{{ $totalSearches }}</h3>
                </div>
                <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-search fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Recherches à 0 Résultat') }}</span>
                    <h3 class="fw-bold mb-0 text-danger">{{ $zeroResultSearches }}</h3>
                </div>
                <div class="bg-danger-subtle text-danger rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-exclamation-octagon fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Taux de Succès') }}</span>
                    @php
                        $successRate = $totalSearches > 0 ? round((($totalSearches - $zeroResultSearches) / $totalSearches) * 100) : 100;
                    @endphp
                    <h3 class="fw-bold mb-0 text-success">{{ $successRate }}%</h3>
                </div>
                <div class="bg-success-subtle text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-graph-up-arrow fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Top Queries & Opportunities -->
        <div class="col-lg-6">
            <!-- Top Queries -->
            <div class="card card-outline card-primary shadow-sm rounded-4 border-0 bg-white mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-dark">
                        <i class="bi bi-trophy text-warning me-1"></i> {{ __('Mots-clés les plus recherchés') }}
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light fs-7 text-uppercase text-muted">
                                <tr>
                                    <th style="width: 55%;">{{ __('Terme recherché') }}</th>
                                    <th style="width: 20%;" class="text-center">{{ __('Occurrences') }}</th>
                                    <th style="width: 25%;" class="text-end pe-3">{{ __('Moy. résultats') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topQueries as $top)
                                    <tr>
                                        <td>
                                            <a href="{{ url('/search?q=' . urlencode($top->query)) }}" target="_blank" class="fw-semibold text-dark text-decoration-none">
                                                « {{ $top->query }} »
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1">
                                                {{ $top->count }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-3 text-muted small">
                                            {{ round((float)$top->avg_results, 1) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted small">
                                            {{ __('Aucune recherche enregistrée.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Zero Results (Editorial Opportunities) -->
            <div class="card card-outline card-danger shadow-sm rounded-4 border-0 bg-white">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-danger">
                        <i class="bi bi-lightbulb me-1 text-warning"></i> {{ __('Opportunités de contenu (0 résultat)') }}
                    </h5>
                    <span class="badge bg-danger-subtle text-danger rounded-pill">{{ $zeroQueries->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light fs-7 text-uppercase text-muted">
                                <tr>
                                    <th style="width: 70%;">{{ __('Terme sans réponse') }}</th>
                                    <th style="width: 30%;" class="text-end pe-3">{{ __('Demandes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($zeroQueries as $zero)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold text-danger">« {{ $zero->query }} »</span>
                                            <div class="small text-muted" style="font-size: 0.75rem;">
                                                {{ __('Conseil : Créez une page ou un article sur ce sujet.') }}
                                            </div>
                                        </td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-danger text-white rounded-pill px-2 py-1">
                                                {{ $zero->count }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center py-4 text-muted small">
                                            {{ __('Excellente nouvelle ! Aucune recherche sans résultat.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Recent Activity Logs -->
        <div class="col-lg-6">
            <div class="card card-outline card-secondary shadow-sm rounded-4 border-0 bg-white">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-secondary">
                        <i class="bi bi-clock-history me-1"></i> {{ __('Dernières requêtes en temps réel') }}
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light fs-7 text-uppercase text-muted">
                                <tr>
                                    <th style="width: 45%;">{{ __('Requête') }}</th>
                                    <th style="width: 25%;">{{ __('Résultats') }}</th>
                                    <th style="width: 30%;" class="text-end pe-3">{{ __('Date / Heure') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentLogs as $log)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold text-dark">« {{ $log->query }} »</span>
                                            @if($log->ip_address)
                                                <span class="small text-muted font-monospace d-block" style="font-size: 0.7rem;">{{ $log->ip_address }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->results_count > 0)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                                    {{ $log->results_count }}
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">
                                                    0
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3 text-muted small">
                                            {{ $log->created_at ? $log->created_at->format('d/m H:i') : '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted small">
                                            {{ __('Aucune recherche récente enregistrée.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($recentLogs->hasPages())
                    <div class="card-footer bg-white border-top py-3">
                        {{ $recentLogs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
