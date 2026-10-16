<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Gestion des Plugins</title>
    <style>
        :root {
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-color: #0f172a;
            --muted-color: #64748b;
            --border-color: #e2e8f0;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --success: #10b981;
            --warning: #f59e0b;
        }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 2rem;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        h1 {
            font-size: 1.75rem;
            margin: 0;
            font-weight: 700;
        }
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-warning {
            background-color: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        th, td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
        }
        th {
            background-color: #f1f5f9;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted-color);
        }
        tr:last-child td {
            border-bottom: none;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-available { background-color: #e2e8f0; color: #475569; }
        .badge-installed { background-color: #dbeafe; color: #1e40af; }
        .badge-active { background-color: #d1fae5; color: #065f46; }
        .badge-disabled { background-color: #fef3c7; color: #92400e; }
        .badge-error { background-color: #fee2e2; color: #991b1b; }
        .btn {
            display: inline-block;
            padding: 0.4rem 0.85rem;
            font-size: 0.85rem;
            font-weight: 500;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
            text-decoration: none;
        }
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        .btn-danger:hover {
            background-color: var(--danger-hover);
        }
        .btn-secondary {
            background-color: #64748b;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #475569;
        }
        .actions-form {
            display: inline-block;
            margin-right: 0.25rem;
        }
        .plugin-name {
            font-weight: 600;
            font-size: 1rem;
        }
        .plugin-id {
            font-family: monospace;
            font-size: 0.85rem;
            color: var(--muted-color);
        }
        .empty-state {
            padding: 3rem;
            text-align: center;
            color: var(--muted-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h1>Gestion des Extensions CMS</h1>
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

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

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

        <div class="card">
            @if($plugins->isEmpty())
                <div class="empty-state">
                    <h3>Aucune extension détectée</h3>
                    <p>Déposez un dossier de plugin conforme dans <code>platform/plugins/</code> pour qu'il soit automatiquement découvert.</p>
                </div>
            @else
                <table>
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
                                    <div class="plugin-name">{{ $manifest->getName() }}</div>
                                    <div class="plugin-id">{{ $pluginId }}</div>
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
    </div>
</body>
</html>

