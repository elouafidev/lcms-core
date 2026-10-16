<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menus de Navigation — Administration</title>
    <style>
        body { font-family: system-ui, sans-serif; line-height: 1.5; padding: 2rem; background: #f8fafc; color: #1e293b; }
        .card { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .table th, .table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #3b82f6; color: white; border-radius: 4px; text-decoration: none; border: none; cursor: pointer; }
        .btn-danger { background: #ef4444; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.25rem; }
        .form-group input, .form-group textarea { width: 100%; max-width: 500px; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Menus de Navigation</h1>

    @if(session('success'))
        <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <h2>Créer un nouveau menu</h2>
        <form action="/admin/menus" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Nom du menu</label>
                <input type="text" id="name" name="name" required placeholder="ex: Menu Principal">
            </div>
            <div class="form-group">
                <label for="slug">Identifiant (slug)</label>
                <input type="text" id="slug" name="slug" required placeholder="ex: main-nav">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="2" placeholder="Description optionnelle"></textarea>
            </div>
            <button type="submit" class="btn">Créer le menu</button>
        </form>
    </div>

    <div class="card">
        <h2>Menus existants</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Slug</th>
                    <th>Éléments</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($menus as $menu)
                    <tr>
                        <td>{{ $menu->id }}</td>
                        <td><strong>{{ $menu->name }}</strong></td>
                        <td><code>{{ $menu->slug }}</code></td>
                        <td>{{ $menu->items_count }} élément(s)</td>
                        <td>{{ $menu->is_active ? 'Actif' : 'Inactif' }}</td>
                        <td>
                            <a href="/admin/menus/{{ $menu->id }}" class="btn">Gérer</a>
                            <form action="/admin/menus/{{ $menu->id }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Confirmer la suppression ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748b;">Aucun menu de navigation configuré pour le moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
