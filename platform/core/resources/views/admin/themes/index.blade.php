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

    <!-- Card Upload ZIP Theme -->
    <div class="card" style="margin-bottom: 1.5rem; background-color: #f8fafc;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="margin: 0 0 0.25rem 0; font-size: 1.1rem;">Importer un thème (.zip)</h3>
                <p style="margin: 0; color: var(--muted-color); font-size: 0.85rem;">
                    Sélectionnez une archive ZIP contenant un thème LCMS conforme avec son fichier <code>theme.json</code>.
                </p>
            </div>
            <form method="POST" action="{{ route('admin.themes.upload') }}" enctype="multipart/form-data" style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                @csrf
                <input type="file" name="theme_archive" accept=".zip,application/zip" required style="font-size: 0.85rem; border: 1px solid var(--border-color); border-radius: 0.375rem; padding: 0.35rem 0.5rem; background: #fff;">
                <label style="font-size: 0.8rem; color: var(--muted-color); display: flex; align-items: center; gap: 0.25rem; cursor: pointer;">
                    <input type="checkbox" name="overwrite" value="1"> Écraser si existant
                </label>
                <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.4rem;">
                    Installer le ZIP
                </button>
            </form>
        </div>
    </div>

    <div class="card" style="padding: 0;">
        @if(empty($themes))
            <div style="padding: 3rem; text-align: center; color: var(--muted-color);">
                <h3>Aucun thème détecté</h3>
                <p>Déposez un dossier de thème conforme dans <code>platform/themes/</code> avec son fichier <code>theme.json</code> ou importez une archive ZIP ci-dessus.</p>
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
                                <div style="font-family: monospace; font-size: 0.85rem; color: var(--muted-color); font-weight: normal;">{{ $themeId }}</div>
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
                                    <form method="POST" action="{{ route('admin.themes.delete', $themeId) }}" class="actions-form" onsubmit="return confirm('Confirmer la suppression définitive des fichiers du thème \'{{ $theme->getName() }}\' ?');">
                                        @csrf
                                        <button type="submit" class="btn btn-danger">Supprimer</button>
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
