@extends('core::admin.layouts.app')

@section('title', 'Configuration SEO — Administration')

@section('content')
    <header class="admin-header">
        <div>
            <h1 class="admin-title">Configuration SEO</h1>
            <p style="margin: 0.25rem 0 0 0; color: var(--muted-color); font-size: 0.9rem;">
                Paramètres par défaut des métadonnées, balises OpenGraph et robots
            </p>
        </div>
    </header>

    <div class="card" style="max-width: 650px;">
        <h2 style="font-size: 1.25rem; margin-top: 0; margin-bottom: 1.25rem;">Paramètres par défaut</h2>
        <form action="/admin/seo" method="POST">
            @csrf
            <div style="margin-bottom: 1.25rem;">
                <label for="site_name" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Nom du site</label>
                <input type="text" id="site_name" name="site_name" value="{{ $settings['site_name'] ?? '' }}" placeholder="ex: Mon Super CMS" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label for="default_title" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Titre par défaut (meta title / og:title)</label>
                <input type="text" id="default_title" name="default_title" value="{{ $settings['default_title'] ?? '' }}" placeholder="ex: Accueil - Mon Super CMS" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label for="default_description" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Méta-description par défaut</label>
                <textarea id="default_description" name="default_description" rows="3" placeholder="Description générale du site web" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-sizing: border-box;">{{ $settings['default_description'] ?? '' }}</textarea>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label for="canonical_base" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">URL de base canonique (optionnel)</label>
                <input type="url" id="canonical_base" name="canonical_base" value="{{ $settings['canonical_base'] ?? '' }}" placeholder="https://example.com" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-sizing: border-box;">
            </div>
            <button type="submit" class="btn btn-primary">Enregistrer la configuration SEO</button>
        </form>
    </div>
@endsection
