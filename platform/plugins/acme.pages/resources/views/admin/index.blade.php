@extends('core::admin.layouts.app')

@section('title', 'Gestion des Pages — Administration')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">Pages Statiques</h1>
            <p class="text-muted small mb-0">
                Création et gestion des pages de contenu publiables
            </p>
        </div>
    </div>

    <!-- Create Page Card -->
    <div class="card card-outline card-primary shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title fw-bold fs-6 mb-0 text-primary">
                <i class="bi bi-file-earmark-plus me-1"></i> Créer une nouvelle page
            </h5>
        </div>
        <div class="card-body">
            <form action="/admin/pages" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label fw-semibold small">Titre :</label>
                        <input type="text" id="title" name="title" required class="form-control" placeholder="Titre de la page">
                    </div>
                    <div class="col-md-6">
                        <label for="slug" class="form-label fw-semibold small">Slug (optionnel) :</label>
                        <input type="text" id="slug" name="slug" placeholder="ex: a-propos" class="form-control">
                    </div>
                    <div class="col-12">
                        <label for="content" class="form-label fw-semibold small">Contenu :</label>
                        <textarea id="content" name="content" rows="5" class="form-control" placeholder="Contenu HTML ou texte de la page..."></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_published" value="1" id="isPublishedSwitch">
                            <label class="form-check-label fw-semibold small" for="isPublishedSwitch">
                                Publier immédiatement
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <i class="bi bi-save"></i> Enregistrer la page
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Pages Table -->
    <div class="card card-outline card-secondary shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title fw-bold fs-6 mb-0 text-secondary">
                <i class="bi bi-files me-1"></i> Pages existantes
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light fs-7 text-uppercase text-muted">
                        <tr>
                            <th style="width: 40%;">Titre</th>
                            <th style="width: 35%;">Slug / URL</th>
                            <th style="width: 15%;">Statut</th>
                            <th style="width: 10%;" class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pages as $page)
                            <tr>
                                <td><strong>{{ $page->title }}</strong></td>
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
                                    <span class="badge {{ $page->is_published ? 'badge-active text-bg-success' : 'badge-disabled text-bg-warning' }} px-2 py-1">
                                        {{ $page->is_published ? 'Publié' : 'Brouillon' }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <form action="/admin/pages/{{ $page->id }}" method="POST" class="actions-form" onsubmit="return confirm('Supprimer cette page ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash me-1"></i> Supprimer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4 small">
                                    Aucune page créée pour le moment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
