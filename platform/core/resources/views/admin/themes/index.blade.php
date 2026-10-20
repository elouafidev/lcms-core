@extends('core::admin.layouts.app')

@section('title', 'Administration - Gestion des Thèmes')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">Gestion des Thèmes CMS</h1>
            <p class="text-muted small mb-0">
                Moteur de thèmes découplé, hiérarchie de vues et personnalisation du front
            </p>
        </div>
        <div>
            @if($activeTheme)
                <span class="badge badge-active text-bg-success fs-6 px-3 py-2 shadow-sm">
                    <i class="bi bi-check2-circle me-1"></i> Thème actif : {{ $activeTheme->getName() }}
                </span>
            @else
                <span class="badge badge-available text-bg-secondary fs-6 px-3 py-2 shadow-sm">
                    <i class="bi bi-info-circle me-1"></i> Aucun thème actif (Rendu par défaut)
                </span>
            @endif
        </div>
    </div>

    <!-- Card Upload ZIP Theme -->
    <div class="card card-outline card-primary shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-lg-6">
                    <h5 class="fw-bold mb-1 text-primary">
                        <i class="bi bi-file-earmark-zip me-1"></i> Importer un thème (.zip)
                    </h5>
                    <p class="text-muted small mb-0">
                        Sélectionnez une archive ZIP contenant un thème LCMS conforme avec son fichier <code>theme.json</code>.
                    </p>
                </div>
                <div class="col-lg-6">
                    <form method="POST" action="{{ route('admin.themes.upload') }}" enctype="multipart/form-data" class="d-flex align-items-center gap-2 flex-wrap justify-content-lg-end">
                        @csrf
                        <input type="file" name="theme_archive" accept=".zip,application/zip" required class="form-control form-control-sm" style="max-width: 250px;">
                        <div class="form-check me-2">
                            <input class="form-check-input" type="checkbox" name="overwrite" value="1" id="overwriteTheme">
                            <label class="form-check-label small text-muted" for="overwriteTheme">
                                Écraser si existant
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <i class="bi bi-upload"></i> Installer le ZIP
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Themes Table Card -->
    <div class="card card-outline card-secondary shadow-sm">
        <div class="card-header bg-white py-3">
            <h3 class="card-title fw-bold fs-6 mb-0 text-secondary">
                <i class="bi bi-palette me-1"></i> Thèmes disponibles sur le système
            </h3>
        </div>
        <div class="card-body p-0">
            @if(empty($themes))
                <div class="text-center text-muted py-5">
                    <i class="bi bi-palette2 fs-1 d-block mb-2 text-muted"></i>
                    <h4>Aucun thème détecté</h4>
                    <p class="small">Déposez un dossier de thème conforme dans <code>platform/themes/</code> avec son fichier <code>theme.json</code> ou importez une archive ZIP ci-dessus.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7 text-muted">
                            <tr>
                                <th style="width: 40%;">Thème</th>
                                <th style="width: 15%;">Version</th>
                                <th style="width: 15%;">Auteur</th>
                                <th style="width: 15%;">Statut</th>
                                <th style="width: 15%;" class="text-end pe-3">Actions</th>
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
                                        <div class="fw-bold">{{ $theme->getName() }}</div>
                                        <div class="font-monospace small text-muted">{{ $themeId }}</div>
                                        @if($manifest->getDescription())
                                            <div class="small text-muted mt-1">{{ $manifest->getDescription() }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $manifest->getVersion() }}</span>
                                    </td>
                                    <td>
                                        <span class="small">{{ $author }}</span>
                                    </td>
                                    <td>
                                        @if($isActive)
                                            <span class="badge badge-active text-bg-success px-2 py-1">Actif</span>
                                        @else
                                            <span class="badge badge-available text-bg-secondary px-2 py-1">Disponible</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        @if($isActive)
                                            <form method="POST" action="{{ route('admin.themes.deactivate') }}" class="actions-form">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary">
                                                    <i class="bi bi-stop-circle me-1"></i> Désactiver
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.themes.activate', $themeId) }}" class="actions-form">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-check-circle me-1"></i> Activer
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.themes.delete', $themeId) }}" class="actions-form" onsubmit="return confirm('Confirmer la suppression définitive des fichiers du thème \'{{ $theme->getName() }}\' ?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash me-1"></i> Supprimer
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
