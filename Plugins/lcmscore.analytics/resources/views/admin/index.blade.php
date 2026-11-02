@extends('core::admin.layouts.app')

@section('title', __('Statistiques & Audience — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Statistiques d\'Audience RGPD') }}</h1>
            <p class="text-muted small mb-0">
                <i class="bi bi-shield-check text-success me-1"></i>
                {{ __('Mesure d\'audience 100% anonyme sans cookies, respectueuse de la vie privée (conforme CNIL)') }}
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <!-- Period Selector -->
            <div class="btn-group btn-group-sm shadow-sm" role="group">
                <a href="{{ url('/admin/analytics?period=today') }}" class="btn {{ $period === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ __('Aujourd\'hui') }}
                </a>
                <a href="{{ url('/admin/analytics?period=7d') }}" class="btn {{ $period === '7d' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ __('7 jours') }}
                </a>
                <a href="{{ url('/admin/analytics?period=30d') }}" class="btn {{ $period === '30d' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ __('30 jours') }}
                </a>
                <a href="{{ url('/admin/analytics?period=all') }}" class="btn {{ $period === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ __('Tout') }}
                </a>
            </div>

            @if($totalPageviews > 0)
                <form action="{{ url('/admin/analytics/clear') }}" method="POST" onsubmit="return confirm('{{ __('Purger l\'intégralité des statistiques d\'audience ?') }}');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                        <i class="bi bi-trash"></i> {{ __('Purger') }}
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
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Pages Vues') }}</span>
                    <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalPageviews, 0, ',', ' ') }}</h3>
                </div>
                <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-eye fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Visiteurs Uniques') }}</span>
                    <h3 class="fw-bold mb-0 text-success">{{ number_format($uniqueVisitors, 0, ',', ' ') }}</h3>
                </div>
                <div class="bg-success-subtle text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-people fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Pages / Visiteur') }}</span>
                    <h3 class="fw-bold mb-0 text-info">{{ $pagesPerVisitor }}</h3>
                </div>
                <div class="bg-info-subtle text-info rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-compass fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Conformité RGPD') }}</span>
                    <h4 class="fw-bold mb-0 text-success fs-6"><i class="bi bi-shield-lock-fill me-1"></i> 100% Anonyme</h4>
                    <span class="text-muted" style="font-size: 0.72rem;">0 cookie &bull; Hachage IP quotidien</span>
                </div>
                <div class="bg-success-subtle text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-cookie fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Trend Visual Chart -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold fs-6 mb-0 text-dark">
                <i class="bi bi-bar-chart-line-fill text-primary me-1"></i> {{ __('Évolution quotidienne de la fréquentation') }}
            </h5>
            <span class="badge bg-light text-secondary border font-monospace">Période : {{ $period }}</span>
        </div>
        <div class="card-body p-4">
            @if($dailyTrends->isNotEmpty())
                @php
                    $maxViews = max(1, $dailyTrends->max('views'));
                @endphp
                <div class="d-flex align-items-end justify-content-between gap-2 pt-4 pb-2" style="height: 160px; overflow-x: auto;">
                    @foreach($dailyTrends as $trend)
                        @php
                            $heightPercent = max(8, round(($trend->views / $maxViews) * 100));
                        @endphp
                        <div class="d-flex flex-column align-items-center flex-grow-1" style="min-width: 45px;">
                            <span class="text-muted font-monospace small mb-1" style="font-size: 0.7rem;">{{ $trend->views }}</span>
                            <div class="w-100 bg-primary rounded-3 shadow-sm transition-all"
                                 style="height: {{ $heightPercent }}%; min-height: 8px;"
                                 title="{{ $trend->date_key }} : {{ $trend->views }} vues ({{ $trend->visitors }} visiteurs)"></div>
                            <span class="text-muted font-monospace mt-2 text-truncate" style="font-size: 0.68rem; max-width: 60px;">
                                {{ \Carbon\Carbon::parse($trend->date_key)->format('d/m') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bar-chart fs-1 opacity-25 d-block mb-2"></i>
                    <p class="mb-0">{{ __('Aucune donnée enregistrée pour cette période.') }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Top Pages and Top Referrers Row -->
    <div class="row g-4 mb-4">
        <!-- Top Pages -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-dark">
                        <i class="bi bi-file-earmark-text text-primary me-1"></i> {{ __('Top 10 des Pages les plus consultées') }}
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light fs-7 text-uppercase text-muted">
                            <tr>
                                <th style="width: 60%;">{{ __('Page / Chemin') }}</th>
                                <th style="width: 20%;" class="text-end">{{ __('Vues') }}</th>
                                <th style="width: 20%;" class="text-end pe-3">{{ __('Visiteurs') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topPages as $page)
                                <tr>
                                    <td>
                                        <a href="{{ $page->path }}" target="_blank" class="text-decoration-none font-monospace small fw-semibold text-dark">
                                            {{ $page->path }}
                                        </a>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace">
                                            {{ $page->views }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <span class="badge bg-light text-secondary border rounded-pill font-monospace">
                                            {{ $page->visitors }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted small">
                                        {{ __('Aucune page consultée pour le moment.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Referrers -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-dark">
                        <i class="bi bi-box-arrow-up-right text-info me-1"></i> {{ __('Top 10 des Sources de trafic (Référents)') }}
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light fs-7 text-uppercase text-muted">
                            <tr>
                                <th style="width: 70%;">{{ __('Source / Domaine') }}</th>
                                <th style="width: 30%;" class="text-end pe-3">{{ __('Vues générées') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topReferrers as $ref)
                                <tr>
                                    <td>
                                        <span class="fw-semibold text-dark font-monospace small">
                                            @if($ref->source === 'Direct / Inconnu')
                                                <i class="bi bi-compass text-muted me-1"></i> {{ $ref->source }}
                                            @else
                                                <i class="bi bi-globe2 text-primary me-1"></i> {{ $ref->source }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace">
                                            {{ $ref->views }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted small">
                                        {{ __('Aucune source de trafic détectée.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Devices and Browsers Row -->
    <div class="row g-4">
        <!-- Devices Breakdown -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                <h5 class="card-title fw-bold fs-6 mb-3 text-dark">
                    <i class="bi bi-display text-secondary me-1"></i> {{ __('Répartition par Type d\'Appareil') }}
                </h5>
                @if($devices->isNotEmpty())
                    @foreach($devices as $dev)
                        @php
                            $devPercent = $totalPageviews > 0 ? round(($dev->count / $totalPageviews) * 100) : 0;
                            $icon = match($dev->device) {
                                'mobile' => 'bi-phone',
                                'tablet' => 'bi-tablet',
                                default => 'bi-laptop',
                            };
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <span class="fw-semibold text-capitalize text-dark">
                                    <i class="bi {{ $icon }} me-1 text-primary"></i> {{ $dev->device }}
                                </span>
                                <span class="text-muted font-monospace">{{ $dev->count }} ({{ $devPercent }}%)</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $devPercent }}%;" aria-valuenow="{{ $devPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted small mb-0">{{ __('Aucune donnée d\'appareil.') }}</p>
                @endif
            </div>
        </div>

        <!-- Browsers Breakdown -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                <h5 class="card-title fw-bold fs-6 mb-3 text-dark">
                    <i class="bi bi-browser-chrome text-warning me-1"></i> {{ __('Répartition par Navigateur') }}
                </h5>
                @if($browsers->isNotEmpty())
                    @foreach($browsers as $browser)
                        @php
                            $bPercent = $totalPageviews > 0 ? round(($browser->count / $totalPageviews) * 100) : 0;
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <span class="fw-semibold text-dark">
                                    <i class="bi bi-window me-1 text-muted"></i> {{ $browser->browser }}
                                </span>
                                <span class="text-muted font-monospace">{{ $browser->count }} ({{ $bPercent }}%)</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: {{ $bPercent }}%;" aria-valuenow="{{ $bPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted small mb-0">{{ __('Aucune donnée de navigateur.') }}</p>
                @endif
            </div>
        </div>
    </div>
@endsection
