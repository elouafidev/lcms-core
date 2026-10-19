@extends('core::admin.layouts.app')

@section('title', $menu->name . ' — Gestion du Menu')

@section('content')
    <header class="admin-header">
        <div>
            <p style="margin: 0 0 0.5rem 0;">
                <a href="/admin/menus" style="color: var(--primary); text-decoration: none; font-size: 0.9rem;">&larr; Retour aux menus</a>
            </p>
            <h1 class="admin-title">{{ $menu->name }} <code style="font-size: 1rem; color: var(--muted-color); font-weight: 400;">({{ $menu->slug }})</code></h1>
        </div>
    </header>

    <div class="card">
        <h2 style="font-size: 1.25rem; margin-top: 0; margin-bottom: 1rem;">Ajouter un lien</h2>
        <form action="/admin/menus/{{ $menu->id }}/items" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label for="title" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Titre du lien</label>
                <input type="text" id="title" name="title" required placeholder="ex: Accueil, Services, Contact..." style="width: 100%; max-width: 500px; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label for="url" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">URL cible</label>
                <input type="text" id="url" name="url" required placeholder="ex: / ou /page/about-us" style="width: 100%; max-width: 500px; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label for="parent_id" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Élément parent (optionnel)</label>
                <select id="parent_id" name="parent_id" style="width: 100%; max-width: 500px; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-sizing: border-box; background: white;">
                    <option value="">-- Aucun parent (Lien racine) --</option>
                    @foreach($menu->items as $parentOption)
                        <option value="{{ $parentOption->id }}">{{ $parentOption->title }} ({{ $parentOption->url }})</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 1rem;">
                <label for="order" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Ordre d'affichage</label>
                <input type="number" id="order" name="order" value="0" style="width: 100px; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-sizing: border-box;">
            </div>
            <button type="submit" class="btn btn-primary">Ajouter le lien</button>
        </form>
    </div>

    <div class="card" style="padding: 0;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color);">
            <h2 style="font-size: 1.25rem; margin: 0;">Arborescence des liens</h2>
        </div>
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
                            <form action="/admin/menus/items/{{ $item->id }}" method="POST" class="actions-form" onsubmit="return confirm('Supprimer ce lien ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--muted-color); padding: 2rem;">Aucun élément dans ce menu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
