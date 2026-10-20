@extends('core::admin.layouts.app')

@section('title', 'Administration - Détails Plugin : ' . $manifest->getName())

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.plugins.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i> &larr; Retour à la liste des extensions
        </a>
    </div>

    @php
        $state = strtoupper($plugin->getState());
        $pluginId = $manifest->getId();
        $badgeClass = match($state) {
            'AVAILABLE' => 'badge-available',
            'INSTALLED' => 'badge-installed',
            'ACTIVE' => 'badge-active',
            'DISABLED' => 'badge-disabled',
            'ERROR' => 'badge-error',
            default => 'badge-available',
        };
        $bsBadgeClass = match($state) {
            'AVAILABLE' => 'text-bg-secondary',
            'INSTALLED' => 'text-bg-primary',
            'ACTIVE' => 'text-bg-success',
            'DISABLED' => 'text-bg-warning',
            'ERROR' => 'text-bg-danger',
            default => 'text-bg-secondary',
        };
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h3 fw-bold mb-0">{{ $manifest->getName() }}</h1>
                <span class="badge {{ $badgeClass }} {{ $bsBadgeClass }} px-2 py-1">{{ $state }}</span>
            </div>
            <p class="font-monospace text-muted small mb-0">{{ $pluginId }}</p>
        </div>
        <div>
            @if($isSafeMode)
                <span class="badge text-bg-danger fs-6 px-3 py-2 shadow-sm">
                    <i class="bi bi-shield-slash me-1"></i> MODE DE SECOURS ACTIF
                </span>
            @endif
        </div>
    </div>

    @if($isSafeMode)
        <div class="alert alert-warning shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Attention :</strong> Le mode de récupération d'urgence (Safe Mode) est activé. Cette extension tierce est actuellement neutralisée au démarrage.
        </div>
    @endif

    @if($state === 'ERROR' && $record?->error_message)
        <div class="alert alert-danger alert-error shadow-sm mb-4">
            <h6 class="fw-bold"><i class="bi bi-bug-fill me-1"></i> Erreur d'exécution isolée :</h6>
            <pre class="mb-0 font-monospace small bg-white p-2 rounded border border-danger-subtle">{{ $record->error_message }}</pre>
        </div>
    @endif

    @if(!empty($activeDependents))
        <div class="alert alert-warning shadow-sm mb-4">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <strong>Avertissement de dépendance :</strong> Cette extension est requise par {{ count($activeDependents) }} extension(s) actuellement active(s) :
            <strong>{{ implode(', ', array_map(fn ($p) => $p->getManifest()->getName() . ' (' . $p->getManifest()->getId() . ')', $activeDependents)) }}</strong>.
            Sa désactivation ou sa désinstallation sans forçage sera bloquée pour garantir la stabilité du système.
        </div>
    @endif

    <!-- Actions Toolbar Card -->
    <div class="card card-outline card-primary shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="fw-bold mb-1 text-primary">
                        <i class="bi bi-sliders me-1"></i> Actions sur le cycle de vie
                    </h5>
                    <p class="text-muted small mb-0">
                        Contrôle direct de l'état de l'extension avec vérification de dépendances
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if($state === 'AVAILABLE')
                        <form method="POST" action="{{ route('admin.plugins.enable', $pluginId) }}" class="actions-form">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-play-circle me-1"></i> Installer et Activer
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.plugins.delete', $pluginId) }}" class="actions-form" onsubmit="return confirm('Attention : Êtes-vous sûr de vouloir supprimer définitivement les fichiers de cette extension ? Cette action est irréversible.');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash me-1"></i> Supprimer définitivement
                            </button>
                        </form>
                    @elseif($state === 'INSTALLED' || $state === 'DISABLED')
                        <form method="POST" action="{{ route('admin.plugins.enable', $pluginId) }}" class="actions-form">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-play-circle me-1"></i> Activer l'extension
                            </button>
                        </form>
                    @elseif($state === 'ACTIVE')
                        <form method="POST" action="{{ route('admin.plugins.disable', $pluginId) }}" class="actions-form" onsubmit="return confirm('Confirmer la désactivation de {{ $manifest->getName() }} ?');">
                            @csrf
                            @if(!empty($activeDependents))
                                <input type="hidden" name="force" value="1">
                            @endif
                            <button type="submit" class="btn btn-sm btn-secondary">
                                <i class="bi bi-pause-circle me-1"></i> Désactiver l'extension
                                @if(!empty($activeDependents)) (Forcer) @endif
                            </button>
                        </form>
                    @elseif($state === 'ERROR')
                        <form method="POST" action="{{ route('admin.plugins.disable', $pluginId) }}" class="actions-form">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-secondary">
                                <i class="bi bi-shield-x me-1"></i> Mettre en quarantaine / Désactiver
                            </button>
                        </form>
                    @endif

                    @if($state !== 'AVAILABLE')
                        <form method="POST" action="{{ route('admin.plugins.uninstall', $pluginId) }}" class="actions-form d-flex align-items-center gap-2" onsubmit="return confirm('Attention : Êtes-vous sûr de vouloir désinstaller cette extension ?');">
                            @csrf
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" name="purge_data" value="1" id="purgeData">
                                <label class="form-check-label small text-muted" for="purgeData">
                                    Purger les données
                                </label>
                            </div>
                            @if(!empty($activeDependents))
                                <input type="hidden" name="force" value="1">
                            @endif
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="bi bi-x-circle me-1"></i> Désinstaller
                                @if(!empty($activeDependents)) (Forcer) @endif
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Metadata Grid -->
    <div class="row g-4 mb-4">
        <!-- General Info -->
        <div class="col-lg-6">
            <div class="card card-outline card-secondary h-100 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold fs-6 mb-0">
                        <i class="bi bi-info-circle me-1"></i> Informations Générales
                    </h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0 small">
                        <tbody>
                            <tr>
                                <th style="width: 35%;" class="bg-light ps-3">Identifiant</th>
                                <td class="font-monospace"><code>{{ $pluginId }}</code></td>
                            </tr>
                            <tr>
                                <th class="bg-light ps-3">Nom lisible</th>
                                <td class="fw-semibold">{{ $manifest->getName() }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light ps-3">Version</th>
                                <td><span class="badge text-bg-primary px-2 py-1">{{ $manifest->getVersion() }}</span></td>
                            </tr>
                            <tr>
                                <th class="bg-light ps-3">Statut actuel</th>
                                <td><span class="badge {{ $badgeClass }} {{ $bsBadgeClass }} px-2 py-1">{{ $state }}</span></td>
                            </tr>
                            <tr>
                                <th class="bg-light ps-3">Date d'installation</th>
                                <td>
                                    @if($record?->created_at)
                                        {{ $record->created_at->format('d/m/Y H:i:s') }}
                                    @else
                                        <span class="text-muted fst-italic">Non enregistrée / Non installée</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light ps-3">Chemin d'accès</th>
                                <td><code class="small text-break">{{ $plugin->getPath() }}</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Description & Author -->
        <div class="col-lg-6">
            <div class="card card-outline card-secondary h-100 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold fs-6 mb-0">
                        <i class="bi bi-person-lines-fill me-1"></i> Auteur &amp; Description
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-uppercase text-muted fw-bold fs-7 mb-1">Description</h6>
                        <p class="small mb-0">
                            {{ $manifest->getDescription() ?: 'Aucune description fournie.' }}
                        </p>
                    </div>
                    @php $author = $manifest->getAuthor(); @endphp
                    <div>
                        <h6 class="text-uppercase text-muted fw-bold fs-7 mb-2">Éditeur / Auteur</h6>
                        <table class="table table-sm table-bordered align-middle mb-0 small">
                            <tbody>
                                <tr>
                                    <th style="width: 30%;" class="bg-light">Nom</th>
                                    <td>{{ $author['name'] ?? 'Non spécifié' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Email</th>
                                    <td>
                                        @if(!empty($author['email']))
                                            <a href="mailto:{{ $author['email'] }}" class="text-decoration-none">{{ $author['email'] }}</a>
                                        @else
                                            <span class="text-muted fst-italic">Non spécifié</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Site web</th>
                                    <td>
                                        @if(!empty($author['homepage']))
                                            <a href="{{ $author['homepage'] }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                                {{ $author['homepage'] }} <i class="bi bi-box-arrow-up-right small"></i>
                                            </a>
                                        @else
                                            <span class="text-muted fst-italic">Non spécifié</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Technical Declarations Grid -->
    <div class="row g-4 mb-4">
        <!-- Compatibility & Providers -->
        <div class="col-lg-6">
            <div class="card card-outline card-secondary h-100 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold fs-6 mb-0">
                        <i class="bi bi-cpu me-1"></i> Compatibilité &amp; Fournisseurs
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-uppercase text-muted fw-bold fs-7 mb-1">Compatibilité système</h6>
                        @php $compat = $manifest->getCompatibility(); @endphp
                        @if(empty($compat))
                            <p class="text-muted small fst-italic mb-0">Aucune contrainte déclarée</p>
                        @else
                            <ul class="list-unstyled mb-0 small">
                                @foreach($compat as $key => $version)
                                    <li><i class="bi bi-check2-circle text-success me-1"></i> <strong>{{ $key }}</strong>: <code>{{ $version }}</code></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div>
                        <h6 class="text-uppercase text-muted fw-bold fs-7 mb-1">Fournisseurs de services (Providers)</h6>
                        @php $providers = $manifest->getProviders(); @endphp
                        @if(empty($providers))
                            <p class="text-muted small fst-italic mb-0">Aucun ServiceProvider déclaré</p>
                        @else
                            <ul class="list-unstyled mb-0 font-monospace small">
                                @foreach($providers as $provider)
                                    <li class="text-break"><i class="bi bi-box me-1 text-primary"></i> {{ $provider }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions & Routes -->
        <div class="col-lg-6">
            <div class="card card-outline card-secondary h-100 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold fs-6 mb-0">
                        <i class="bi bi-shield-check me-1"></i> Permissions &amp; Routes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-uppercase text-muted fw-bold fs-7 mb-2">Permissions déclarées</h6>
                        @php $permissions = $manifest->getPermissions(); @endphp
                        @if(empty($permissions))
                            <p class="text-muted small fst-italic mb-0">Aucune permission déclarée</p>
                        @else
                            <table class="table table-sm table-bordered align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Clé</th>
                                        <th>Libellé</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($permissions as $perm)
                                        <tr>
                                            <td><code>{{ $perm['key'] }}</code></td>
                                            <td>{{ $perm['label'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                    <div>
                        <h6 class="text-uppercase text-muted fw-bold fs-7 mb-1">Fichiers de routes</h6>
                        @php $routes = $manifest->getRoutes(); @endphp
                        @if(empty(array_filter($routes)))
                            <p class="text-muted small fst-italic mb-0">Aucune route personnalisée déclarée</p>
                        @else
                            <ul class="list-unstyled mb-0 small">
                                @foreach($routes as $channel => $file)
                                    @if($file)
                                        <li><strong>{{ strtoupper($channel) }}</strong> : <code>{{ $file }}</code></li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Direct Dependencies -->
    <div class="card card-outline card-secondary shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title fw-bold fs-6 mb-0">
                <i class="bi bi-diagram-3 me-1"></i> Dépendances requises par cette extension
            </h5>
            <small class="text-muted d-block mt-1">
                Vérification de l'état d'installation et d'activation des prérequis
            </small>
        </div>
        <div class="card-body p-0">
            @if(empty($dependenciesHealth))
                <div class="text-center text-muted py-4 small">
                    Cette extension ne requiert aucun autre plugin pour fonctionner.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light fs-7 text-uppercase text-muted">
                            <tr>
                                <th>Identifiant du plugin</th>
                                <th>Contrainte déclarée</th>
                                <th>État de résolution</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dependenciesHealth as $depId => $dep)
                                @php
                                    $badgeDep = match($dep['status']) {
                                        'satisfied' => 'badge-active',
                                        'disabled' => 'badge-disabled',
                                        'available' => 'badge-available',
                                        'missing' => 'badge-error',
                                        default => 'badge-available',
                                    };
                                    $bsBadgeDep = match($dep['status']) {
                                        'satisfied' => 'text-bg-success',
                                        'disabled' => 'text-bg-warning',
                                        'available' => 'text-bg-secondary',
                                        'missing' => 'text-bg-danger',
                                        default => 'text-bg-secondary',
                                    };
                                    $labelDep = match($dep['status']) {
                                        'satisfied' => 'Satisfaite (Active)',
                                        'disabled' => 'Installée mais Inactive',
                                        'available' => 'Disponible non installée',
                                        'missing' => 'Manquante / Inexistante',
                                        default => 'Inconnue',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $depId }}</strong>
                                        @if($dep['plugin'] !== null)
                                            <div class="small text-muted">
                                                {{ $dep['plugin']->getManifest()->getName() }}
                                            </div>
                                        @endif
                                    </td>
                                    <td><code>{{ $dep['constraint'] }}</code></td>
                                    <td>
                                        <span class="badge {{ $badgeDep }} {{ $bsBadgeDep }} px-2 py-1">{{ $labelDep }}</span>
                                    </td>
                                    <td class="text-end pe-3">
                                        @if($dep['plugin'] !== null)
                                            <a href="{{ route('admin.plugins.show', $depId) }}" class="btn btn-sm btn-outline-secondary">
                                                Inspecter
                                            </a>
                                        @else
                                            <span class="text-danger small fw-semibold">Action requise</span>
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

    <!-- Reverse Dependents -->
    <div class="card card-outline card-secondary shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title fw-bold fs-6 mb-0">
                <i class="bi bi-diagram-2 me-1"></i> Extensions dépendantes (Dépendances inverses)
            </h5>
            <small class="text-muted d-block mt-1">
                Extensions installées ou détectées qui ont déclaré une dépendance sur {{ $pluginId }}
            </small>
        </div>
        <div class="card-body p-0">
            @if(empty($dependents))
                <div class="text-center text-muted py-4 small">
                    Aucune extension n'a déclaré de dépendance envers cette extension.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light fs-7 text-uppercase text-muted">
                            <tr>
                                <th>Extension dépendante</th>
                                <th>Statut actuel</th>
                                <th>Impact si désactivation</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dependents as $depId => $depPlugin)
                                @php
                                    $depState = strtoupper($depPlugin->getState());
                                    $depActive = $depPlugin->isActive();
                                    $badgeDepClass = match($depState) {
                                        'ACTIVE' => 'badge-active',
                                        'DISABLED' => 'badge-disabled',
                                        'INSTALLED' => 'badge-installed',
                                        'ERROR' => 'badge-error',
                                        default => 'badge-available',
                                    };
                                    $bsBadgeDepClass = match($depState) {
                                        'ACTIVE' => 'text-bg-success',
                                        'DISABLED' => 'text-bg-warning',
                                        'INSTALLED' => 'text-bg-primary',
                                        'ERROR' => 'text-bg-danger',
                                        default => 'text-bg-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $depPlugin->getManifest()->getName() }}</strong>
                                        <div class="font-monospace small text-muted">{{ $depId }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeDepClass }} {{ $bsBadgeDepClass }} px-2 py-1">{{ $depState }}</span>
                                    </td>
                                    <td>
                                        @if($depActive)
                                            <span class="text-danger fw-semibold small">
                                                <i class="bi bi-exclamation-octagon me-1"></i> Blocage : l'extension est active
                                            </span>
                                        @else
                                            <span class="text-muted small">
                                                Inactif (aucun impact immédiat)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('admin.plugins.show', $depId) }}" class="btn btn-sm btn-outline-secondary">
                                            Inspecter
                                        </a>
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
