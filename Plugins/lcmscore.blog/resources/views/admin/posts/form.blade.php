@extends('core::admin.layouts.app')

@section('title', ($isEdit ? __('Modifier l\'article : ') . $post->title : __('Rédiger un nouvel article')) . ' — Administration')

@push('styles')
    <style>
        .editor-toolbar { background: #f8fafc; border: 1px solid #e2e8f0; border-bottom: none; border-radius: 0.5rem 0.5rem 0 0; padding: 0.5rem 0.75rem; display: flex; flex-wrap: wrap; gap: 0.25rem; align-items: center; }
        .editor-toolbar .btn-group { margin-right: 0.5rem; }
        .editor-textarea { border-radius: 0 0 0.5rem 0.5rem !important; border-top: none; font-family: monospace; font-size: 0.9rem; line-height: 1.5; min-height: 380px; }
        .preview-box { min-height: 380px; border: 1px solid #e2e8f0; border-radius: 0 0 0.5rem 0.5rem; background: #ffffff; padding: 1.25rem; }
        .google-serp-preview { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 0.5rem; padding: 1rem; font-family: Arial, sans-serif; }
        .google-serp-title { color: #1a0dab; font-size: 1.1rem; line-height: 1.3; font-weight: 400; cursor: pointer; text-decoration: none; }
        .google-serp-url { color: #202124; font-size: 0.85rem; line-height: 1.4; }
        .google-serp-snippet { color: #4d5156; font-size: 0.85rem; line-height: 1.4; word-wrap: break-word; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="/admin/blog/posts" class="btn btn-sm btn-outline-secondary rounded-circle" title="{{ __('Retour à la liste') }}">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0">
                    {{ $isEdit ? __('Modifier l\'article') : __('Rédiger un nouvel article') }}
                </h1>
            </div>
            <p class="text-muted small mb-0 mt-1">
                {{ $isEdit ? __('Mettez à jour le contenu, les taxonomies ou le statut de publication') : __('Rédigez et publiez un article d\'actualité sur votre blog') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/admin/blog/posts" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                {{ __('Annuler') }}
            </a>
            <button type="submit" form="postForm" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                <i class="bi bi-save me-1"></i> {{ $isEdit ? __('Enregistrer les modifications') : __('Publier l\'article') }}
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

    <form id="postForm" action="{{ $isEdit ? '/admin/blog/posts/'.$post->id : '/admin/blog/posts' }}" method="POST">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="row g-4">
            <!-- Left Main Column (Content) -->
            <div class="col-lg-8">
                <!-- Title & Slug Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="postTitle" class="form-label fw-bold text-dark small text-uppercase mb-1">
                                {{ __('Titre de l\'article') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="postTitle" name="title" value="{{ old('title', $post->title) }}" required
                                   class="form-control form-control-lg fw-semibold" placeholder="{{ __('Ex: Les nouveautés majeures de LCMS Core en 2026') }}">
                        </div>

                        <div>
                            <label for="postSlug" class="form-label fw-semibold text-secondary small mb-1">
                                {{ __('Identifiant URL (Slug)') }}
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted font-monospace">/blog/</span>
                                <input type="text" id="postSlug" name="slug" value="{{ old('slug', $post->slug) }}"
                                       class="form-control font-monospace" placeholder="{{ __('laissez vide pour générer automatiquement') }}">
                            </div>
                            <div class="form-text small text-muted">
                                {{ __('Ce slug détermine l\'adresse web de consultation publique de l\'article.') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rich Content Editor Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-file-earmark-richtext text-primary me-1"></i> {{ __('Contenu de l\'article') }} <span class="text-danger">*</span>
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
                    <div class="card-body p-0">
                        <div class="tab-content" id="editorTabsContent">
                            <!-- Write Tab -->
                            <div class="tab-pane fade show active" id="tab-write" role="tabpanel">
                                <!-- Toolbar -->
                                <div class="editor-toolbar">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" onclick="insertFormat('**', '**')" title="{{ __('Gras') }}"><i class="bi bi-type-bold"></i></button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="insertFormat('*', '*')" title="{{ __('Italique') }}"><i class="bi bi-type-italic"></i></button>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" onclick="insertFormat('## ', '')" title="{{ __('Titre H2') }}">H2</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="insertFormat('### ', '')" title="{{ __('Titre H3') }}">H3</button>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" onclick="insertFormat('- ', '')" title="{{ __('Liste à puces') }}"><i class="bi bi-list-ul"></i></button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="insertFormat('1. ', '')" title="{{ __('Liste ordonnée') }}"><i class="bi bi-list-ol"></i></button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="insertFormat('> ', '')" title="{{ __('Citation') }}"><i class="bi bi-quote"></i></button>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" onclick="insertFormat('[', '](https://)')" title="{{ __('Insérer un lien') }}"><i class="bi bi-link-45deg"></i></button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="insertFormat('```\n', '\n```')" title="{{ __('Bloc de code') }}"><i class="bi bi-code-slash"></i></button>
                                    </div>
                                    @if(view()->exists('media-library::components.picker-modal'))
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openMediaForEditor()" title="{{ __('Insérer une image de la médiathèque') }}">
                                            <i class="bi bi-image"></i> {{ __('Médiathèque') }}
                                        </button>
                                    @endif
                                </div>
                                <textarea id="postContent" name="content" required
                                          class="form-control editor-textarea border-0"
                                          placeholder="{{ __('Rédigez votre article en HTML ou Markdown...') }}">{{ old('content', $post->content) }}</textarea>
                            </div>

                            <!-- Preview Tab -->
                            <div class="tab-pane fade" id="tab-preview" role="tabpanel">
                                <div id="previewContainer" class="preview-box">
                                    <em class="text-muted">{{ __('Chargement de l\'aperçu...') }}</em>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Excerpt Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-text-paragraph text-primary me-1"></i> {{ __('Extrait de l\'article (Résumé)') }}
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <textarea name="excerpt" id="postExcerpt" rows="3" class="form-control"
                                  placeholder="{{ __('Court paragraphe d\'accroche affiché sur la liste des articles et dans le flux RSS...') }}">{{ old('excerpt', $post->excerpt) }}</textarea>
                        <div class="form-text small text-muted">
                            {{ __('Si laissé vide, les 200 premiers caractères du contenu seront utilisés.') }}
                        </div>
                    </div>
                </div>

                <!-- SEO Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-search text-primary me-1"></i> {{ __('Optimisation pour les moteurs de recherche (SEO)') }}
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="metaTitle" class="form-label fw-semibold text-secondary small mb-1">
                                {{ __('Balise Meta Title') }}
                            </label>
                            <input type="text" id="metaTitle" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}"
                                   class="form-control" placeholder="{{ __('Titre affiché dans les résultats Google (recommandé: max 60 caractères)') }}">
                        </div>

                        <div class="mb-3">
                            <label for="metaDescription" class="form-label fw-semibold text-secondary small mb-1">
                                {{ __('Balise Meta Description') }}
                            </label>
                            <textarea id="metaDescription" name="meta_description" rows="2" class="form-control"
                                      placeholder="{{ __('Description affichée sous le titre dans les résultats Google (recommandé: 120-160 caractères)') }}">{{ old('meta_description', $post->meta_description) }}</textarea>
                        </div>

                        <!-- Live SERP Preview -->
                        <div class="mt-3">
                            <div class="fw-semibold small text-muted text-uppercase mb-2">{{ __('Aperçu Google SERP') }}</div>
                            <div class="google-serp-preview">
                                <div class="google-serp-url small font-monospace text-truncate">{{ url('/blog') }}/<span id="serpSlugPreview">{{ $post->slug ?: 'nouvel-article' }}</span></div>
                                <div class="google-serp-title" id="serpTitlePreview">{{ $post->meta_title ?: ($post->title ?: __('Titre de l\'article')) }}</div>
                                <div class="google-serp-snippet" id="serpDescPreview">{{ $post->meta_description ?: ($post->excerpt ?: __('Extrait de l\'article affiché dans les résultats de recherche Google...')) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar Column -->
            <div class="col-lg-4">
                <!-- Publication Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-send text-primary me-1"></i> {{ __('Publication') }}
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="postStatus" class="form-label fw-bold text-dark small mb-1">{{ __('Statut') }}</label>
                            <select id="postStatus" name="status" class="form-select fw-semibold" onchange="toggleScheduledDate(this.value)">
                                <option value="draft" {{ old('status', $post->status) === 'draft' ? 'selected' : '' }}>
                                    📝 {{ __('Brouillon (Non visible)') }}
                                </option>
                                <option value="published" {{ old('status', $post->status) === 'published' ? 'selected' : '' }}>
                                    🌐 {{ __('Publié (Accessible en ligne)') }}
                                </option>
                                <option value="scheduled" {{ old('status', $post->status) === 'scheduled' ? 'selected' : '' }}>
                                    ⏱️ {{ __('Planifié (Date ultérieure)') }}
                                </option>
                            </select>
                        </div>

                        <div id="scheduledDateContainer" class="mb-3 {{ old('status', $post->status) === 'scheduled' ? '' : 'd-none' }}">
                            <label for="publishedAt" class="form-label fw-semibold text-secondary small mb-1">
                                {{ __('Date & Heure de publication') }}
                            </label>
                            <input type="datetime-local" id="publishedAt" name="published_at"
                                   value="{{ old('published_at', $post->published_at ? $post->published_at->format('Y-m-d\TH:i') : '') }}"
                                   class="form-control form-control-sm">
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="isFeatured" name="is_featured" value="1"
                                   {{ old('is_featured', $post->is_featured) ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold text-dark" for="isFeatured">
                                <i class="bi bi-star-fill text-warning me-1"></i> {{ __('Mettre en avant cet article') }}
                            </label>
                        </div>

                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-primary fw-semibold py-2 rounded-pill shadow-sm">
                                <i class="bi bi-save me-1"></i> {{ $isEdit ? __('Mettre à jour l\'article') : __('Publier maintenant') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Featured Image Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-image text-primary me-1"></i> {{ __('Image à la une') }}
                        </h6>
                    </div>
                    <div class="card-body p-4 text-center">
                        <div class="mb-3">
                            <div id="featuredImagePreview" class="border rounded-3 p-2 bg-light d-flex align-items-center justify-content-center overflow-hidden" style="min-height: 140px; max-height: 200px;">
                                @if($post->featured_image)
                                    <img src="{{ $post->featured_image }}" alt="Image à la une" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;">
                                @else
                                    <div class="text-muted small">
                                        <i class="bi bi-card-image fs-1 opacity-50 d-block mb-1"></i>
                                        {{ __('Aucune image sélectionnée') }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="input-group input-group-sm mb-2">
                            <input type="text" id="featuredImageInput" name="featured_image"
                                   value="{{ old('featured_image', $post->featured_image) }}"
                                   class="form-control" placeholder="https://... ou URL locale"
                                   oninput="updateImagePreview(this.value)">
                            @if(view()->exists('media-library::components.picker-modal'))
                                <button type="button" class="btn btn-outline-primary" onclick="openMediaForFeaturedImage()" title="{{ __('Choisir dans la médiathèque') }}">
                                    <i class="bi bi-folder2-open"></i>
                                </button>
                            @endif
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0" onclick="clearFeaturedImage()">
                            <i class="bi bi-x-circle"></i> {{ __('Supprimer l\'image') }}
                        </button>
                    </div>
                </div>

                <!-- Category Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-folder text-primary me-1"></i> {{ __('Catégorie') }}
                        </h6>
                        <a href="/admin/blog/categories" target="_blank" class="small text-decoration-none">
                            <i class="bi bi-plus-circle"></i> {{ __('Gérer') }}
                        </a>
                    </div>
                    <div class="card-body p-4">
                        <select name="category_id" class="form-select">
                            <option value="">{{ __('— Sans catégorie —') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ (string)old('category_id', $post->category_id) === (string)$category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Author Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-person text-primary me-1"></i> {{ __('Auteur') }}
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <select name="author_id" class="form-select">
                            @foreach($authors as $author)
                                <option value="{{ $author->id }}" {{ (string)old('author_id', $post->author_id ?? auth()->id()) === (string)$author->id ? 'selected' : '' }}>
                                    {{ $author->name }} ({{ $author->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Tags Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-tags text-primary me-1"></i> {{ __('Étiquettes (Tags)') }}
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <input type="text" id="postTags" name="tags" value="{{ old('tags', $selectedTagNames) }}"
                               class="form-control" placeholder="{{ __('ex: laravel, cms, actualité') }}">
                        <div class="form-text small text-muted">
                            {{ __('Séparez les mots-clés par des virgules. De nouvelles étiquettes seront créées automatiquement.') }}
                        </div>
                        @if($tags->isNotEmpty())
                            <div class="mt-2 pt-2 border-top">
                                <span class="small text-muted d-block mb-1">{{ __('Étiquettes existantes :') }}</span>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($tags as $t)
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill font-monospace" style="font-size: 0.75rem;"
                                                onclick="appendTag('{{ $t->name }}')">
                                            + {{ $t->name }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Inclusion conditionnelle du modal de la médiathèque si disponible -->
    @if(view()->exists('media-library::components.picker-modal'))
        @include('media-library::components.picker-modal')
    @endif
@endsection

@push('scripts')
    <script>
        function toggleScheduledDate(status) {
            const container = document.getElementById('scheduledDateContainer');
            if (status === 'scheduled') {
                container.classList.remove('d-none');
            } else {
                container.classList.add('d-none');
            }
        }

        function insertFormat(before, after) {
            const textarea = document.getElementById('postContent');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selected = textarea.value.substring(start, end);
            const replacement = before + selected + after;
            textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + before.length, start + before.length + selected.length);
        }

        function updateImagePreview(url) {
            const preview = document.getElementById('featuredImagePreview');
            if (url && url.trim() !== '') {
                preview.innerHTML = `<img src="${url}" alt="Preview" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;">`;
            } else {
                preview.innerHTML = `<div class="text-muted small"><i class="bi bi-card-image fs-1 opacity-50 d-block mb-1"></i>{{ __('Aucune image sélectionnée') }}</div>`;
            }
        }

        function clearFeaturedImage() {
            document.getElementById('featuredImageInput').value = '';
            updateImagePreview('');
        }

        function appendTag(tagName) {
            const input = document.getElementById('postTags');
            const current = input.value.split(',').map(s => s.trim()).filter(Boolean);
            if (!current.includes(tagName)) {
                current.push(tagName);
                input.value = current.join(', ');
            }
        }

        function openMediaForFeaturedImage() {
            if (window.LcmsMediaPicker) {
                window.LcmsMediaPicker.open(function(media) {
                    document.getElementById('featuredImageInput').value = media.url;
                    updateImagePreview(media.url);
                }, 'image');
            }
        }

        function openMediaForEditor() {
            if (window.LcmsMediaPicker) {
                window.LcmsMediaPicker.open(function(media) {
                    const imgMarkdown = `\n![${media.alt_text || media.name}](${media.url})\n`;
                    insertFormat('', imgMarkdown);
                }, 'image');
            }
        }

        // Live preview tab listener
        document.getElementById('tab-preview-btn')?.addEventListener('shown.bs.tab', function () {
            const content = document.getElementById('postContent').value;
            const previewContainer = document.getElementById('previewContainer');
            if (!content.trim()) {
                previewContainer.innerHTML = '<em class="text-muted">{{ __('Le contenu est vide.') }}</em>';
                return;
            }
            previewContainer.innerHTML = content.replace(/\n/g, '<br>');
        });

        // Live SEO SERP listener
        const postTitle = document.getElementById('postTitle');
        const postSlug = document.getElementById('postSlug');
        const postExcerpt = document.getElementById('postExcerpt');
        const metaTitle = document.getElementById('metaTitle');
        const metaDescription = document.getElementById('metaDescription');

        function updateSerp() {
            const titleVal = metaTitle.value || postTitle.value || '{{ __('Titre de l\'article') }}';
            const slugVal = postSlug.value || 'nouvel-article';
            const descVal = metaDescription.value || postExcerpt.value || '{{ __('Extrait de l\'article affiché dans les résultats de recherche Google...') }}';

            document.getElementById('serpTitlePreview').textContent = titleVal;
            document.getElementById('serpSlugPreview').textContent = slugVal;
            document.getElementById('serpDescPreview').textContent = descVal;
        }

        postTitle?.addEventListener('input', updateSerp);
        postSlug?.addEventListener('input', updateSerp);
        postExcerpt?.addEventListener('input', updateSerp);
        metaTitle?.addEventListener('input', updateSerp);
        metaDescription?.addEventListener('input', updateSerp);
    </script>
@endpush
