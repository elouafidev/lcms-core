@extends('core::admin.layouts.app')

@section('title', 'Menus de Navigation — Administration')

@section('content')
    <header class="admin-header">
        <div>
            <h1 class="admin-title">Menus de Navigation</h1>
            <p style="margin: 0.25rem 0 0 0; color: var(--muted-color); font-size: 0.9rem;">
                Configuration des menus arborescents et des liens de navigation
            </p>
        </div>
    </header>

    <div class="card">
        <h2 style="font-size: 1.25rem; margin-top: 0; margin-bottom: 1rem;">Créer un nouveau menu</h2>
        <form action="/admin/menus" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label for="name" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Nom du menu</label>
                <input type="text" id="name" name="name" required placeholder="ex: Menu Principal" style="width: 100%; max-width: 500px; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label for="slug" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Identifiant (slug)</label>
                <input type="text" id="slug" name="slug" required placeholder="ex: main-nav" style="width: 100%; max-width: 500px; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label for="description" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Description</label>
                <textarea id="description" name="description" rows="2" placeholder="Description optionnelle" style="width: 100%; max-width: 500px; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-sizing: border-box;"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Créer le menu</button>
        </form>
    </div>

    <div class="card" style="padding: 0;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color);">
            <h2 style="font-size: 1.25rem; margin: 0;">Menus existants</h2>
        </div>
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
                        <td>
                            <span class="badge {{ $menu->is_active ? 'badge-active' : 'badge-disabled' }}">
                                {{ $menu->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td>
                            <a href="/admin/menus/{{ $menu->id }}" class="btn btn-secondary">Gérer</a>
                            <form action="/admin/menus/{{ $menu->id }}" method="POST" class="actions-form" onsubmit="return confirm('Confirmer la suppression ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--muted-color); padding: 2rem;">Aucun menu de navigation configuré pour le moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
