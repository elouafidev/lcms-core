@extends('core::admin.layouts.app')

@section('title', __('Nouvelle Redirection — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Nouvelle Redirection') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Redirigez automatiquement un ancien lien vers une nouvelle page ou URL externe') }}
            </p>
        </div>
        <div>
            <a href="{{ url('/admin/redirects') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> {{ __('Retour à la liste') }}
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger shadow-sm mb-4 alert-dismissible fade show">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <form action="{{ url('/admin/redirects') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('Chemin Source (URL d\'origine)') }} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted font-monospace">/</span>
                            <input type="text" name="source_path" value="{{ old('source_path', ltrim($prefilledSource, '/')) }}"
                                   class="form-control font-monospace" required placeholder="mon-ancien-article-blog">
                        </div>
                        <div class="form-text small">
                            {{ __('Le chemin relatif à intercepter (ex: "ancienne-page" ou "/blog/vieux-post"). Les barres obliques de début sont normalisées automatiquement.') }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('URL de Destination (Cible)') }} <span class="text-danger">*</span></label>
                        <input type="text" name="target_url" value="{{ old('target_url') }}"
                               class="form-control font-monospace" required placeholder="/nouvelle-page ou https://autre-site.com/page">
                        <div class="form-text small">
                            {{ __('Peut être un chemin interne du site (ex: "/nouveau-lien") ou une URL absolue externe commençant par "http://" ou "https://".') }}
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">{{ __('Code Statut HTTP') }} <span class="text-danger">*</span></label>
                            <select name="status_code" class="form-select font-monospace">
                                <option value="301" {{ old('status_code', '301') == '301' ? 'selected' : '' }}>301 — Redirection Permanente (Recommandé SEO)</option>
                                <option value="302" {{ old('status_code') == '302' ? 'selected' : '' }}>302 — Redirection Temporaire</option>
                                <option value="307" {{ old('status_code') == '307' ? 'selected' : '' }}>307 — Redirection Temporaire Stricte</option>
                                <option value="308" {{ old('status_code') == '308' ? 'selected' : '' }}>308 — Redirection Permanente Stricte</option>
                            </select>
                            <div class="form-text small">
                                {{ __('Le code 301 indique aux moteurs de recherche que la page a déménagé de manière définitive.') }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">{{ __('Description / Contexte') }}</label>
                            <input type="text" name="description" value="{{ old('description') }}" class="form-control" placeholder="Migration refonte v2...">
                            <div class="form-text small">
                                {{ __('Note interne pour votre équipe (optionnel).') }}
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold small" for="isActiveSwitch">
                            {{ __('Activer immédiatement cette règle de redirection') }}
                        </label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ url('/admin/redirects') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-4">{{ __('Annuler') }}</a>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold">{{ __('Créer la redirection') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
