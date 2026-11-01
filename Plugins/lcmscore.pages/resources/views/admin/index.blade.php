@extends('core::admin.layouts.app')

@section('title', __('Pages Management — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Static Pages') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Creation and management of publishable content pages') }}
            </p>
        </div>
        <div>
            <a href="/admin/pages/create" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-file-earmark-plus"></i> {{ __('Créer une nouvelle page') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search & Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form action="/admin/pages" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control border-start-0"
                               placeholder="{{ __('Rechercher par titre ou slug...') }}">
                        @if(!empty($search))
                            <a href="/admin/pages" class="btn btn-outline-secondary" title="{{ __('Réinitialiser') }}">
                                <i class="bi bi-x"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="col-md-6 col-lg-7 d-flex justify-content-md-end align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'all']) }}"
                           class="btn btn-outline-secondary {{ ($status ?? 'all') === 'all' ? 'active' : '' }}">
                            {{ __('Toutes') }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'published']) }}"
                           class="btn btn-outline-secondary {{ ($status ?? '') === 'published' ? 'active' : '' }}">
                            {{ __('Publiées') }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'draft']) }}"
                           class="btn btn-outline-secondary {{ ($status ?? '') === 'draft' ? 'active' : '' }}">
                            {{ __('Brouillons') }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'scheduled']) }}"
                           class="btn btn-outline-secondary {{ ($status ?? '') === 'scheduled' ? 'active' : '' }}">
                            {{ __('Programmées') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Pages Table -->
    <div class="card card-outline card-secondary shadow-sm rounded-4 border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="card-title fw-bold fs-6 mb-0 text-secondary">
                <i class="bi bi-files me-1"></i> {{ __('Pages enregistrées') }}
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light fs-7 text-uppercase text-muted">
                        <tr>
                            <th style="width: 32%;">{{ __('Titre & Image') }}</th>
                            <th style="width: 25%;">{{ __('Slug / Chemin') }}</th>
                            <th style="width: 15%;">{{ __('Modèle (Template)') }}</th>
                            <th style="width: 13%;">{{ __('Statut') }}</th>
                            <th style="width: 15%;" class="text-end pe-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pages as $page)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($page->featured_image)
                                            <img src="{{ $page->featured_image }}" alt="" class="rounded shadow-sm" style="width: 40px; height: 32px; object-fit: cover;">
                                        @else
                                            <div class="bg-light border rounded text-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 32px;">
                                                <i class="bi bi-file-earmark-text small"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-bold text-dark">
                                                <a href="/admin/pages/{{ $page->id }}/edit" class="text-dark text-decoration-none hover-primary">
                                                    {{ $page->title }}
                                                </a>
                                            </div>
                                            @if($page->parent)
                                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;">
                                                    <i class="bi bi-arrow-return-right me-1"></i>{{ $page->parent->title }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($page->is_published)
                                        <a href="/page/{{ $page->slug }}" target="_blank" class="text-primary text-decoration-none" rel="noopener">
                                            <code>/page/{{ $page->slug }}</code> <i class="bi bi-box-arrow-up-right small"></i>
                                        </a>
                                    @else
                                        <span class="text-muted"><code>/page/{{ $page->slug }}</code></span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border fw-normal">
                                        {{ $templates[$page->template] ?? $page->template }}
                                    </span>
                                </td>
                                <td>
                                    @if($page->status === 'published' || $page->is_published)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="bi bi-check-circle me-1"></i>{{ __('Publié') }}
                                        </span>
                                    @elseif($page->status === 'scheduled')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1" title="{{ $page->published_at ? $page->published_at->format('d/m/Y H:i') : '' }}">
                                            <i class="bi bi-clock me-1"></i>{{ __('Programmé') }}
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                            <i class="bi bi-pencil me-1"></i>{{ __('Brouillon') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/pages/{{ $page->id }}/edit" class="btn btn-outline-primary" title="{{ __('Modifier') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="/admin/pages/{{ $page->id }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Supprimer définitivement cette page ?') }}');">
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
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-file-earmark-x fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                    <h5>{{ __('Aucune page trouvée') }}</h5>
                                    <p class="small mb-3">{{ __('Commencez par créer votre première page de contenu.') }}</p>
                                    <a href="/admin/pages/create" class="btn btn-primary btn-sm rounded-pill px-3">
                                        <i class="bi bi-plus-lg me-1"></i> {{ __('Créer une page') }}
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
