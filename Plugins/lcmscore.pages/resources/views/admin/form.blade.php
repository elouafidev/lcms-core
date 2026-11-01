@extends('core::admin.layouts.app')

@section('title', ($isEdit ? __('Modifier la page : ') . $page->title : __('Créer une nouvelle page')) . ' — Administration')

@push('styles')
    <style>
        .editor-toolbar { background: #f8fafc; border: 1px solid #e2e8f0; border-bottom: none; border-radius: 0.5rem 0.5rem 0 0; padding: 0.5rem 0.75rem; display: flex; flex-wrap: wrap; gap: 0.25rem; align-items: center; }
        .editor-toolbar .btn-group { margin-right: 0.5rem; }
        .editor-textarea { border-radius: 0 0 0.5rem 0.5rem !important; border-top: none; font-family: monospace; font-size: 0.9rem; line-height: 1.5; min-height: 320px; }
        .preview-box { min-height: 320px; border: 1px solid #e2e8f0; border-radius: 0 0 0.5rem 0.5rem; background: #ffffff; padding: 1.25rem; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="/admin/pages" class="btn btn-sm btn-outline-secondary rounded-circle" title="{{ __('Retour à la liste') }}">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0">
                    {{ $isEdit ? __('Modifier la page') : __('Créer une nouvelle page') }}
                </h1>
            </div>
            <p class="text-muted small mb-0 mt-1">
                {{ $isEdit ? __('Modifiez le contenu, la structure et le statut de publication') : __('Rédigez et publiez une nouvelle page de contenu') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/admin/pages" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                {{ __('Annuler') }}
            </a>
            <button type="submit" form="pageForm" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                <i class="bi bi-save me-1"></i> {{ $isEdit ? __('Enregistrer les modifications') : __('Créer la page') }}
            </button>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger shadow-sm mb-4">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ __('Erreurs de validation :') }}</h6>
            <ul class="mb-0 ps-3 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="pageForm" action="{{ $isEdit ? '/admin/pages/'.$page->id : '/admin/pages' }}" method="POST">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="row g-4">
            <!-- Left Main Column (Content) -->
            <div class="col-lg-8">
                <!-- Title & Slug Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="pageTitle" class="form-label fw-bold text-dark small text-uppercase mb-1">
                                {{ __('Titre de la page') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="pageTitle" name="title" value="{{ old('title', $page->title) }}" required
                                   class="form-control form-control-lg fw-semibold" placeholder="{{ __('Ex: À propos de notre entreprise') }}">
                        </div>

                        <div>
                            <label for="pageSlug" class="form-label fw-semibold text-secondary small mb-1">
                                {{ __('Identifiant URL (Slug)') }}
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted font-monospace">/page/</span>
                                <input type="text" id="pageSlug" name="slug" value="{{ old('slug', $page->slug) }}"
                                       class="form-control font-monospace" placeholder="{{ __('laissez vide pour générer automatiquement') }}">
                            </div>
                            <div class="form-text small text-muted">
                                {{ __('Ce slug détermine l\'adresse web de consultation de la page.') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rich Content Editor Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-file-earmark-richtext text-primary me-1"></i> {{ __('Contenu de la page') }}
                        </h6>
                        <ul class="nav nav-pills nav-fill" id="editorTabs" role="tablist" style="font-size: 0.8rem;">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active py-1 px-3" id="tab-write-btn" data-bs-toggle="tab" data-bs-target="#tab-write" type="button" role="tab">
                                    <i class="bi bi-pencil-fill me-1"></i> {{ __('Édition') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-1 px-3" id="tab-preview-btn" data-bs-toggle="tab" data-bs-target="#tab-preview" type="button" role="tab">
                                    <i class="bi bi-eye-fill me-1"></i> {{ __('Aperçu direct') }}
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-3">
                        <div class="tab-content" id="editorTabsContent">
                            <!-- Write Tab -->
                            <div class="tab-pane fade show active" id="tab-write" role="tabpanel">
                                <!-- Formatting Toolbar -->
                                <div class="editor-toolbar">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-light border" onclick="wrapText('**', '**')" title="{{ __('Gras') }}"><i class="bi bi-type-bold"></i></button>
                                        <button type="button" class="btn btn-light border" onclick="wrapText('*', '*')" title="{{ __('Italique') }}"><i class="bi bi-type-italic"></i></button>
                                        <button type="button" class="btn btn-light border" onclick="wrapText('<u>', '</u>')" title="{{ __('Souligné') }}"><i class="bi bi-type-underline"></i></button>
                                    </div>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-light border" onclick="prefixLine('## ')" title="{{ __('Titre H2') }}">H2</button>
                                        <button type="button" class="btn btn-light border" onclick="prefixLine('### ')" title="{{ __('Titre H3') }}">H3</button>
                                    </div>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-light border" onclick="prefixLine('- ')" title="{{ __('Liste à puces') }}"><i class="bi bi-list-ul"></i></button>
                                        <button type="button" class="btn btn-light border" onclick="prefixLine('1. ')" title="{{ __('Liste numérotée') }}"><i class="bi bi-list-ol"></i></button>
                                        <button type="button" class="btn btn-light border" onclick="prefixLine('> ')" title="{{ __('Citation') }}"><i class="bi bi-quote"></i></button>
                                    </div>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-light border" onclick="insertLink()" title="{{ __('Insérer un lien') }}"><i class="bi bi-link-45deg"></i></button>
                                        <button type="button" class="btn btn-light border" onclick="wrapText('`', '`')" title="{{ __('Code') }}"><i class="bi bi-code-slash"></i></button>
                                    </div>

                                    @if(view()->exists('media-library::components.picker-modal'))
                                        <div class="btn-group btn-group-sm ms-auto" role="group">
                                            <button type="button" class="btn btn-primary text-white" id="btnInsertMediaInEditor" title="{{ __('Insérer une image depuis la Médiathèque') }}">
                                                <i class="bi bi-images me-1"></i> {{ __('Insérer Image') }}
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <textarea id="pageContent" name="content" rows="14" class="form-control editor-textarea"
                                          placeholder="{{ __('Rédigez votre contenu ici en HTML ou Markdown...') }}">{{ old('content', $page->content) }}</textarea>
                            </div>

                            <!-- Preview Tab -->
                            <div class="tab-pane fade" id="tab-preview" role="tabpanel">
                                <div id="pageContentPreview" class="preview-box">
                                    <span class="text-muted fst-italic">{{ __('Aucun contenu à prévisualiser.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar Column (Settings & Metadata) -->
            <div class="col-lg-4">
                <!-- Publish Status Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-send-check text-primary me-1"></i> {{ __('Publication') }}
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <label for="pageStatus" class="form-label small fw-semibold text-secondary mb-1">
                                {{ __('Statut de la page') }}
                            </label>
                            <select id="pageStatus" name="status" class="form-select form-select-sm">
                                <option value="published" {{ old('status', $page->status) === 'published' ? 'selected' : '' }}>
                                    ✅ {{ __('Publié (accessible publiquement)') }}
                                </option>
                                <option value="draft" {{ old('status', $page->status) === 'draft' ? 'selected' : '' }}>
                                    📝 {{ __('Brouillon (invisible aux visiteurs)') }}
                                </option>
                                <option value="scheduled" {{ old('status', $page->status) === 'scheduled' ? 'selected' : '' }}>
                                    ⏱️ {{ __('Programmé (automatique par date)') }}
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="pagePublishedAt" class="form-label small fw-semibold text-secondary mb-1">
                                {{ __('Date de publication') }}
                            </label>
                            <input type="datetime-local" id="pagePublishedAt" name="published_at"
                                   value="{{ old('published_at', $page->published_at ? $page->published_at->format('Y-m-d\TH:i') : '') }}"
                                   class="form-control form-control-sm">
                            <div class="form-text small text-muted">
                                {{ __('Laissez vide pour publier immédiatement dès activation.') }}
                            </div>
                        </div>

                        <div class="d-grid pt-2 border-top">
                            <button type="submit" class="btn btn-primary rounded-pill fw-semibold">
                                <i class="bi bi-check-lg me-1"></i> {{ $isEdit ? __('Mettre à jour la page') : __('Enregistrer la page') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Page Attributes & Hierarchy Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-diagram-3 text-primary me-1"></i> {{ __('Structure & Modèle') }}
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <!-- Parent Page -->
                        <div class="mb-3">
                            <label for="pageParentId" class="form-label small fw-semibold text-secondary mb-1">
                                {{ __('Page Parente (Hiérarchie)') }}
                            </label>
                            <select id="pageParentId" name="parent_id" class="form-select form-select-sm">
                                <option value="">{{ __('(Aucune - Page Racine)') }}</option>
                                @foreach($parents as $parentItem)
                                    <option value="{{ $parentItem->id }}" {{ (string) old('parent_id', $page->parent_id) === (string) $parentItem->id ? 'selected' : '' }}>
                                        {{ $parentItem->title }} (/page/{{ $parentItem->slug }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small text-muted">
                                {{ __('Permet d\'organiser vos pages en sous-sections arborescentes.') }}
                            </div>
                        </div>

                        <!-- Template Selection -->
                        <div>
                            <label for="pageTemplate" class="form-label small fw-semibold text-secondary mb-1">
                                {{ __('Gabarit de mise en page (Template)') }}
                            </label>
                            <select id="pageTemplate" name="template" class="form-select form-select-sm">
                                @foreach($templates as $key => $label)
                                    <option value="{{ $key }}" {{ old('template', $page->template) === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small text-muted">
                                {{ __('Définit la structure Blade appliquée pour afficher cette page.') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Featured Image Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-image text-primary me-1"></i> {{ __('Image à la une') }}
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        @if(view()->exists('media-library::components.media-picker-field'))
                            @include('media-library::components.media-picker-field', [
                                'name' => 'featured_image',
                                'value' => old('featured_image', $page->featured_image),
                                'label' => null,
                                'type' => 'image',
                            ])
                        @else
                            <div>
                                <label for="featuredImageInput" class="form-label small fw-semibold text-secondary mb-1">
                                    {{ __('URL de l\'image') }}
                                </label>
                                <input type="text" id="featuredImageInput" name="featured_image"
                                       value="{{ old('featured_image', $page->featured_image) }}"
                                       class="form-control form-control-sm" placeholder="https://...">
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Include Universal Media Picker Modal if Media Library plugin is active --}}
    @if(view()->exists('media-library::components.picker-modal'))
        @include('media-library::components.picker-modal')
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto slug from title when creating
    const titleInput = document.getElementById('pageTitle');
    const slugInput = document.getElementById('pageSlug');
    let userEditedSlug = {{ $isEdit ? 'true' : 'false' }};

    if (slugInput) {
        slugInput.addEventListener('input', () => { userEditedSlug = true; });
    }

    if (titleInput && slugInput) {
        titleInput.addEventListener('input', function() {
            if (!userEditedSlug) {
                slugInput.value = this.value
                    .toLowerCase()
                    .trim()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });
    }

    // Live preview tab rendering
    const previewBtn = document.getElementById('tab-preview-btn');
    const contentTextarea = document.getElementById('pageContent');
    const previewContainer = document.getElementById('pageContentPreview');

    if (previewBtn && contentTextarea && previewContainer) {
        previewBtn.addEventListener('shown.bs.tab', function() {
            const raw = contentTextarea.value.trim();
            if (!raw) {
                previewContainer.innerHTML = '<span class="text-muted fst-italic">{{ __("Aucun contenu à prévisualiser.") }}</span>';
                return;
            }

            // Simple client-side Markdown rendering fallback
            let html = raw
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/&lt;img (.*?)&gt;/g, '<img $1>')
                .replace(/^### (.*$)/gim, '<h3 class="fw-bold mt-3">$1</h3>')
                .replace(/^## (.*$)/gim, '<h2 class="fw-bold mt-4">$1</h2>')
                .replace(/^# (.*$)/gim, '<h1 class="fw-bold mt-4">$1</h1>')
                .replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/gim, '<em>$1</em>')
                .replace(/`([^`]+)`/gim, '<code>$1</code>')
                .replace(/\n\n/gim, '<p></p>')
                .replace(/\n/gim, '<br>');

            previewContainer.innerHTML = html;
        });
    }

    // Media insert button in editor
    const btnInsertMedia = document.getElementById('btnInsertMediaInEditor');
    if (btnInsertMedia && window.LcmsMediaPicker) {
        btnInsertMedia.addEventListener('click', function() {
            window.LcmsMediaPicker.open({ type: 'image' }, function(media) {
                const imgMarkdown = `\n<img src="${media.url}" alt="${media.alt_text || media.name}" class="img-fluid rounded shadow-sm my-3">\n`;
                insertAtCursor(contentTextarea, imgMarkdown);
            });
        });
    }
});

function wrapText(prefix, suffix) {
    const textarea = document.getElementById('pageContent');
    if (!textarea) return;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.substring(start, end);
    const replacement = prefix + selected + suffix;
    textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
    textarea.focus();
    textarea.setSelectionRange(start + prefix.length, end + prefix.length);
}

function prefixLine(prefix) {
    const textarea = document.getElementById('pageContent');
    if (!textarea) return;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.substring(start, end);
    const replacement = prefix + (selected || 'Texte');
    textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
    textarea.focus();
}

function insertLink() {
    const url = prompt('{{ __("Entrez l'URL du lien :") }}', 'https://');
    if (url) {
        wrapText('[', `](${url})`);
    }
}

function insertAtCursor(textarea, text) {
    if (!textarea) return;
    const start = textarea.selectionStart || 0;
    const end = textarea.selectionEnd || 0;
    textarea.value = textarea.value.substring(0, start) + text + textarea.value.substring(end);
    textarea.focus();
    textarea.setSelectionRange(start + text.length, start + text.length);
}
</script>
@endpush
