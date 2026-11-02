@extends('core::admin.layouts.app')

@section('title', __('Gestion du Blog & Actualités — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Blog & Actualités') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Publication d\'articles, gestion des catégories et étiquettes') }}
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="/admin/blog/categories" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-folder"></i> {{ __('Catégories') }}
            </a>
            <a href="/admin/blog/tags" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-tags"></i> {{ __('Étiquettes') }}
            </a>
            <a href="/admin/blog/posts/create" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-plus-circle"></i> {{ __('Nouvel article') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Articles') }}</span>
                    <h3 class="fw-bold mb-0 text-dark">{{ $totalCount }}</h3>
                </div>
                <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-card-text fs-4"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Articles Publiés') }}</span>
                    <h3 class="fw-bold mb-0 text-success">{{ $publishedCount }}</h3>
                </div>
                <div class="bg-success-subtle text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-check-circle fs-4"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Brouillons') }}</span>
                    <h3 class="fw-bold mb-0 text-warning">{{ $draftCount }}</h3>
                </div>
                <div class="bg-warning-subtle text-warning rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-pencil-square fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form action="/admin/blog/posts" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control border-start-0"
                               placeholder="{{ __('Rechercher par titre ou contenu...') }}">
                        @if(!empty($search))
                            <a href="/admin/blog/posts" class="btn btn-outline-secondary" title="{{ __('Réinitialiser') }}">
                                <i class="bi bi-x"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="col-md-4 col-lg-3">
                    <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">{{ __('Toutes les catégories') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (string)($categoryId ?? '') === (string)$category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 col-lg-5 d-flex justify-content-md-end align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'all']) }}"
                           class="btn btn-outline-secondary {{ ($status ?? 'all') === 'all' ? 'active' : '' }}">
                            {{ __('Tous') }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'published']) }}"
                           class="btn btn-outline-secondary {{ ($status ?? '') === 'published' ? 'active' : '' }}">
                            {{ __('Publiés') }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'draft']) }}"
                           class="btn btn-outline-secondary {{ ($status ?? '') === 'draft' ? 'active' : '' }}">
                            {{ __('Brouillons') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Posts Table -->
    <div class="card card-outline card-secondary shadow-sm rounded-4 border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h5 class="card-title fw-bold fs-6 mb-0 text-secondary">
                <i class="bi bi-files me-1"></i> {{ __('Liste des articles') }}
            </h5>
            <a href="/blog/feed.xml" target="_blank" class="btn btn-sm btn-outline-warning text-dark d-inline-flex align-items-center gap-1">
                <i class="bi bi-rss-fill text-warning"></i> {{ __('Flux RSS 2.0') }}
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light fs-7 text-uppercase text-muted">
                        <tr>
                            <th style="width: 32%;">{{ __('Article') }}</th>
                            <th style="width: 15%;">{{ __('Catégorie') }}</th>
                            <th style="width: 14%;">{{ __('Auteur') }}</th>
                            <th style="width: 12%;">{{ __('Statut') }}</th>
                            <th style="width: 12%;">{{ __('Vues / Lecture') }}</th>
                            <th style="width: 15%;" class="text-end pe-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($posts as $post)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($post->featured_image)
                                            <img src="{{ $post->featured_image }}" alt="" class="rounded shadow-sm" style="width: 48px; height: 38px; object-fit: cover;">
                                        @else
                                            <div class="bg-light border rounded text-secondary d-flex align-items-center justify-content-center" style="width: 48px; height: 38px;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-bold text-dark">
                                                <a href="/admin/blog/posts/{{ $post->id }}/edit" class="text-dark text-decoration-none">
                                                    {{ $post->title }}
                                                </a>
                                                @if($post->is_featured)
                                                    <span class="badge bg-warning text-dark ms-1"><i class="bi bi-star-fill"></i> {{ __('En avant') }}</span>
                                                @endif
                                            </div>
                                            <div class="small text-muted font-monospace">/blog/{{ $post->slug }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($post->category)
                                        <span class="badge px-2 py-1 rounded-pill" style="background-color: {{ $post->category->color }}20; color: {{ $post->category->color }}; border: 1px solid {{ $post->category->color }}40;">
                                            {{ $post->category->name }}
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">{{ $post->author?->name ?? 'Système' }}</div>
                                    <div class="text-muted fs-8">{{ $post->published_at ? $post->published_at->format('d/m/Y') : 'Non publié' }}</div>
                                </td>
                                <td>
                                    @if($post->status === 'published')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                            <i class="bi bi-check-circle-fill me-1"></i> {{ __('Publié') }}
                                        </span>
                                    @elseif($post->status === 'scheduled')
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill">
                                            <i class="bi bi-clock-history me-1"></i> {{ __('Planifié') }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill">
                                            <i class="bi bi-pencil-fill me-1"></i> {{ __('Brouillon') }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small text-muted"><i class="bi bi-eye"></i> {{ $post->views_count }} {{ __('vues') }}</div>
                                    <div class="small text-muted"><i class="bi bi-clock"></i> {{ $post->reading_time }} {{ __('min') }}</div>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        @if($post->status === 'published')
                                            <a href="/blog/{{ $post->slug }}" target="_blank" class="btn btn-outline-secondary" title="{{ __('Voir en ligne') }}">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        @endif
                                        <a href="/admin/blog/posts/{{ $post->id }}/edit" class="btn btn-outline-primary" title="{{ __('Modifier') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" title="{{ __('Supprimer') }}"
                                                onclick="if(confirm('{{ __('Supprimer définitivement cet article ?') }}')) { document.getElementById('delete-post-{{ $post->id }}').submit(); }">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <form id="delete-post-{{ $post->id }}" action="/admin/blog/posts/{{ $post->id }}" method="POST" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                    {{ __('Aucun article trouvé.') }}
                                    <div class="mt-2">
                                        <a href="/admin/blog/posts/create" class="btn btn-sm btn-primary rounded-pill">
                                            {{ __('Rédiger le premier article') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($posts->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
@endsection
