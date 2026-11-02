@extends('core::admin.layouts.app')

@section('title', __('Gestion des Catégories de Blog — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="/admin/blog/posts" class="btn btn-sm btn-outline-secondary rounded-circle" title="{{ __('Retour aux articles') }}">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0">{{ __('Catégories de Blog') }}</h1>
            </div>
            <p class="text-muted small mb-0 mt-1">
                {{ __('Organisez vos articles par thématiques et dossiers éditoriaux') }}
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
        <!-- Left Column: Add Category Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-dark">
                        <i class="bi bi-plus-circle text-primary me-1"></i> {{ __('Ajouter une catégorie') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="/admin/blog/categories" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="catName" class="form-label fw-semibold small text-dark mb-1">
                                {{ __('Nom de la catégorie') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="catName" name="name" required class="form-control" placeholder="{{ __('Ex: Actualités') }}">
                        </div>

                        <div class="mb-3">
                            <label for="catSlug" class="form-label fw-semibold small text-secondary mb-1">
                                {{ __('Slug (Identifiant URL)') }}
                            </label>
                            <input type="text" id="catSlug" name="slug" class="form-control font-monospace" placeholder="{{ __('ex: actualites (optionnel)') }}">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="catColor" class="form-label fw-semibold small text-secondary mb-1">
                                    {{ __('Couleur badge') }}
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="color" id="catColor" name="color" value="#0d6efd" class="form-control form-control-color" style="max-width: 48px;">
                                    <input type="text" id="catColorText" class="form-control font-monospace" value="#0d6efd" onchange="document.getElementById('catColor').value = this.value">
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="catOrder" class="form-label fw-semibold small text-secondary mb-1">
                                    {{ __('Ordre d\'affichage') }}
                                </label>
                                <input type="number" id="catOrder" name="order" value="0" min="0" class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="catDesc" class="form-label fw-semibold small text-secondary mb-1">
                                {{ __('Description (facultatif)') }}
                            </label>
                            <textarea id="catDesc" name="description" rows="3" class="form-control" placeholder="{{ __('Courte présentation de la catégorie...') }}"></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-semibold rounded-pill">
                                <i class="bi bi-save me-1"></i> {{ __('Enregistrer la catégorie') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Categories List -->
        <div class="col-lg-8">
            <div class="card card-outline card-secondary shadow-sm rounded-4 border-0 bg-white">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-secondary">
                        <i class="bi bi-folder-fill me-1"></i> {{ __('Catégories existantes') }} ({{ $categories->count() }})
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light fs-7 text-uppercase text-muted">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 35%;">{{ __('Nom & Description') }}</th>
                                    <th style="width: 25%;">{{ __('Slug') }}</th>
                                    <th style="width: 15%;">{{ __('Articles') }}</th>
                                    <th style="width: 20%;" class="text-end pe-3">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $category)
                                    <tr>
                                        <td>
                                            <span class="badge rounded-circle p-2" style="background-color: {{ $category->color }};" title="{{ $category->color }}"></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $category->name }}</div>
                                            @if($category->description)
                                                <div class="small text-muted text-truncate" style="max-width: 260px;">{{ $category->description }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="font-monospace small text-muted">/blog/category/{{ $category->slug }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $category->posts_count ?? 0 }} {{ __('articles') }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <a href="/blog/category/{{ $category->slug }}" target="_blank" class="btn btn-outline-secondary" title="{{ __('Voir la page publique') }}">
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" title="{{ __('Supprimer') }}"
                                                        onclick="if(confirm('{{ __('Supprimer cette catégorie ? Les articles associés ne seront pas supprimés.') }}')) { document.getElementById('delete-cat-{{ $category->id }}').submit(); }">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <form id="delete-cat-{{ $category->id }}" action="/admin/blog/categories/{{ $category->id }}" method="POST" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-folder2-open fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                            {{ __('Aucune catégorie créée pour le moment.') }}
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
        document.getElementById('catColor')?.addEventListener('input', function() {
            document.getElementById('catColorText').value = this.value;
        });
    </script>
@endpush
