<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Gestion des Thèmes</title>
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
            padding: 0;
            display: flex;
            min-height: 100vh;
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
        .badge-active { background-color: #d1fae5; color: #065f46; }
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
        .btn-secondary {
            background-color: #64748b;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #475569;
        }
        .actions-form {
            display: inline-block;
            margin: 0;
        }
        .theme-name {
            font-weight: 600;
            font-size: 1rem;
        }
        .theme-id {
            font-family: monospace;
            font-size: 0.85rem;
            color: var(--muted-color);
        }
        .theme-desc {
            font-size: 0.85rem;
            color: #475569;
            margin-top: 0.25rem;
        }
        .empty-state {
            padding: 3rem;
            text-align: center;
            color: var(--muted-color);
        }
    </style>
</head>
<body>
    @include('core::admin.layouts.sidebar')

    <div class="container" style="flex: 1; padding: 2rem;">
        <header>
            <div>
                <h1>Gestion des Thèmes CMS</h1>
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

        <div class="card">
            @if(empty($themes))
                <div class="empty-state">
                    <h3>Aucun thème détecté</h3>
                    <p>Déposez un dossier de thème conforme dans <code>platform/themes/</code> avec son fichier <code>theme.json</code> pour qu'il apparaisse ici.</p>
                </div>
            @else
                <table>
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
                                    <div class="theme-name">{{ $theme->getName() }}</div>
                                    <div class="theme-id">{{ $themeId }}</div>
                                    @if($manifest->getDescription())
                                        <div class="theme-desc">{{ $manifest->getDescription() }}</div>
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
    </div>
</body>
</html>

