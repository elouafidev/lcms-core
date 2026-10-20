@extends('core::admin.layouts.app')

@section('title', 'Médiathèque — Administration')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/plugins/acme.media-library/css/media.css') }}">
    <style>
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.25rem; }
        .media-card { background: white; border-radius: 0.375rem; border: 1px solid var(--bs-border-color, #dee2e6); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .media-preview { height: 140px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-bottom: 1px solid var(--bs-border-color, #dee2e6); }
        .media-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .media-body { padding: 0.75rem; font-size: 0.875rem; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">Médiathèque</h1>
            <p class="text-muted small mb-0">
                Stockage sécurisé, organisation et gestion des fichiers médias
            </p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-error shadow-sm mb-4">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Erreurs :</h6>
            <ul class="mb-0 ps-3 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Upload Card -->
    <div class="card card-outline card-primary shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title fw-bold fs-6 mb-0 text-primary">
                <i class="bi bi-cloud-arrow-up me-1"></i> Téléverser un nouveau média
            </h5>
        </div>
        <div class="card-body">
            <form action="/admin/media" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end g-3">
                    <div class="col-md-8 col-lg-6">
                        <label for="mediaFile" class="form-label fw-semibold small">Sélectionner un fichier</label>
                        <input type="file" id="mediaFile" name="file" required class="form-control form-control-sm">
                        <div class="form-text small text-muted">Formats acceptés : JPG, PNG, GIF, SVG, WebP, PDF. Max 10 Mo.</div>
                    </div>
                    <div class="col-md-4 col-lg-6">
                        <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <i class="bi bi-upload"></i> Envoyer le fichier
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(empty($items))
        <div class="card card-outline card-secondary shadow-sm text-center text-muted py-5">
            <i class="bi bi-images fs-1 d-block mb-2 text-muted"></i>
            <h4>Aucun média enregistré</h4>
            <p class="small mb-0">Utilisez le formulaire ci-dessus pour téléverser votre premier document ou votre première image.</p>
        </div>
    @else
        <div class="media-grid">
            @foreach($items as $item)
                <div class="media-card">
                    <div class="media-preview">
                        @if($item->isImage())
                            <img src="{{ $item->url }}" alt="{{ $item->name }}">
                        @else
                            <i class="bi bi-file-earmark-text text-secondary fs-1"></i>
                        @endif
                    </div>
                    <div class="media-body">
                        <div class="fw-bold text-truncate" title="{{ $item->name }}">
                            {{ $item->name }}
                        </div>
                        <div class="small text-muted mt-1">
                            {{ $item->formatted_size }} &bull; {{ $item->mime_type }}
                        </div>
                        <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                            <a href="{{ $item->url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                                <i class="bi bi-eye"></i> Voir
                            </a>
                            <form action="/admin/media/{{ $item->id }}" method="POST" onsubmit="return confirm('Supprimer ce média définitivement ?');" class="m-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;">
                                    <i class="bi bi-trash"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
