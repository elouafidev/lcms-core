<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $menu->name }} — Gestion du Menu</title>
    <style>
        body { font-family: system-ui, sans-serif; line-height: 1.5; padding: 2rem; background: #f8fafc; color: #1e293b; }
        .card { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .table th, .table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #3b82f6; color: white; border-radius: 4px; text-decoration: none; border: none; cursor: pointer; }
        .btn-danger { background: #ef4444; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.25rem; }
        .form-group input, .form-group select { width: 100%; max-width: 500px; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body>
    <p><a href="/admin/menus">&larr; Retour aux menus</a></p>
    <h1>{{ $menu->name }} (<code>{{ $menu->slug }}</code>)</h1>

    @if(session('success'))
        <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <h2>Ajouter un lien</h2>
        <form action="/admin/menus/{{ $menu->id }}/items" method="POST">
            @csrf
            <div class="form-group">
                <label for="title">Titre du lien</label>
                <input type="text" id="title" name="title" required placeholder="ex: Accueil, Services, Contact...">
            </div>
            <div class="form-group">
                <label for="url">URL cible</label>
                <input type="text" id="url" name="url" required placeholder="ex: / ou /page/about-us">
            </div>
            <div class="form-group">
                <label for="parent_id">Élément parent (optionnel)</label>
                <select id="parent_id" name="parent_id">
                    <option value="">-- Aucun parent (Lien racine) --</option>
                    @foreach($menu->items as $parentOption)
                        <option value="{{ $parentOption->id }}">{{ $parentOption->title }} ({{ $parentOption->url }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="order">Ordre d'affichage</label>
                <input type="number" id="order" name="order" value="0">
            </div>
            <button type="submit" class="btn">Ajouter le lien</button>
        </form>
    </div>

    <div class="card">
        <h2>Arborescence des liens</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Ordre</th>
                    <th>Titre</th>
                    <th>URL</th>
                    <th>Parent</th>
                    <th>Cible</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($menu->items as $item)
                    <tr>
                        <td>{{ $item->order }}</td>
                        <td><strong>{{ $item->title }}</strong></td>
                        <td><code>{{ $item->url }}</code></td>
                        <td>{{ $item->parent ? $item->parent->title : 'Racine' }}</td>
                        <td>{{ $item->target }}</td>
                        <td>
                            <form action="/admin/menus/items/{{ $item->id }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Supprimer ce lien ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748b;">Aucun élément dans ce menu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
