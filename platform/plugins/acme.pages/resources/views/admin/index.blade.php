@extends('core::admin.layouts.app')

@section('title', 'Gestion des Pages — Administration')

@section('content')
    <header class="admin-header">
        <div>
            <h1 class="admin-title">Pages Statiques</h1>
            <p style="margin: 0.25rem 0 0 0; color: var(--muted-color); font-size: 0.9rem;">
                Création et gestion des pages de contenu publiables
            </p>
        </div>
    </header>

    <div class="card">
        <h2 style="font-size: 1.25rem; margin-top: 0; margin-bottom: 1rem;">Créer une nouvelle page</h2>
        <form action="/admin/pages" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label for="title" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Titre :</label>
                <input type="text" id="title" name="title" required style="width: 100%; max-width: 600px; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label for="slug" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Slug (optionnel) :</label>
                <input type="text" id="slug" name="slug" placeholder="ex: a-propos" style="width: 100%; max-width: 600px; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label for="content" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Contenu :</label>
                <textarea id="content" name="content" rows="5" style="width: 100%; max-width: 600px; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-sizing: border-box;"></textarea>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_published" value="1">
                    <span>Publier immédiatement</span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Enregistrer la page</button>
        </form>
    </div>

    <div class="card" style="padding: 0;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color);">
            <h2 style="font-size: 1.25rem; margin: 0;">Pages existantes</h2>
        </div>
        <table class="table">
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
                                <a href="/page/{{ $page->slug }}" target="_blank" style="color: var(--primary); text-decoration: none;">/page/{{ $page->slug }}</a>
                            @else
                                <span style="color: var(--muted-color);">/page/{{ $page->slug }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $page->is_published ? 'badge-active' : 'badge-disabled' }}">
                                {{ $page->is_published ? 'Publié' : 'Brouillon' }}
                            </span>
                        </td>
                        <td>
                            <form action="/admin/pages/{{ $page->id }}" method="POST" class="actions-form" onsubmit="return confirm('Supprimer cette page ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--muted-color); padding: 2rem;">Aucune page créée pour le moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
