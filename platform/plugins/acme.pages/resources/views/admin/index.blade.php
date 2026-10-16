<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestion des Pages - CMS Admin</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; padding: 2rem; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; }
        .form-box { background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 2rem; }
        .table-box { background: white; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; font-weight: 600; font-size: 0.875rem; }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-published { background: #dcfce7; color: #166534; }
        .badge-draft { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Pages Statiques</h1>
            <a href="/admin/plugins" style="color: #4f46e5; text-decoration: none;">&larr; Retour aux Plugins</a>
        </header>

        @if(session('success'))
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                {{ session('success') }}
            </div>
        @endif

        <div class="form-box">
            <h3>Créer une nouvelle page</h3>
            <form action="/admin/pages" method="POST">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.25rem;">Titre :</label>
                    <input type="text" name="title" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.25rem;">Slug (optionnel) :</label>
                    <input type="text" name="slug" placeholder="ex: a-propos" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.25rem;">Contenu :</label>
                    <textarea name="content" rows="5" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;"></textarea>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="is_published" value="1">
                        Publier immédiatement
                    </label>
                </div>
                <button type="submit" style="background: #4f46e5; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer;">
                    Enregistrer la page
                </button>
            </form>
        </div>

        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Slug / URL</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr>
                            <td><strong>{{ $page->title }}</strong></td>
                            <td>
                                @if($page->is_published)
                                    <a href="/page/{{ $page->slug }}" target="_blank" style="color: #4f46e5;">/page/{{ $page->slug }}</a>
                                @else
                                    <span style="color: #94a3b8;">/page/{{ $page->slug }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $page->is_published ? 'badge-published' : 'badge-draft' }}">
                                    {{ $page->is_published ? 'Publié' : 'Brouillon' }}
                                </span>
                            </td>
                            <td>
                                <form action="/admin/pages/{{ $page->id }}" method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cette page ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="color: #ef4444; background: none; border: none; cursor: pointer; text-decoration: underline;">
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: #64748b; padding: 2rem;">Aucune page créée pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
