@extends('core::admin.layouts.app')

@section('title', 'Médiathèque — Administration')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/plugins/acme.media-library/css/media.css') }}">
    <style>
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem; }
        .media-card { background: white; border-radius: 0.5rem; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .media-preview { height: 140px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; }
        .media-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .media-body { padding: 0.75rem; font-size: 0.875rem; }
    </style>
@endpush

@section('content')
    <header class="admin-header">
        <div>
            <h1 class="admin-title">Médiathèque</h1>
            <p style="margin: 0.25rem 0 0 0; color: var(--muted-color); font-size: 0.9rem;">
                Stockage sécurisé, organisation et gestion des fichiers médias
            </p>
        </div>
    </header>

    @if($errors->any())
        <div class="alert alert-error">
            <ul style="margin: 0; padding-left: 1.25rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <h2 style="font-size: 1.25rem; margin-top: 0; margin-bottom: 1rem;">Téléverser un nouveau média</h2>
        <form action="/admin/media" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom: 1rem;">
                <input type="file" name="file" required style="display: block; margin-bottom: 0.5rem;">
                <span style="font-size: 0.8rem; color: var(--muted-color);">Formats acceptés : JPG, PNG, GIF, SVG, WebP, PDF. Max 10 Mo.</span>
            </div>
            <button type="submit" class="btn btn-primary">Envoyer le fichier</button>
        </form>
    </div>

    @if(empty($items))
        <div class="card" style="text-align: center; color: var(--muted-color); padding: 3rem;">
            <h3>Aucun média enregistré</h3>
            <p>Utilisez le formulaire ci-dessus pour téléverser votre premier document ou votre première image.</p>
        </div>
    @else
        <div class="media-grid">
            @foreach($items as $item)
                <div class="media-card">
                    <div class="media-preview">
                        @if($item->isImage())
                            <img src="{{ $item->url }}" alt="{{ $item->name }}">
                        @else
                            <span style="font-size: 2.5rem;">📄</span>
                        @endif
                    </div>
                    <div class="media-body">
                        <div style="font-weight: 600; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="{{ $item->name }}">
                            {{ $item->name }}
                        </div>
                        <div style="font-size: 0.75rem; color: var(--muted-color); margin-top: 0.25rem;">
                            {{ $item->formatted_size }} &bull; {{ $item->mime_type }}
                        </div>
                        <div style="margin-top: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                            <a href="{{ $item->url }}" target="_blank" style="color: var(--primary); font-size: 0.8rem; text-decoration: none;">Voir</a>
                            <form action="/admin/media/{{ $item->id }}" method="POST" onsubmit="return confirm('Supprimer ce média définitivement ?');" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="color: var(--danger); background: none; border: none; cursor: pointer; font-size: 0.8rem; padding: 0;">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
