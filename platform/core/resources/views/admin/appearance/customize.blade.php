@extends('core::admin.layouts.app')

@section('title', __('Administration') . ' — ' . __('Personnaliser apparence'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Personnaliser l\'apparence publique') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Injectez du CSS, du JavaScript et des balises d\'en-tête (Head) sur les pages publiques sans modifier les fichiers sources des thèmes.') }}
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($activeTheme)
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-7 shadow-sm">
                    <i class="bi bi-palette me-1"></i> {{ __('Thème actif :') }} <strong>{{ $activeTheme->getName() }}</strong>
                </span>
            @endif
            <a href="{{ url('/') }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-box-arrow-up-right"></i> {{ __('Voir le site') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.appearance.customize.update') }}">
        @csrf

        <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs card-header-tabs mx-3 mt-2 border-bottom-0" id="customizeTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold text-dark py-3 px-4 border-0 border-bottom border-primary border-3" id="css-tab" data-bs-toggle="tab" data-bs-target="#css-content" type="button" role="tab" aria-controls="css-content" aria-selected="true">
                            <i class="bi bi-filetype-css text-primary me-2"></i> {{ __('CSS Personnalisé') }}
                            @if(trim($customCss) !== '')
                                <span class="badge bg-primary-subtle text-primary ms-1 fs-8">{{ __('Actif') }}</span>
                            @endif
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-dark py-3 px-4 border-0" id="js-tab" data-bs-toggle="tab" data-bs-target="#js-content" type="button" role="tab" aria-controls="js-content" aria-selected="false">
                            <i class="bi bi-filetype-js text-warning me-2"></i> {{ __('JavaScript Personnalisé') }}
                            @if(trim($customJs) !== '')
                                <span class="badge bg-warning-subtle text-warning ms-1 fs-8">{{ __('Actif') }}</span>
                            @endif
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-dark py-3 px-4 border-0" id="head-tab" data-bs-toggle="tab" data-bs-target="#head-content" type="button" role="tab" aria-controls="head-content" aria-selected="false">
                            <i class="bi bi-code-square text-info me-2"></i> {{ __('Balises <head> & Métadonnées') }}
                            @if(trim($customHeadTags) !== '')
                                <span class="badge bg-info-subtle text-info ms-1 fs-8">{{ __('Actif') }}</span>
                            @endif
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="customizeTabsContent">
                    <!-- Tab 1: Custom CSS -->
                    <div class="tab-pane fade show active" id="css-content" role="tabpanel" aria-labelledby="css-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold mb-1 text-dark">{{ __('Feuille de style CSS publique') }}</h5>
                                <p class="text-muted small mb-0">
                                    {{ __('Injecté automatiquement dans toutes les pages publiques au sein d\'une balise <style id="lcms-custom-css"> juste avant la fermeture de </head>.') }}
                                </p>
                            </div>
                        </div>

                        <div class="position-relative">
                            <textarea
                                name="custom_css"
                                class="form-control font-monospace text-light p-3 border-0 @error('custom_css') is-invalid @enderror"
                                style="background-color: #0d1117; color: #c9d1d9; border-radius: 0.75rem; min-height: 340px; font-size: 0.875rem; tab-size: 4;"
                                spellcheck="false"
                                placeholder="/* {{ __('Votre code CSS personnalisé ici...') }} */&#10;:root {&#10;    --primary-custom: #3b82f6;&#10;}&#10;&#10;.custom-highlight {&#10;    color: var(--primary-custom);&#10;}"
                            >{{ old('custom_css', $customCss) }}</textarea>
                            @error('custom_css')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-light border mt-3 small text-muted d-flex align-items-center gap-2 mb-0">
                            <i class="bi bi-lightbulb text-warning fs-5"></i>
                            <div>
                                <strong>{{ __('Astuce :') }}</strong> {{ __('Vous pouvez surcharger les variables de couleur, les typographies et les marges sans altérer les fichiers compilés du thème.') }}
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Custom JavaScript -->
                    <div class="tab-pane fade" id="js-content" role="tabpanel" aria-labelledby="js-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold mb-1 text-dark">{{ __('Code JavaScript public') }}</h5>
                                <p class="text-muted small mb-0">
                                    {{ __('Injecté au bas de toutes les pages publiques dans une balise <script id="lcms-custom-js"> avant la fermeture de </body>.') }}
                                </p>
                            </div>
                        </div>

                        <div class="position-relative">
                            <textarea
                                name="custom_js"
                                class="form-control font-monospace text-light p-3 border-0 @error('custom_js') is-invalid @enderror"
                                style="background-color: #0d1117; color: #c9d1d9; border-radius: 0.75rem; min-height: 340px; font-size: 0.875rem; tab-size: 4;"
                                spellcheck="false"
                                placeholder="// {{ __('Votre code JavaScript personnalisé ici...') }}&#10;document.addEventListener('DOMContentLoaded', function() {&#10;    // console.log('LCMS Core Theme Ready');&#10;});"
                            >{{ old('custom_js', $customJs) }}</textarea>
                            @error('custom_js')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-warning border border-warning-subtle mt-3 small text-dark d-flex align-items-center gap-2 mb-0">
                            <i class="bi bi-shield-exclamation text-warning fs-5"></i>
                            <div>
                                <strong>{{ __('Avertissement de sécurité :') }}</strong> {{ __('N\'insérez que des scripts provenant de sources fiables afin d\'éviter d\'exposer vos visiteurs à des failles de sécurité.') }}
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Custom Head Tags & Meta -->
                    <div class="tab-pane fade" id="head-content" role="tabpanel" aria-labelledby="head-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold mb-1 text-dark">{{ __('Balises <head> & Métadonnées brutes') }}</h5>
                                <p class="text-muted small mb-0">
                                    {{ __('Injecté directement dans le conteneur <head> via le hook cms.head. Idéal pour Google Analytics, polices Web, favicons ou balises de vérification.') }}
                                </p>
                            </div>
                        </div>

                        <div class="position-relative">
                            <textarea
                                name="custom_head_tags"
                                class="form-control font-monospace text-light p-3 border-0 @error('custom_head_tags') is-invalid @enderror"
                                style="background-color: #0d1117; color: #c9d1d9; border-radius: 0.75rem; min-height: 340px; font-size: 0.875rem; tab-size: 4;"
                                spellcheck="false"
                                placeholder="<!-- {{ __('Exemple : Balise Meta de vérification ou Script de suivi') }} -->&#10;<meta name=&quot;google-site-verification&quot; content=&quot;YOUR_CODE_HERE&quot;>&#10;<link rel=&quot;preconnect&quot; href=&quot;https://fonts.googleapis.com&quot;>"
                            >{{ old('custom_head_tags', $customHeadTags) }}</textarea>
                            @error('custom_head_tags')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-light border mt-3 small text-muted d-flex align-items-center gap-2 mb-0">
                            <i class="bi bi-info-circle text-primary fs-5"></i>
                            <div>
                                <strong>{{ __('Compatibilité :') }}</strong> {{ __('Ces balises sont combinées de façon ordonnée avec les méta-tags SEO générés par les extensions actives.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white border-top p-4 d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    <i class="bi bi-clock-history me-1"></i> {{ __('Les modifications s\'appliquent immédiatement sur la partie publique.') }}
                </span>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.themes.index') }}" class="btn btn-light px-4">{{ __('Annuler') }}</a>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm d-inline-flex align-items-center gap-2">
                        <i class="bi bi-save"></i> {{ __('Enregistrer les personnalisations') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

