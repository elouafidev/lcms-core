@extends('core::admin.layouts.app')

@section('title', 'Administration - Gestion des Thèmes')

@section('content')
    <header class="admin-header">
        <div>
            <h1 class="admin-title">Gestion des Thèmes CMS</h1>
            <p style="margin: 0.25rem 0 0 0; color: var(--muted-color); font-size: 0.9rem;">
                Moteur de thèmes découplé, hiérarchie de vues et personnalisation du front
            </p>
        </div>
        @if($activeTheme)
            <span class="badge badge-active" style="font-size: 0.85rem; padding: 0.4rem 0.85rem;">
                Thème actif : {{ $activeTheme->getName() }}
            </span>
        @else
            <span class="badge badge-available" style="font-size: 0.85rem; padding: 0.4rem 0.85rem;">
                Aucun thème actif (Rendu par défaut)
            </span>
        @endif
    </header>

    <div class="card" style="padding: 0;">
        @if(empty($themes))
            <div style="padding: 3rem; text-align: center; color: var(--muted-color);">
                <h3>Aucun thème détecté</h3>
                <p>Déposez un dossier de thème conforme dans <code>platform/themes/</code> avec son fichier <code>theme.json</code> pour qu'il apparaisse ici.</p>
            </div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Thème</th>
                        <th>Version</th>
                        <th>Auteur</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($themes as $theme)
                        @php
                            $manifest = $theme->getManifest();
                            $themeId = $theme->getId();
                            $isActive = ($themeId === $activeThemeId);
                            $author = $manifest->getAuthor()['name'] ?? 'Inconnu';
                        @endphp
                        <tr>
                            <td>
                                <div style="font-weight: 600; font-size: 1rem;">{{ $theme->getName() }}</div>
                                <div style="font-family: monospace; font-size: 0.85rem; color: var(--muted-color);">{{ $themeId }}</div>
                                @if($manifest->getDescription())
                                    <div style="font-size: 0.85rem; color: #475569; margin-top: 0.25rem;">{{ $manifest->getDescription() }}</div>
                                @endif
                            </td>
                            <td>{{ $manifest->getVersion() }}</td>
                            <td>{{ $author }}</td>
                            <td>
                                @if($isActive)
                                    <span class="badge badge-active">Actif</span>
                                @else
                                    <span class="badge badge-available">Disponible</span>
                                @endif
                            </td>
                            <td>
                                @if($isActive)
                                    <form method="POST" action="{{ route('admin.themes.deactivate') }}" class="actions-form">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary">Désactiver</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.themes.activate', $themeId) }}" class="actions-form">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">Activer</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
