@extends('core::admin.layouts.app')

@section('title', 'Configuration SEO — Administration')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">Configuration SEO</h1>
            <p class="text-muted small mb-0">
                Paramètres par défaut des métadonnées, balises OpenGraph et robots
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-xl-7">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-primary">
                        <i class="bi bi-search me-1"></i> Paramètres par défaut
                    </h5>
                </div>
                <div class="card-body">
                    <form action="/admin/seo" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="site_name" class="form-label fw-semibold small">Nom du site</label>
                            <input type="text" id="site_name" name="site_name" value="{{ $settings['site_name'] ?? '' }}" placeholder="ex: Mon Super CMS" class="form-control">
                            <div class="form-text small text-muted">Utilisé comme suffixe de titre et dans les balises og:site_name.</div>
                        </div>
                        <div class="mb-3">
                            <label for="default_title" class="form-label fw-semibold small">Titre par défaut (meta title / og:title)</label>
                            <input type="text" id="default_title" name="default_title" value="{{ $settings['default_title'] ?? '' }}" placeholder="ex: Accueil - Mon Super CMS" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="default_description" class="form-label fw-semibold small">Méta-description par défaut</label>
                            <textarea id="default_description" name="default_description" rows="3" placeholder="Description générale du site web" class="form-control">{{ $settings['default_description'] ?? '' }}</textarea>
                        </div>
                        <div class="mb-4">
                            <label for="canonical_base" class="form-label fw-semibold small">URL de base canonique (optionnel)</label>
                            <input type="url" id="canonical_base" name="canonical_base" value="{{ $settings['canonical_base'] ?? '' }}" placeholder="https://example.com" class="form-control">
                            <div class="form-text small text-muted">Préfixe utilisé pour générer les balises canoniques absolues.</div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <i class="bi bi-save"></i> Enregistrer la configuration SEO
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
