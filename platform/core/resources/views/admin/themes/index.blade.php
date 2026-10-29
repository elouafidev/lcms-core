@extends('core::admin.layouts.app')

@section('title', __('Administration') . ' - ' . __('Themes Management'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('CMS Themes Management') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Decoupled theme engine, view hierarchy and front-end customization') }}
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($activeTheme)
                <span class="badge badge-active text-bg-success fs-6 px-3 py-2 shadow-sm">
                    <i class="bi bi-check2-circle me-1"></i> {{ __('Active Theme:') }} {{ $activeTheme->getName() }}
                </span>
            @else
                <span class="badge badge-available text-bg-secondary fs-6 px-3 py-2 shadow-sm">
                    <i class="bi bi-info-circle me-1"></i> {{ __('No active theme (Default rendering)') }}
                </span>
            @endif
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

    <!-- Card Upload ZIP Theme & Global Actions -->
    <div class="card card-outline card-primary shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-lg-6">
                    <h5 class="fw-bold mb-1 text-primary">
                        <i class="bi bi-file-earmark-zip me-1"></i> {{ __('Import a theme (.zip)') }}
                    </h5>
                    <p class="text-muted small mb-0">
                        {{ __('Select a compliant LCMS ZIP archive containing its theme.json file (max 50 MB).') }}
                    </p>
                </div>
                <div class="col-lg-6">
                    <form method="POST" action="{{ route('admin.themes.upload') }}" enctype="multipart/form-data" class="d-flex align-items-center gap-2 flex-wrap justify-content-lg-end">
                        @csrf
                        <input type="file" name="theme_archive" accept=".zip,application/zip,application/x-zip,application/x-zip-compressed" required class="form-control form-control-sm" style="max-width: 250px;">
                        <div class="form-check me-2">
                            <input class="form-check-input" type="checkbox" name="overwrite" value="1" id="overwriteTheme">
                            <label class="form-check-label small text-muted" for="overwriteTheme">
                                {{ __('Overwrite if existing') }}
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
                            <i class="bi bi-upload"></i> {{ __('Install ZIP') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Themes Table Card -->
    <div class="card card-outline card-secondary shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title fw-bold fs-6 mb-0 text-secondary">
                <i class="bi bi-palette me-1"></i> {{ __('Themes available on system') }}
            </h3>
            @if(! empty($themes))
                <form method="POST" action="{{ route('admin.themes.publish-all-assets') }}" class="d-inline" onsubmit="return confirm('{{ __('Publier et écraser les assets statiques pour l\'ensemble des thèmes découverts vers public/vendor/themes ?') }}');">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="bi bi-arrow-repeat"></i> {{ __('Publier tous les assets') }}
                    </button>
                </form>
            @endif
        </div>
        <div class="card-body p-0">
            @if(empty($themes))
                <div class="text-center text-muted py-5">
                    <i class="bi bi-palette2 fs-1 d-block mb-2 text-muted"></i>
                    <h4>{{ __('No theme detected') }}</h4>
                    <p class="small">{{ __('Deposit compliant themes in platform/themes/ with their theme.json manifest or import a ZIP archive above.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7 text-muted">
                            <tr>
                                <th style="width: 35%;">{{ __('Theme') }}</th>
                                <th style="width: 12%;">{{ __('Version') }}</th>
                                <th style="width: 15%;">{{ __('Author') }}</th>
                                <th style="width: 15%;">{{ __('Assets') }}</th>
                                <th style="width: 10%;">{{ __('Status') }}</th>
                                <th style="width: 13%;" class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($themes as $theme)
                                @php
                                    $manifest = $theme->getManifest();
                                    $themeId = $theme->getId();
                                    $isActive = ($themeId === $activeThemeId);
                                    $author = $manifest->getAuthor()['name'] ?? __('Unknown');
                                    $hasPublished = isset($assetManager) ? $assetManager->hasPublished($themeId) : false;
                                @endphp
                                <tr>
                                    <td>
                                        <a href="#" class="fw-bold text-decoration-none text-primary fs-6" data-bs-toggle="modal" data-bs-target="#themeModal-{{ $themeId }}" title="{{ __('Cliquer pour afficher les détails du thème') }}">
                                            <i class="bi bi-box-arrow-up-right me-1 fs-7"></i> {{ $theme->getName() }}
                                        </a>
                                        <div class="font-monospace small text-muted">{{ $themeId }}</div>
                                        @if($manifest->getDescription())
                                            <div class="small text-muted mt-1 text-truncate" style="max-width: 380px;">{{ $manifest->getDescription() }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $manifest->getVersion() }}</span>
                                    </td>
                                    <td>
                                        <span class="small">{{ $author }}</span>
                                    </td>
                                    <td>
                                        @if($hasPublished)
                                            <span class="badge bg-success-subtle text-success fs-8">
                                                <i class="bi bi-check-circle me-1"></i> {{ __('Publiés') }}
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning fs-8">
                                                <i class="bi bi-exclamation-triangle me-1"></i> {{ __('Non publiés') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($isActive)
                                            <span class="badge badge-active text-bg-success px-2 py-1">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge badge-available text-bg-secondary px-2 py-1">{{ __('Available') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex align-items-center justify-content-end gap-1 flex-wrap">
                                            <!-- Details Modal Button -->
                                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#themeModal-{{ $themeId }}" title="{{ __('Voir les informations détaillées') }}">
                                                <i class="bi bi-info-circle"></i>
                                            </button>

                                            <!-- Publish Assets Button -->
                                            <form method="POST" action="{{ route('admin.themes.publish-assets', $themeId) }}" class="actions-form d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="{{ __('Publier / Forcer la copie des assets vers public/vendor/themes') }}">
                                                    <i class="bi bi-cloud-arrow-up"></i>
                                                </button>
                                            </form>

                                            @if($isActive)
                                                <form method="POST" action="{{ route('admin.themes.deactivate') }}" class="actions-form d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-secondary" title="{{ __('Désactiver le thème') }}">
                                                        <i class="bi bi-stop-circle me-1"></i> {{ __('Deactivate') }}
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.themes.activate', $themeId) }}" class="actions-form d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary" title="{{ __('Activer le thème') }}">
                                                        <i class="bi bi-check-circle me-1"></i> {{ __('Activate') }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.themes.delete', $themeId) }}" class="actions-form d-inline" onsubmit="return confirm('{{ __('Are you sure you want to permanently delete this theme and all its files? This action is irreversible.', ['name' => $theme->getName()]) }}');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Supprimer définitivement le thème') }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Modals de Détails des Thèmes -->
    @foreach($themes as $theme)
        @php
            $manifest = $theme->getManifest();
            $themeId = $theme->getId();
            $isActive = ($themeId === $activeThemeId);
            $authorData = $manifest->getAuthor();
            $authorName = $authorData['name'] ?? __('Inconnu');
            $authorEmail = $authorData['email'] ?? null;
            $authorUrl = $authorData['url'] ?? null;
            $hasPublished = isset($assetManager) ? $assetManager->hasPublished($themeId) : false;
            $parent = $manifest->getParentTheme();
            $settingsManifest = $manifest->getSettings();
        @endphp
        <div class="modal fade" id="themeModal-{{ $themeId }}" tabindex="-1" aria-labelledby="themeModalLabel-{{ $themeId }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content shadow border-0" style="border-radius: 1rem;">
                    <div class="modal-header bg-light border-bottom py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 rounded bg-primary text-white">
                                <i class="bi bi-palette fs-5"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold mb-0 text-dark" id="themeModalLabel-{{ $themeId }}">
                                    {{ $theme->getName() }}
                                </h5>
                                <span class="font-monospace small text-muted">{{ $themeId }}</span>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-4">
                            <!-- Theme General Info -->
                            <div class="col-md-7">
                                <h6 class="text-uppercase fs-7 text-muted fw-bold mb-2">{{ __('Description') }}</h6>
                                <p class="text-dark small leading-relaxed">
                                    {{ $manifest->getDescription() ?: __('Aucune description fournie pour ce thème.') }}
                                </p>

                                <div class="border-top pt-3 mt-3">
                                    <h6 class="text-uppercase fs-7 text-muted fw-bold mb-2">{{ __('Auteur & Éditeur') }}</h6>
                                    <ul class="list-unstyled small mb-0 text-dark">
                                        <li class="mb-1"><i class="bi bi-person me-2 text-primary"></i> <strong>{{ $authorName }}</strong></li>
                                        @if($authorEmail)
                                            <li class="mb-1"><i class="bi bi-envelope me-2 text-primary"></i> <a href="mailto:{{ $authorEmail }}" class="text-decoration-none">{{ $authorEmail }}</a></li>
                                        @endif
                                        @if($authorUrl)
                                            <li><i class="bi bi-globe me-2 text-primary"></i> <a href="{{ $authorUrl }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none">{{ $authorUrl }}</a></li>
                                        @endif
                                    </ul>
                                </div>

                                @if($parent)
                                    <div class="border-top pt-3 mt-3">
                                        <h6 class="text-uppercase fs-7 text-muted fw-bold mb-1">{{ __('Thème parent (héritage)') }}</h6>
                                        <span class="badge bg-secondary-subtle text-secondary font-monospace">{{ $parent }}</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Technical Specs -->
                            <div class="col-md-5">
                                <div class="bg-light p-3 rounded border">
                                    <h6 class="text-uppercase fs-7 text-muted fw-bold mb-3">{{ __('Spécifications techniques') }}</h6>

                                    <div class="mb-2 pb-2 border-bottom d-flex justify-content-between align-items-center">
                                        <span class="small text-muted">{{ __('Version :') }}</span>
                                        <span class="badge bg-primary">{{ $manifest->getVersion() }}</span>
                                    </div>

                                    <div class="mb-2 pb-2 border-bottom d-flex justify-content-between align-items-center">
                                        <span class="small text-muted">{{ __('Statut :') }}</span>
                                        @if($isActive)
                                            <span class="badge bg-success">{{ __('Actif') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('Disponible') }}</span>
                                        @endif
                                    </div>

                                    <div class="mb-2 pb-2 border-bottom d-flex justify-content-between align-items-center">
                                        <span class="small text-muted">{{ __('Assets publics :') }}</span>
                                        @if($hasPublished)
                                            <span class="badge bg-success-subtle text-success fs-8">
                                                <i class="bi bi-check-circle me-1"></i> {{ __('Copiés dans public/') }}
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning fs-8">
                                                <i class="bi bi-exclamation-triangle me-1"></i> {{ __('Non copiés') }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="small">
                                        <span class="text-muted d-block mb-1">{{ __('Emplacement disque :') }}</span>
                                        <code class="d-block text-break p-2 rounded bg-white border fs-8">{{ $theme->getPath() }}</code>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Configurable Options from manifest -->
                        @if(! empty($settingsManifest))
                            <div class="border-top pt-3 mt-4">
                                <h6 class="text-uppercase fs-7 text-muted fw-bold mb-2">{{ __('Paramètres déclarés (theme.json)') }}</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered small mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('Clé') }}</th>
                                                <th>{{ __('Valeur par défaut') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($settingsManifest as $key => $val)
                                                <tr>
                                                    <td class="font-monospace text-primary">{{ $key }}</td>
                                                    <td><code>{{ is_array($val) ? json_encode($val) : (string) $val }}</code></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer bg-light border-top py-3 d-flex justify-content-between">
                        <div>
                            <!-- Direct Publish Assets button inside modal -->
                            <form method="POST" action="{{ route('admin.themes.publish-assets', $themeId) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-cloud-arrow-up"></i> {{ __('Publier les assets maintenant') }}
                                </button>
                            </form>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Fermer') }}</button>
                            @if($isActive)
                                <form method="POST" action="{{ route('admin.themes.deactivate') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="bi bi-stop-circle me-1"></i> {{ __('Désactiver') }}
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.themes.activate', $themeId) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-check-circle me-1"></i> {{ __('Activer ce thème') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.themes.delete', $themeId) }}" class="d-inline" onsubmit="return confirm('{{ __('Confirmez-vous la suppression irréversible de ce thème ?') }}');">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash me-1"></i> {{ __('Supprimer') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
