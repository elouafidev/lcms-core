@extends('core::admin.layouts.app')

@section('title', __('Modifier la Redirection — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Modifier la Redirection') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Modifiez les paramètres de routage pour') }} <code class="text-primary">{{ $rule->source_path }}</code>
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
                <form action="{{ url('/admin/redirects/'.$rule->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('Chemin Source (URL d\'origine)') }} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted font-monospace">/</span>
                            <input type="text" name="source_path" value="{{ old('source_path', ltrim($rule->source_path, '/')) }}"
                                   class="form-control font-monospace" required placeholder="mon-ancien-article-blog">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('URL de Destination (Cible)') }} <span class="text-danger">*</span></label>
                        <input type="text" name="target_url" value="{{ old('target_url', $rule->target_url) }}"
                               class="form-control font-monospace" required placeholder="/nouvelle-page ou https://autre-site.com/page">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">{{ __('Code Statut HTTP') }} <span class="text-danger">*</span></label>
                            <select name="status_code" class="form-select font-monospace">
                                <option value="301" {{ old('status_code', (string) $rule->status_code) == '301' ? 'selected' : '' }}>301 — Redirection Permanente</option>
                                <option value="302" {{ old('status_code', (string) $rule->status_code) == '302' ? 'selected' : '' }}>302 — Redirection Temporaire</option>
                                <option value="307" {{ old('status_code', (string) $rule->status_code) == '307' ? 'selected' : '' }}>307 — Redirection Temporaire Stricte</option>
                                <option value="308" {{ old('status_code', (string) $rule->status_code) == '308' ? 'selected' : '' }}>308 — Redirection Permanente Stricte</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">{{ __('Description / Contexte') }}</label>
                            <input type="text" name="description" value="{{ old('description', $rule->description) }}" class="form-control" placeholder="Migration refonte v2...">
                        </div>
                    </div>

                    <div class="mb-4 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', $rule->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold small" for="isActiveSwitch">
                            {{ __('Règle active') }}
                        </label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ url('/admin/redirects') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-4">{{ __('Annuler') }}</a>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold">{{ __('Mettre à jour') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
