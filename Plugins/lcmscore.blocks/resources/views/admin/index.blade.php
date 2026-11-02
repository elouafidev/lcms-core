@extends('core::admin.layouts.app')

@section('title', __('Gestion des Blocs & Sections — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Blocs & Sections Réutilisables') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Créez des sections modulaires (Hero, FAQ, Témoignages, CTA) et insérez-les dans vos pages') }}
            </p>
        </div>
        <div>
            <a href="/admin/blocks/create" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-plus-circle"></i> {{ __('Créer un nouveau bloc') }}
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
            <form action="/admin/blocks" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control border-start-0"
                               placeholder="{{ __('Rechercher par nom ou slug...') }}">
                        @if(!empty($search))
                            <a href="/admin/blocks" class="btn btn-outline-secondary" title="{{ __('Réinitialiser') }}">
                                <i class="bi bi-x"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="col-md-4 col-lg-3">
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all">{{ __('Tous les types de blocs') }}</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" {{ ($type ?? '') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Blocks Table -->
    <div class="card card-outline card-secondary shadow-sm rounded-4 border-0 bg-white">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="card-title fw-bold fs-6 mb-0 text-secondary">
                <i class="bi bi-grid-1x2-fill me-1"></i> {{ __('Blocs configurés') }} ({{ $blocks->total() }})
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light fs-7 text-uppercase text-muted">
                        <tr>
                            <th style="width: 25%;">{{ __('Nom du Bloc') }}</th>
                            <th style="width: 15%;">{{ __('Type') }}</th>
                            <th style="width: 28%;">{{ __('Directive Blade d\'insertion') }}</th>
                            <th style="width: 12%;">{{ __('Statut') }}</th>
                            <th style="width: 20%;" class="text-end pe-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blocks as $block)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <a href="/admin/blocks/{{ $block->id }}/edit" class="text-dark text-decoration-none">
                                            {{ $block->name }}
                                        </a>
                                    </div>
                                    <div class="small text-muted font-monospace">{{ $block->slug }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1">
                                        {{ $types[$block->type] ?? $block->type }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-inline-flex align-items-center gap-1 bg-light border rounded px-2 py-1">
                                        <code class="text-danger small font-monospace user-select-all">&#64;renderBlock('{{ $block->slug }}')</code>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-secondary" title="{{ __('Copier') }}"
                                                onclick="copyBlockCode('{{ $block->slug }}')">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <form action="/admin/blocks/{{ $block->id }}/toggle" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="badge {{ $block->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} rounded-pill px-2 py-1 border-0" style="cursor: pointer;">
                                            <i class="bi {{ $block->is_active ? 'bi-check-circle-fill' : 'bi-dash-circle' }} me-1"></i>
                                            {{ $block->is_active ? __('Actif') : __('Désactivé') }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/blocks/{{ $block->id }}/preview" target="_blank" class="btn btn-outline-secondary" title="{{ __('Aperçu dans un nouvel onglet') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="/admin/blocks/{{ $block->id }}/edit" class="btn btn-outline-primary" title="{{ __('Modifier') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" title="{{ __('Supprimer') }}"
                                                onclick="if(confirm('{{ __('Supprimer définitivement ce bloc ?') }}')) { document.getElementById('delete-block-{{ $block->id }}').submit(); }">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <form id="delete-block-{{ $block->id }}" action="/admin/blocks/{{ $block->id }}" method="POST" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-grid-1x2 fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                    {{ __('Aucun bloc de contenu créé.') }}
                                    <div class="mt-2">
                                        <a href="/admin/blocks/create" class="btn btn-sm btn-primary rounded-pill">
                                            {{ __('Créer le premier bloc') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($blocks->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $blocks->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        function copyBlockCode(slug) {
            navigator.clipboard.writeText("@" + "renderBlock('" + slug + "')");
            alert("{{ __('Code copié dans le presse-papier !') }}");
        }
    </script>
@endpush
