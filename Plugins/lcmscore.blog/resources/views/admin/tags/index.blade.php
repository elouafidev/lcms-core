@extends('core::admin.layouts.app')

@section('title', __('Gestion des Étiquettes (Tags) de Blog — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="/admin/blog/posts" class="btn btn-sm btn-outline-secondary rounded-circle" title="{{ __('Retour aux articles') }}">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0">{{ __('Étiquettes (Tags)') }}</h1>
            </div>
            <p class="text-muted small mb-0 mt-1">
                {{ __('Mots-clés transversaux pour indexer et relier les articles du blog') }}
            </p>
        </div>
        <div>
            <a href="/admin/blog/posts" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-card-text me-1"></i> {{ __('Voir les articles') }}
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
        <!-- Left: Create Tag -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-dark">
                        <i class="bi bi-plus-circle text-primary me-1"></i> {{ __('Nouvelle étiquette') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="/admin/blog/tags" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="tagName" class="form-label fw-semibold small text-dark mb-1">
                                {{ __('Nom de l\'étiquette') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="tagName" name="name" required class="form-control" placeholder="{{ __('Ex: Tutoriel') }}">
                        </div>

                        <div class="mb-3">
                            <label for="tagSlug" class="form-label fw-semibold small text-secondary mb-1">
                                {{ __('Slug (Identifiant URL)') }}
                            </label>
                            <input type="text" id="tagSlug" name="slug" class="form-control font-monospace" placeholder="{{ __('ex: tutoriel (optionnel)') }}">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-semibold rounded-pill">
                                <i class="bi bi-save me-1"></i> {{ __('Créer l\'étiquette') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Tags Cloud & List -->
        <div class="col-lg-8">
            <div class="card card-outline card-secondary shadow-sm rounded-4 border-0 bg-white">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-secondary">
                        <i class="bi bi-tags-fill me-1"></i> {{ __('Étiquettes enregistrées') }} ({{ $tags->count() }})
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if($tags->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2 mb-4 p-3 bg-light rounded-3 border">
                            @foreach($tags as $tag)
                                <div class="badge bg-white text-dark border p-2 d-inline-flex align-items-center gap-2 shadow-xs" style="font-size: 0.9rem;">
                                    <span class="fw-semibold">#{{ $tag->name }}</span>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill" style="font-size: 0.75rem;">
                                        {{ $tag->posts_count ?? 0 }}
                                    </span>
                                    <form action="/admin/blog/tags/{{ $tag->id }}" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('{{ __('Supprimer cette étiquette ?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0 border-0 ms-1" style="font-size: 0.8rem; line-height: 1;">
                                            &times;
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-tags fs-1 text-secondary opacity-50 d-block mb-2"></i>
                            {{ __('Aucune étiquette pour l\'instant. Elles peuvent aussi être créées directement lors de la rédaction d\'un article.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
