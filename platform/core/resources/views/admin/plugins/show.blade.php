@extends('core::admin.layouts.app')

@section('title', 'Administration - Détails Plugin : ' . $manifest->getName())

@section('content')
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('admin.plugins.index') }}" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
            ← Retour à la liste des extensions
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
    @endphp

    <header class="admin-header">
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <h1 class="admin-title">{{ $manifest->getName() }}</h1>
                <span class="badge {{ $badgeClass }}">{{ $state }}</span>
            </div>
            <p style="margin: 0.25rem 0 0 0; font-family: monospace; color: var(--muted-color); font-size: 0.95rem;">
                {{ $pluginId }}
            </p>
        </div>
        <div>
            @if($isSafeMode)
                <span class="badge badge-error" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                    MODE DE SECOURS ACTIF
                </span>
            @endif
        </div>
    </header>

    @if($isSafeMode)
        <div class="alert alert-warning">
            <strong>Attention :</strong> Le mode de récupération d'urgence (Safe Mode) est activé. Cette extension tierce est actuellement neutralisée au démarrage.
        </div>
    @endif

    @if($state === 'ERROR' && $record?->error_message)
        <div class="alert alert-error">
            <strong>Erreur d'exécution isolée :</strong>
            <pre style="margin: 0.5rem 0 0 0; font-family: monospace; font-size: 0.85rem; white-space: pre-wrap;">{{ $record->error_message }}</pre>
        </div>
    @endif

    @if(!empty($activeDependents))
        <div class="alert alert-warning">
            <strong>Avertissement de dépendance :</strong> Cette extension est requise par {{ count($activeDependents) }} extension(s) actuellement active(s) :
            <strong>{{ implode(', ', array_map(fn ($p) => $p->getManifest()->getName() . ' (' . $p->getManifest()->getId() . ')', $activeDependents)) }}</strong>.
            Sa désactivation ou sa désinstallation sans forçage sera bloquée pour garantir la stabilité du système.
        </div>
    @endif

    <!-- Actions Toolbar Card -->
    <div class="card" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; background-color: #f8fafc;">
        <div>
            <h3 style="margin: 0 0 0.25rem 0; font-size: 1.05rem;">Actions sur le cycle de vie</h3>
            <p style="margin: 0; color: var(--muted-color); font-size: 0.85rem;">
                Contrôle direct de l'état de l'extension avec vérification de dépendances
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            @if($state === 'AVAILABLE')
                <form method="POST" action="{{ route('admin.plugins.enable', $pluginId) }}" class="actions-form">
                    @csrf
                    <button type="submit" class="btn btn-primary">Installer et Activer</button>
                </form>
            @elseif($state === 'INSTALLED' || $state === 'DISABLED')
                <form method="POST" action="{{ route('admin.plugins.enable', $pluginId) }}" class="actions-form">
                    @csrf
                    <button type="submit" class="btn btn-primary">Activer l'extension</button>
                </form>
            @elseif($state === 'ACTIVE')
                <form method="POST" action="{{ route('admin.plugins.disable', $pluginId) }}" class="actions-form" onsubmit="return confirm('Confirmer la désactivation de {{ $manifest->getName() }} ?');">
                    @csrf
                    @if(!empty($activeDependents))
                        <input type="hidden" name="force" value="1">
                    @endif
                    <button type="submit" class="btn btn-secondary">
                        Désactiver l'extension
                        @if(!empty($activeDependents)) (Forcer) @endif
                    </button>
                </form>
            @elseif($state === 'ERROR')
                <form method="POST" action="{{ route('admin.plugins.disable', $pluginId) }}" class="actions-form">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Mettre en quarantaine / Désactiver</button>
                </form>
            @endif

            @if($state !== 'AVAILABLE')
                <form method="POST" action="{{ route('admin.plugins.uninstall', $pluginId) }}" class="actions-form" onsubmit="return confirm('Attention : Êtes-vous sûr de vouloir désinstaller cette extension ?');" style="display: flex; align-items: center; gap: 0.5rem;">
                    @csrf
                    <label style="font-size: 0.85rem; color: var(--muted-color); display: flex; align-items: center; gap: 0.25rem; cursor: pointer;">
                        <input type="checkbox" name="purge_data" value="1">
                        Purger les données
                    </label>
                    @if(!empty($activeDependents))
                        <input type="hidden" name="force" value="1">
                    @endif
                    <button type="submit" class="btn btn-danger">
                        Désinstaller
                        @if(!empty($activeDependents)) (Forcer) @endif
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Metadata Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
        <!-- General Info -->
        <div class="card" style="margin-bottom: 0;">
            <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                Informations Générales
            </h3>
            <table class="table" style="font-size: 0.9rem;">
                <tbody>
                    <tr>
                        <td style="font-weight: 600; width: 35%;">Identifiant</td>
                        <td><code>{{ $pluginId }}</code></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Nom lisible</td>
                        <td>{{ $manifest->getName() }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Version</td>
                        <td><span class="badge badge-installed">{{ $manifest->getVersion() }}</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Statut actuel</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $state }}</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Date d'installation</td>
                        <td>
                            @if($record?->created_at)
                                {{ $record->created_at->format('d/m/Y H:i:s') }}
                            @else
                                <span style="color: var(--muted-color); font-style: italic;">Non enregistrée / Non installée</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Chemin d'accès</td>
                        <td><code style="word-break: break-all; font-size: 0.8rem;">{{ $plugin->getPath() }}</code></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Description & Author -->
        <div class="card" style="margin-bottom: 0;">
            <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                Auteur & Description
            </h3>
            <div style="margin-bottom: 1rem;">
                <h4 style="margin: 0 0 0.35rem 0; font-size: 0.95rem; color: var(--muted-color);">Description</h4>
                <p style="margin: 0; font-size: 0.95rem; line-height: 1.5;">
                    {{ $manifest->getDescription() ?: 'Aucune description fournie.' }}
                </p>
            </div>
            @php $author = $manifest->getAuthor(); @endphp
            <div>
                <h4 style="margin: 0 0 0.35rem 0; font-size: 0.95rem; color: var(--muted-color);">Éditeur / Auteur</h4>
                <table class="table" style="font-size: 0.9rem;">
                    <tbody>
                        <tr>
                            <td style="font-weight: 600; width: 30%;">Nom</td>
                            <td>{{ $author['name'] ?? 'Non spécifié' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600;">Email</td>
                            <td>
                                @if(!empty($author['email']))
                                    <a href="mailto:{{ $author['email'] }}" style="color: var(--primary);">{{ $author['email'] }}</a>
                                @else
                                    <span style="color: var(--muted-color); font-style: italic;">Non spécifié</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600;">Site web</td>
                            <td>
                                @if(!empty($author['homepage']))
                                    <a href="{{ $author['homepage'] }}" target="_blank" rel="noopener noreferrer" style="color: var(--primary);">{{ $author['homepage'] }}</a>
                                @else
                                    <span style="color: var(--muted-color); font-style: italic;">Non spécifié</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Technical Declarations Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
        <!-- Compatibility & Providers -->
        <div class="card" style="margin-bottom: 0;">
            <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                Compatibilité & Fournisseurs
            </h3>
            <div style="margin-bottom: 1rem;">
                <h4 style="margin: 0 0 0.35rem 0; font-size: 0.95rem; color: var(--muted-color);">Compatibilité système</h4>
                @php $compat = $manifest->getCompatibility(); @endphp
                @if(empty($compat))
                    <p style="margin: 0; color: var(--muted-color); font-size: 0.85rem; font-style: italic;">Aucune contrainte déclarée</p>
                @else
                    <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.9rem;">
                        @foreach($compat as $key => $version)
                            <li><strong>{{ $key }}</strong>: <code>{{ $version }}</code></li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                <h4 style="margin: 0 0 0.35rem 0; font-size: 0.95rem; color: var(--muted-color);">Fournisseurs de services (Providers)</h4>
                @php $providers = $manifest->getProviders(); @endphp
                @if(empty($providers))
                    <p style="margin: 0; color: var(--muted-color); font-size: 0.85rem; font-style: italic;">Aucun ServiceProvider déclaré</p>
                @else
                    <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem; font-family: monospace;">
                        @foreach($providers as $provider)
                            <li>{{ $provider }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <!-- Permissions & Routes -->
        <div class="card" style="margin-bottom: 0;">
            <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                Permissions & Routes
            </h3>
            <div style="margin-bottom: 1rem;">
                <h4 style="margin: 0 0 0.35rem 0; font-size: 0.95rem; color: var(--muted-color);">Permissions déclarées</h4>
                @php $permissions = $manifest->getPermissions(); @endphp
                @if(empty($permissions))
                    <p style="margin: 0; color: var(--muted-color); font-size: 0.85rem; font-style: italic;">Aucune permission déclarée</p>
                @else
                    <table class="table" style="font-size: 0.85rem;">
                        <thead>
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
                <h4 style="margin: 0 0 0.35rem 0; font-size: 0.95rem; color: var(--muted-color);">Fichiers de routes</h4>
                @php $routes = $manifest->getRoutes(); @endphp
                @if(empty(array_filter($routes)))
                    <p style="margin: 0; color: var(--muted-color); font-size: 0.85rem; font-style: italic;">Aucune route personnalisée déclarée</p>
                @else
                    <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem;">
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

    <!-- Direct Dependencies -->
    <div class="card" style="padding: 0; margin-bottom: 1.5rem;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color);">
            <h3 style="margin: 0; font-size: 1.1rem;">Dépendances requises par cette extension</h3>
            <p style="margin: 0.25rem 0 0 0; color: var(--muted-color); font-size: 0.85rem;">
                Vérification de l'état d'installation et d'activation des prérequis
            </p>
        </div>
        @if(empty($dependenciesHealth))
            <div style="padding: 2rem; text-align: center; color: var(--muted-color); font-size: 0.9rem;">
                Cette extension ne requiert aucun autre plugin pour fonctionner.
            </div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Identifiant du plugin</th>
                        <th>Contrainte déclarée</th>
                        <th>État de résolution</th>
                        <th>Action</th>
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
                                    <div style="font-size: 0.85rem; color: var(--muted-color);">
                                        {{ $dep['plugin']->getManifest()->getName() }}
                                    </div>
                                @endif
                            </td>
                            <td><code>{{ $dep['constraint'] }}</code></td>
                            <td>
                                <span class="badge {{ $badgeDep }}">{{ $labelDep }}</span>
                            </td>
                            <td>
                                @if($dep['plugin'] !== null)
                                    <a href="{{ route('admin.plugins.show', $depId) }}" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                                        Inspecter
                                    </a>
                                @else
                                    <span style="color: var(--danger); font-size: 0.85rem;">Action requise</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <!-- Reverse Dependents -->
    <div class="card" style="padding: 0;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color);">
            <h3 style="margin: 0; font-size: 1.1rem;">Extensions dépendantes (Dépendances inverses)</h3>
            <p style="margin: 0.25rem 0 0 0; color: var(--muted-color); font-size: 0.85rem;">
                Extensions installées ou détectées qui ont déclaré une dépendance sur {{ $pluginId }}
            </p>
        </div>
        @if(empty($dependents))
            <div style="padding: 2rem; text-align: center; color: var(--muted-color); font-size: 0.9rem;">
                Aucune extension n'a déclaré de dépendance envers cette extension.
            </div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Extension dépendante</th>
                        <th>Statut actuel</th>
                        <th>Impact si désactivation</th>
                        <th>Action</th>
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
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $depPlugin->getManifest()->getName() }}</strong>
                                <div style="font-family: monospace; font-size: 0.85rem; color: var(--muted-color);">{{ $depId }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $badgeDepClass }}">{{ $depState }}</span>
                            </td>
                            <td>
                                @if($depActive)
                                    <span style="color: var(--danger); font-weight: 600; font-size: 0.85rem;">
                                        ⚠️ Blocage : l'extension est active
                                    </span>
                                @else
                                    <span style="color: var(--muted-color); font-size: 0.85rem;">
                                        Inactif (aucun impact immédiat)
                                    </span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.plugins.show', $depId) }}" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                                    Inspecter
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
