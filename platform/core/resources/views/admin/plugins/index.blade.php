@extends('core::admin.layouts.app')

@section('title', 'Administration - Gestion des Plugins')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">Gestion des Extensions CMS</h1>
            <p class="text-muted small mb-0">
                Noyau minimalisé et architecture modulaire sécurisée
            </p>
        </div>
        <div>
            @if($isSafeMode)
                <span class="badge text-bg-danger fs-6 px-3 py-2 shadow-sm">
                    <i class="bi bi-shield-slash me-1"></i> MODE DE SECOURS (SAFE MODE) ACTIF
                </span>
            @endif
        </div>
    </div>

    @if($isSafeMode)
        <div class="alert alert-warning shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                <div>
                    <strong>Attention :</strong> Le mode de récupération d'urgence (Safe Mode) est activé. Toutes les extensions tierces sont neutralisées au démarrage pour protéger la stabilité du système.
                </div>
            </div>
        </div>
    @endif

    @php
        $quarantinedPlugins = $plugins->filter(fn ($p) => strtoupper($p->getState()) === 'ERROR');
    @endphp

    @if($quarantinedPlugins->isNotEmpty())
        <div class="alert alert-danger alert-error shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-bug-fill fs-4 me-3"></i>
                <div>
                    <strong>Attention critique :</strong> Des extensions défaillantes ont été isolées en quarantaine ({{ $quarantinedPlugins->map(fn ($p) => $p->getManifest()->getName())->join(', ') }}). Consultez les détails ou désactivez-les pour restaurer la pleine conformité.
                </div>
            </div>
        </div>
    @endif

    <!-- Card Upload ZIP Plugin -->
    <div class="card card-outline card-primary shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-lg-6">
                    <h5 class="fw-bold mb-1 text-primary">
                        <i class="bi bi-file-earmark-zip me-1"></i> Importer une extension (.zip)
                    </h5>
                    <p class="text-muted small mb-0">
                        Sélectionnez une archive ZIP contenant un plugin LCMS conforme avec son manifeste <code>plugin.json</code>.
                    </p>
                </div>
                <div class="col-lg-6">
                    <form method="POST" action="{{ route('admin.plugins.upload') }}" enctype="multipart/form-data" class="d-flex align-items-center gap-2 flex-wrap justify-content-lg-end">
                        @csrf
                        <input type="file" name="plugin_archive" accept=".zip,application/zip" required class="form-control form-control-sm" style="max-width: 250px;">
                        <div class="form-check me-2">
                            <input class="form-check-input" type="checkbox" name="overwrite" value="1" id="overwritePlugin">
                            <label class="form-check-label small text-muted" for="overwritePlugin">
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

    <!-- Plugins Table Card -->
    <div class="card card-outline card-secondary shadow-sm">
        <div class="card-header bg-white py-3">
            <h3 class="card-title fw-bold fs-6 mb-0 text-secondary">
                <i class="bi bi-puzzle me-1"></i> Extensions disponibles &amp; installées
            </h3>
        </div>
        <div class="card-body p-0">
            @if($plugins->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-folder-x fs-1 d-block mb-2 text-muted"></i>
                    <h4>Aucune extension détectée</h4>
                    <p class="small">Déposez un dossier de plugin conforme dans <code>platform/plugins/</code> pour qu'il soit automatiquement découvert.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7 text-muted">
                            <tr>
                                <th style="width: 45%;">Extension</th>
                                <th style="width: 15%;">Version</th>
                                <th style="width: 15%;">Statut</th>
                                <th style="width: 25%;" class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plugins as $plugin)
                                @php
                                    $manifest = $plugin->getManifest();
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
                                <tr>
                                    <td>
                                        <div class="fw-bold">
                                            <a href="{{ route('admin.plugins.show', $pluginId) }}" class="text-primary text-decoration-none" title="Voir les détails et configurer">
                                                {{ $manifest->getName() }}
                                            </a>
                                        </div>
                                        <div class="font-monospace small text-muted">{{ $pluginId }}</div>
                                        @if($state === 'ERROR' && $plugin instanceof \App\Core\PluginRegistry\Models\PluginRecord && $plugin->error_message)
                                            <div class="text-danger small font-monospace mt-1">
                                                <i class="bi bi-x-circle me-1"></i> {{ $plugin->error_message }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $manifest->getVersion() }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeClass }} {{ $bsBadgeClass }} px-2 py-1">{{ $state }}</span>
                                    </td>
                                    <td class="text-end pe-3">
                                        @if($state === 'AVAILABLE')
                                            <form method="POST" action="{{ route('admin.plugins.enable', $pluginId) }}" class="actions-form">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">Installer et Activer</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.plugins.delete', $pluginId) }}" class="actions-form" onsubmit="return confirm('Confirmer la suppression définitive des fichiers de l\'extension \'{{ $manifest->getName() }}\' ?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                            </form>
                                        @elseif($state === 'INSTALLED' || $state === 'DISABLED')
                                            <form method="POST" action="{{ route('admin.plugins.enable', $pluginId) }}" class="actions-form">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">Activer</button>
                                            </form>
                                        @elseif($state === 'ACTIVE')
                                            <form method="POST" action="{{ route('admin.plugins.disable', $pluginId) }}" class="actions-form">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary">Désactiver</button>
                                            </form>
                                        @elseif($state === 'ERROR')
                                            <form method="POST" action="{{ route('admin.plugins.disable', $pluginId) }}" class="actions-form">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary">Mettre en quarantaine</button>
                                            </form>
                                        @endif

                                        @if($state !== 'AVAILABLE')
                                            <form method="POST" action="{{ route('admin.plugins.uninstall', $pluginId) }}" class="actions-form" onsubmit="return confirm('Confirmer la désinstallation ?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger">Désinstaller</button>
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
