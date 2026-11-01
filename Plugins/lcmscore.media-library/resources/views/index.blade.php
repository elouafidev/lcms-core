@extends('core::admin.layouts.app')

@section('title', __('Media Library — Administration'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/plugins/lcmscore.media-library/css/media.css') }}">
    <style>
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 1.25rem; }
        .media-card { background: white; border-radius: 0.85rem; border: 1px solid var(--border-color, #e2e8f0); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: transform 0.15s ease, box-shadow 0.15s ease; }
        .media-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .media-preview { height: 145px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border-bottom: 1px solid var(--border-color, #e2e8f0); position: relative; }
        .media-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .media-body { padding: 0.9rem; font-size: 0.875rem; }
        .dropzone-box { border: 2px dashed #cbd5e1; border-radius: 1rem; background: #f8fafc; transition: all 0.2s ease; cursor: pointer; }
        .dropzone-box:hover, .dropzone-box.dragover { border-color: #0d6efd; background: #eff6ff; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Media Library') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Secure storage, organization and management of media files') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1 shadow-sm"
                    type="button" data-bs-toggle="collapse" data-bs-target="#uploadCollapse" aria-expanded="false" aria-controls="uploadCollapse">
                <i class="bi bi-cloud-arrow-up"></i> {{ __('Téléverser un média') }}
            </button>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-error shadow-sm mb-4">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ __('Errors:') }}</h6>
            <ul class="mb-0 ps-3 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Upload Card (Collapsible) -->
    <div class="collapse mb-4 {{ request()->has('upload') ? 'show' : '' }}" id="uploadCollapse">
        <div class="card card-outline card-primary shadow-sm rounded-4 border-0">
            <div class="card-body p-4">
                <form action="/admin/media/upload" method="POST" enctype="multipart/form-data" id="uploadMediaForm">
                    @csrf
                    <div class="dropzone-box p-4 text-center mb-3" onclick="document.getElementById('mediaFileInput').click()">
                        <i class="bi bi-cloud-arrow-up display-4 text-primary opacity-75 mb-2 d-block"></i>
                        <h6 class="fw-bold mb-1">{{ __('Glissez et déposez votre fichier ici') }}</h6>
                        <p class="text-muted small mb-2">{{ __('ou cliquez pour parcourir vos dossiers locaux') }}</p>
                        <input type="file" id="mediaFileInput" name="file" required class="d-none">
                        <span id="selectedFileName" class="badge bg-primary text-wrap d-none"></span>
                    </div>

                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <label for="mediaAltText" class="form-label small fw-semibold text-secondary mb-1">
                                {{ __('Texte alternatif / Balise Alt (recommandé pour le SEO et l\'accessibilité)') }}
                            </label>
                            <input type="text" id="mediaAltText" name="alt_text" class="form-control form-control-sm rounded-pill px-3"
                                   placeholder="{{ __('Ex: Logo de l\'entreprise en haute définition') }}">
                        </div>
                        <div class="col-md-4 text-md-end mt-md-4">
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                                <i class="bi bi-upload me-1"></i> {{ __('Démarrer l\'envoi') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form action="/admin/media" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control border-start-0"
                               placeholder="{{ __('Rechercher par nom de fichier ou texte alternatif...') }}">
                        @if(!empty($search))
                            <a href="/admin/media" class="btn btn-outline-secondary" title="{{ __('Réinitialiser') }}">
                                <i class="bi bi-x"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="col-md-6 col-lg-7 d-flex justify-content-md-end align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ request()->fullUrlWithQuery(['type' => 'all']) }}"
                           class="btn btn-outline-secondary {{ ($type ?? 'all') === 'all' ? 'active' : '' }}">
                            {{ __('Tous') }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['type' => 'image']) }}"
                           class="btn btn-outline-secondary {{ ($type ?? '') === 'image' ? 'active' : '' }}">
                            <i class="bi bi-image me-1"></i> {{ __('Images') }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['type' => 'document']) }}"
                           class="btn btn-outline-secondary {{ ($type ?? '') === 'document' ? 'active' : '' }}">
                            <i class="bi bi-file-earmark-text me-1"></i> {{ __('Documents') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($items->isEmpty())
        <div class="card card-outline card-secondary shadow-sm text-center text-muted py-5 rounded-4 border-0">
            <i class="bi bi-images fs-1 d-block mb-2 text-muted opacity-50"></i>
            <h4>{{ __('Aucun média trouvé') }}</h4>
            <p class="small mb-0">{{ __('Utilisez le bouton de téléversement ci-dessus pour ajouter vos premiers fichiers.') }}</p>
        </div>
    @else
        <div class="media-grid">
            @foreach($items as $item)
                <div class="media-card">
                    <div class="media-preview">
                        @if($item->isImage())
                            <img src="{{ $item->url }}" alt="{{ $item->alt_text ?? $item->name }}" loading="lazy">
                        @else
                            <i class="bi bi-file-earmark-text text-secondary fs-1"></i>
                        @endif
                        <span class="badge bg-dark bg-opacity-75 position-absolute top-0 end-0 m-2 font-monospace small" style="font-size: 0.68rem;">
                            {{ $item->formatted_size }}
                        </span>
                    </div>

                    <div class="media-body">
                        <div class="fw-bold text-truncate" title="{{ $item->name }}">
                            {{ $item->name }}
                        </div>
                        <div class="small text-muted mt-1 text-truncate" title="{{ $item->alt_text ?? __('Aucun texte alt') }}">
                            <i class="bi bi-card-text me-1"></i> {{ $item->alt_text ? $item->alt_text : __('Sans description Alt') }}
                        </div>

                        <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center gap-1">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ $item->url }}" target="_blank" rel="noopener" class="btn btn-light border py-1 px-2" title="{{ __('Voir le fichier') }}">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-light border py-1 px-2 btn-copy-url" data-url="{{ $item->url }}" title="{{ __('Copier le lien direct') }}">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                                <button type="button" class="btn btn-light border py-1 px-2 btn-edit-media"
                                        data-id="{{ $item->id }}"
                                        data-name="{{ $item->name }}"
                                        data-alt="{{ $item->alt_text }}"
                                        data-url="{{ $item->url }}"
                                        title="{{ __('Modifier les métadonnées') }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>

                            <form action="/admin/media/{{ $item->id }}" method="POST" onsubmit="return confirm('{{ __('Supprimer définitivement ce fichier ?') }}');" class="m-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="{{ __('Supprimer') }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $items->links() }}
        </div>
    @endif

    <!-- Edit Media Metadata Modal -->
    <div class="modal fade" id="editMediaModal" tabindex="-1" aria-labelledby="editMediaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form id="editMediaForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-bottom px-4 py-3">
                        <h5 class="modal-title fw-bold" id="editMediaModalLabel">
                            <i class="bi bi-pencil-square text-primary me-1"></i> {{ __('Modifier le média') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="editMediaName" class="form-label small fw-semibold text-secondary">{{ __('Nom du fichier') }}</label>
                            <input type="text" id="editMediaName" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editMediaAlt" class="form-label small fw-semibold text-secondary">{{ __('Texte alternatif (Alt)') }}</label>
                            <input type="text" id="editMediaAlt" name="alt_text" class="form-control" placeholder="{{ __('Description pour le référencement et l\'accessibilité') }}">
                        </div>
                        <div>
                            <label class="form-label small fw-semibold text-secondary">{{ __('URL publique') }}</label>
                            <input type="text" id="editMediaUrl" class="form-control form-control-sm bg-light" readonly>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-2">
                        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold">{{ __('Enregistrer les modifications') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // File input change detection
    const fileInput = document.getElementById('mediaFileInput');
    const fileNameBadge = document.getElementById('selectedFileName');
    if (fileInput && fileNameBadge) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                fileNameBadge.textContent = this.files[0].name;
                fileNameBadge.classList.remove('d-none');
            }
        });
    }

    // Copy URL buttons
    document.querySelectorAll('.btn-copy-url').forEach(btn => {
        btn.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            if (!url) return;
            navigator.clipboard.writeText(url).then(() => {
                const icon = this.querySelector('i');
                const oldClass = icon.className;
                icon.className = 'bi bi-check text-success';
                setTimeout(() => icon.className = oldClass, 1500);
            });
        });
    });

    // Edit modal
    const editModalEl = document.getElementById('editMediaModal');
    let editModal = null;
    if (editModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        editModal = new bootstrap.Modal(editModalEl);
    }

    document.querySelectorAll('.btn-edit-media').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const alt = this.getAttribute('data-alt');
            const url = this.getAttribute('data-url');

            document.getElementById('editMediaForm').action = `/admin/media/${id}`;
            document.getElementById('editMediaName').value = name || '';
            document.getElementById('editMediaAlt').value = alt || '';
            document.getElementById('editMediaUrl').value = url || '';

            if (editModal) editModal.show();
        });
    });
});
</script>
@endpush
