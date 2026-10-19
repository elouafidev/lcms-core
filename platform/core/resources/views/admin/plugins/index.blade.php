@extends('core::admin.layouts.app')

@section('title', 'Administration - Gestion des Plugins')

@section('content')
    <header class="admin-header">
        <div>
            <h1 class="admin-title">Gestion des Extensions CMS</h1>
            <p style="margin: 0.25rem 0 0 0; color: var(--muted-color); font-size: 0.9rem;">
                Noyau minimalisé et architecture modulaire sécurisée
            </p>
        </div>
        @if($isSafeMode)
            <span class="badge badge-error" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                MODE DE SECOURS (SAFE MODE) ACTIF
            </span>
        @endif
    </header>

    @if($isSafeMode)
        <div class="alert alert-warning">
            <strong>Attention :</strong> Le mode de récupération d'urgence (Safe Mode) est activé. Toutes les extensions tierces sont neutralisées au démarrage pour protéger la stabilité du système.
        </div>
    @endif

    @php
        $quarantinedPlugins = $plugins->filter(fn ($p) => strtoupper($p->getState()) === 'ERROR');
    @endphp

    @if($quarantinedPlugins->isNotEmpty())
        <div class="alert alert-error">
            <strong>Attention critique :</strong> Des extensions défaillantes ont été isolées en quarantaine ({{ $quarantinedPlugins->map(fn ($p) => $p->getManifest()->getName())->join(', ') }}). Consultez les détails ou désactivez-les pour restaurer la pleine conformité.
        </div>
    @endif

    <div class="card" style="padding: 0;">
        @if($plugins->isEmpty())
            <div style="padding: 3rem; text-align: center; color: var(--muted-color);">
                <h3>Aucune extension détectée</h3>
                <p>Déposez un dossier de plugin conforme dans <code>platform/plugins/</code> pour qu'il soit automatiquement découvert.</p>
            </div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Extension</th>
                        <th>Version</th>
                        <th>Statut</th>
                        <th>Actions</th>
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
                        @endphp
                        <tr>
                            <td>
                                <div style="font-weight: 600; font-size: 1rem;">
                                    <a href="{{ route('admin.plugins.show', $pluginId) }}" style="color: var(--primary); text-decoration: none;" title="Voir les détails et configurer">{{ $manifest->getName() }}</a>
                                </div>
                                <div style="font-family: monospace; font-size: 0.85rem; color: var(--muted-color);">{{ $pluginId }}</div>
                                @if($state === 'ERROR' && $plugin instanceof \App\Core\PluginRegistry\Models\PluginRecord && $plugin->error_message)
                                    <div style="color: var(--danger); font-size: 0.8rem; margin-top: 0.35rem; font-family: monospace;">
                                        {{ $plugin->error_message }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $manifest->getVersion() }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }}">{{ $state }}</span>
                            </td>
                            <td>
                                @if($state === 'AVAILABLE')
                                    <form method="POST" action="{{ route('admin.plugins.enable', $pluginId) }}" class="actions-form">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">Installer et Activer</button>
                                    </form>
                                @elseif($state === 'INSTALLED' || $state === 'DISABLED')
                                    <form method="POST" action="{{ route('admin.plugins.enable', $pluginId) }}" class="actions-form">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">Activer</button>
                                    </form>
                                @elseif($state === 'ACTIVE')
                                    <form method="POST" action="{{ route('admin.plugins.disable', $pluginId) }}" class="actions-form">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary">Désactiver</button>
                                    </form>
                                @elseif($state === 'ERROR')
                                    <form method="POST" action="{{ route('admin.plugins.disable', $pluginId) }}" class="actions-form">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary">Mettre en quarantaine</button>
                                    </form>
                                @endif

                                @if($state !== 'AVAILABLE')
                                    <form method="POST" action="{{ route('admin.plugins.uninstall', $pluginId) }}" class="actions-form" onsubmit="return confirm('Confirmer la désinstallation ?');">
                                        @csrf
                                        <button type="submit" class="btn btn-danger">Désinstaller</button>
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
