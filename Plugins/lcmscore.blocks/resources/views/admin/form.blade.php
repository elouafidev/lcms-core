@extends('core::admin.layouts.app')

@section('title', ($isEdit ? __('Modifier le bloc : ') . $block->name : __('Créer un bloc de contenu')) . ' — Administration')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="/admin/blocks" class="btn btn-sm btn-outline-secondary rounded-circle" title="{{ __('Retour à la liste') }}">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0">
                    {{ $isEdit ? __('Modifier le bloc') : __('Créer un bloc de contenu') }}
                </h1>
            </div>
            <p class="text-muted small mb-0 mt-1">
                {{ __('Configurez les paramètres et le contenu de votre section réutilisable') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            @if($isEdit)
                <a href="/admin/blocks/{{ $block->id }}/preview" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-eye me-1"></i> {{ __('Aperçu direct') }}
                </a>
            @endif
            <button type="submit" form="blockForm" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                <i class="bi bi-save me-1"></i> {{ $isEdit ? __('Enregistrer les modifications') : __('Créer le bloc') }}
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

    <form id="blockForm" action="{{ $isEdit ? '/admin/blocks/'.$block->id : '/admin/blocks' }}" method="POST">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="row g-4">
            <!-- Left Main Column (Configuration) -->
            <div class="col-lg-8">
                <!-- Base Info Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="blockName" class="form-label fw-bold text-dark small text-uppercase mb-1">
                                    {{ __('Nom du bloc') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="blockName" name="name" value="{{ old('name', $block->name) }}" required
                                       class="form-control fw-semibold" placeholder="{{ __('Ex: Bannière Héroïque Accueil') }}">
                            </div>

                            <div class="col-md-5">
                                <label for="blockType" class="form-label fw-bold text-dark small text-uppercase mb-1">
                                    {{ __('Type de bloc') }} <span class="text-danger">*</span>
                                </label>
                                <select id="blockType" name="type" class="form-select fw-semibold" onchange="switchBlockType(this.value)">
                                    @foreach($types as $key => $label)
                                        <option value="{{ $key }}" {{ old('type', $block->type) === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="blockSlug" class="form-label fw-semibold text-secondary small mb-1">
                                    {{ __('Identifiant unique (Slug)') }}
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted font-monospace">&#64;renderBlock('</span>
                                    <input type="text" id="blockSlug" name="slug" value="{{ old('slug', $block->slug) }}"
                                           class="form-control font-monospace" placeholder="{{ __('laissez vide pour générer automatiquement') }}">
                                    <span class="input-group-text bg-light text-muted font-monospace">')</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Type-Specific Content Editor -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-sliders text-primary me-1"></i> {{ __('Contenu & Paramètres du Bloc') }}
                        </h6>
                        <span class="badge bg-light text-dark font-monospace" id="currentTypeBadge">{{ $block->type }}</span>
                    </div>
                    <div class="card-body p-4">
                        <!-- HERO Specific Fields -->
                        <div id="section-hero" class="block-section d-none">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary mb-1">{{ __('Badge / Tagline supérieur') }}</label>
                                <input type="text" name="hero_badge" value="{{ old('hero_badge', $block->content['badge'] ?? '') }}" class="form-control" placeholder="{{ __('Ex: Nouveau en 2026') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-dark mb-1">{{ __('Titre principal') }}</label>
                                <input type="text" name="hero_title" value="{{ old('hero_title', $block->content['title'] ?? '') }}" class="form-control fw-bold" placeholder="{{ __('Ex: Le CMS Modulaire & Moderne') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary mb-1">{{ __('Sous-titre / Description') }}</label>
                                <textarea name="hero_subtitle" rows="2" class="form-control" placeholder="{{ __('Courte présentation persuasive...') }}">{{ old('hero_subtitle', $block->content['subtitle'] ?? '') }}</textarea>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-secondary mb-1">{{ __('Bouton principal — Texte') }}</label>
                                    <input type="text" name="hero_btn_primary_text" value="{{ old('hero_btn_primary_text', $block->content['primary_btn_text'] ?? '') }}" class="form-control form-control-sm" placeholder="{{ __('Ex: Démarrer gratuitement') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-secondary mb-1">{{ __('Bouton principal — Lien URL') }}</label>
                                    <input type="text" name="hero_btn_primary_url" value="{{ old('hero_btn_primary_url', $block->content['primary_btn_url'] ?? '') }}" class="form-control form-control-sm" placeholder="{{ __('Ex: /contact ou https://...') }}">
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-secondary mb-1">{{ __('Bouton secondaire — Texte') }}</label>
                                    <input type="text" name="hero_btn_secondary_text" value="{{ old('hero_btn_secondary_text', $block->content['secondary_btn_text'] ?? '') }}" class="form-control form-control-sm" placeholder="{{ __('Ex: En savoir plus') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-secondary mb-1">{{ __('Bouton secondaire — Lien URL') }}</label>
                                    <input type="text" name="hero_btn_secondary_url" value="{{ old('hero_btn_secondary_url', $block->content['secondary_btn_url'] ?? '') }}" class="form-control form-control-sm" placeholder="{{ __('Ex: /a-propos') }}">
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold small text-secondary mb-1">{{ __('Image d\'arrière-plan (URL)') }}</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="heroBgInput" name="hero_bg_image" value="{{ old('hero_bg_image', $block->content['background_image'] ?? '') }}" class="form-control" placeholder="https://...">
                                        @if(view()->exists('media-library::components.picker-modal'))
                                            <button type="button" class="btn btn-outline-primary" onclick="openMediaForHero()" title="{{ __('Choisir dans la médiathèque') }}">
                                                <i class="bi bi-folder2-open"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small text-secondary mb-1">{{ __('Style / Thème') }}</label>
                                    <select name="hero_style" class="form-select form-select-sm">
                                        <option value="dark" {{ (old('hero_style', $block->content['style'] ?? 'dark')) === 'dark' ? 'selected' : '' }}>Sombre (Dark)</option>
                                        <option value="light" {{ (old('hero_style', $block->content['style'] ?? '')) === 'light' ? 'selected' : '' }}>Clair (Light)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- CTA Specific Fields -->
                        <div id="section-cta" class="block-section d-none">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-dark mb-1">{{ __('Titre de l\'accroche (CTA)') }}</label>
                                <input type="text" name="cta_title" value="{{ old('cta_title', $block->content['title'] ?? '') }}" class="form-control fw-bold" placeholder="{{ __('Ex: Prêt à transformer votre présence en ligne ?') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary mb-1">{{ __('Description explicative') }}</label>
                                <textarea name="cta_description" rows="2" class="form-control" placeholder="{{ __('Texte d\'encouragement à l\'action...') }}">{{ old('cta_description', $block->content['description'] ?? '') }}</textarea>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-secondary mb-1">{{ __('Texte du bouton') }}</label>
                                    <input type="text" name="cta_button_text" value="{{ old('cta_button_text', $block->content['button_text'] ?? '') }}" class="form-control" placeholder="{{ __('Ex: Contactez nos experts') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-secondary mb-1">{{ __('Lien URL du bouton') }}</label>
                                    <input type="text" name="cta_button_url" value="{{ old('cta_button_url', $block->content['button_url'] ?? '') }}" class="form-control" placeholder="{{ __('Ex: /contact') }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary mb-1">{{ __('Couleur de fond') }}</label>
                                <input type="color" name="cta_bg_color" value="{{ old('cta_bg_color', $block->content['background_color'] ?? '#2563eb') }}" class="form-control form-control-color" style="max-width: 60px;">
                            </div>
                        </div>

                        <!-- Custom HTML Specific Fields -->
                        <div id="section-custom_html" class="block-section d-none">
                            <label class="form-label fw-semibold small text-dark mb-1">{{ __('Code HTML / CSS libre') }}</label>
                            <textarea name="custom_html" rows="10" class="form-control font-monospace" style="font-size: 0.85rem;"
                                      placeholder="<div><h1>Mon HTML personnalisé</h1></div>">{{ old('custom_html', $block->content['html'] ?? '') }}</textarea>
                        </div>

                        <!-- Structured JSON Editor (Always Available for Complex Types: features, faq, testimonials, pricing) -->
                        <div id="section-json" class="block-section">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="blockContentJson" class="form-label fw-bold small text-dark mb-0">
                                    {{ __('Données structurées (JSON)') }}
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace" style="font-size: 0.75rem;" onclick="loadTypePreset()">
                                    <i class="bi bi-magic me-1"></i> {{ __('Insérer modèle d\'exemple') }}
                                </button>
                            </div>
                            <textarea id="blockContentJson" name="content" rows="12" class="form-control font-monospace border" style="font-size: 0.85rem; line-height: 1.4;">{{ old('content', is_array($block->content) ? json_encode($block->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{}') }}</textarea>
                            <div class="form-text small text-muted">
                                {{ __('Ce payload JSON alimente directement la vue Blade du bloc. Vous pouvez modifier ses listes et valeurs librement.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar Column -->
            <div class="col-lg-4">
                <!-- Status & Order Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-gear text-primary me-1"></i> {{ __('Paramètres d\'affichage') }}
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="blockIsActive" name="is_active" value="1"
                                   {{ old('is_active', $block->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold small text-dark" for="blockIsActive">
                                {{ __('Bloc actif (visible)') }}
                            </label>
                        </div>

                        <div class="mb-3">
                            <label for="blockOrder" class="form-label fw-semibold small text-secondary mb-1">
                                {{ __('Ordre de tri') }}
                            </label>
                            <input type="number" id="blockOrder" name="order" value="{{ old('order', $block->order) }}" min="0" class="form-control form-control-sm">
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary fw-semibold py-2 rounded-pill shadow-sm">
                                <i class="bi bi-save me-1"></i> {{ $isEdit ? __('Enregistrer le bloc') : __('Créer le bloc') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Integration Helper Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-code-slash text-primary me-1"></i> {{ __('Comment l\'utiliser ?') }}
                        </h6>
                    </div>
                    <div class="card-body p-4 small text-secondary">
                        <p class="mb-2">
                            {{ __('Pour intégrer ce bloc dans n\'importe quelle page ou template Blade :') }}
                        </p>
                        <div class="bg-light p-2 rounded border font-monospace text-danger small mb-3">
                            &#64;renderBlock('{{ $block->slug ?: 'slug-du-bloc' }}')
                        </div>
                        <p class="mb-0 text-muted" style="font-size: 0.8rem;">
                            {{ __('Si le bloc est désactivé, aucun code HTML ne sera affiché, sans générer d\'erreur.') }}
                        </p>
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
        const PRESETS = {
            hero: {
                badge: "Nouveau en 2026",
                title: "Créez des Expériences Web Remarquables",
                subtitle: "LCMS Core est une plateforme modulaire ultra-performante basée sur Laravel.",
                primary_btn_text: "Découvrir la documentation",
                primary_btn_url: "/docs",
                secondary_btn_text: "Contactez-nous",
                secondary_btn_url: "/contact",
                background_image: "",
                style: "dark"
            },
            features: {
                title: "Pourquoi Choisir Notre Plateforme ?",
                subtitle: "Découvrez les atouts exclusifs de nos solutions.",
                items: [
                    { icon: "bi bi-lightning-charge", title: "Ultra Rapide", description: "Chargement instantané et optimisation continue des requêtes." },
                    { icon: "bi bi-shield-check", title: "Sécurité Maximale", description: "Authentification 2FA TOTP et contrôle d'accès granulaire RBAC." },
                    { icon: "bi bi-puzzle", title: "100% Extensible", description: "Écosystème de plugins isolés sans modification du Core." }
                ]
            },
            cta: {
                title: "Prêt à Propulser Votre Entreprise ?",
                description: "Rejoignez dès aujourd'hui les milliers d'utilisateurs satisfaits.",
                button_text: "Demander une démo",
                button_url: "/contact",
                background_color: "#2563eb"
            },
            faq: {
                title: "Questions Fréquentes",
                description: "Toutes les réponses à vos interrogations sur notre fonctionnement.",
                items: [
                    { question: "Puis-je créer mes propres plugins ?", answer: "Oui, LCMS Core offre une API de découverte et de contrats pour développer vos extensions en toute liberté." },
                    { question: "Mes données sont-elles sécurisées ?", answer: "Absolument, chaque plugin gère ses propres tables isolées et l'authentification est renforcée." }
                ]
            },
            testimonials: {
                title: "Ils Nous Font Confiance",
                subtitle: "Découvrez les avis de nos clients à travers le monde.",
                items: [
                    { author: "Sophie Martin", role: "Directrice Marketing", quote: "LCMS Core a divisé par deux notre temps de mise en ligne des pages !", avatar: "", rating: 5 },
                    { author: "Alexandre Dupont", role: "CTO TechGroup", quote: "L'architecture Lean Core et la robustesse des plugins sont tout simplement remarquables.", avatar: "", rating: 5 }
                ]
            },
            pricing: {
                title: "Nos Tarifs Clairs et Transparents",
                subtitle: "Choisissez la formule adaptée à la taille de votre équipe.",
                items: [
                    { name: "Starter", price: "29€", period: "/mois", description: "Idéal pour les indépendants.", features: "Jusqu'à 5 pages\nSupport par email\nMises à jour régulières", button_text: "Commencer", button_url: "/checkout/starter", is_featured: false },
                    { name: "Professionnel", badge: "Populaire", price: "79€", period: "/mois", description: "Parfait pour les PME en croissance.", features: "Pages illimitées\nPlugins premium\nSupport prioritaire 24/7\nSauvegardes automatiques", button_text: "Choisir Pro", button_url: "/checkout/pro", is_featured: true }
                ]
            },
            custom_html: {
                html: "<div class=\"p-4 text-center bg-white rounded shadow-sm\">\n  <h3>Section Personnalisée</h3>\n  <p>Insérez ici votre code HTML / CSS sur-mesure.</p>\n</div>"
            }
        };

        function switchBlockType(type) {
            document.getElementById('currentTypeBadge').textContent = type;
            const heroSection = document.getElementById('section-hero');
            const ctaSection = document.getElementById('section-cta');
            const customHtmlSection = document.getElementById('section-custom_html');

            heroSection.classList.add('d-none');
            ctaSection.classList.add('d-none');
            customHtmlSection.classList.add('d-none');

            if (type === 'hero') {
                heroSection.classList.remove('d-none');
            } else if (type === 'cta') {
                ctaSection.classList.remove('d-none');
            } else if (type === 'custom_html') {
                customHtmlSection.classList.remove('d-none');
            }
        }

        function loadTypePreset() {
            const type = document.getElementById('blockType').value;
            if (PRESETS[type]) {
                document.getElementById('blockContentJson').value = JSON.stringify(PRESETS[type], null, 2);
            }
        }

        function openMediaForHero() {
            if (window.LcmsMediaPicker) {
                window.LcmsMediaPicker.open(function(media) {
                    document.getElementById('heroBgInput').value = media.url;
                }, 'image');
            }
        }

        // Initialize display on load
        document.addEventListener('DOMContentLoaded', function() {
            switchBlockType(document.getElementById('blockType').value);
        });
    </script>
@endpush
