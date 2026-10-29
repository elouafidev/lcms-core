@extends('core::admin.layouts.app')

@section('title', 'Administration — ' . __('Nouvel utilisateur'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Nouvel utilisateur') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Créez un nouveau compte administrateur ou collaborateur pour la plateforme.') }}
            </p>
        </div>
        <div>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> {{ __('Retour aux utilisateurs') }}
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title fw-bold mb-0 text-dark">
                    <i class="bi bi-person-circle text-primary me-2"></i> {{ __('Identifiants du compte') }}
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-semibold small text-secondary">{{ __('Nom complet') }}</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold small text-secondary">{{ __('Adresse e-mail') }}</label>
                        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label fw-semibold small text-secondary">{{ __('Mot de passe') }}</label>
                        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                        <div class="form-text small">{{ __('Minimum 8 caractères.') }}</div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label fw-semibold small text-secondary">{{ __('Confirmer le mot de passe') }}</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                    </div>

                    <div class="col-12 mt-3">
                        <div class="form-check form-switch p-0 d-flex align-items-center gap-3">
                            <input class="form-check-input ms-0 mt-0" type="checkbox" role="switch" id="is_active" name="is_active" value="1" checked style="width: 2.5rem; height: 1.3rem;">
                            <label class="form-check-label fw-semibold text-dark" for="is_active">
                                {{ __('Compte actif') }}
                                <span class="d-block small text-muted fw-normal">{{ __('L\'utilisateur peut immédiatement s\'authentifier sur le CMS.') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Assignment Card -->
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title fw-bold mb-0 text-dark">
                    <i class="bi bi-shield-check text-primary me-2"></i> {{ __('Rôles attribués') }}
                </h5>
            </div>
            <div class="card-body p-4">
                <p class="small text-muted mb-3">{{ __('Sélectionnez un ou plusieurs rôles pour ce compte :') }}</p>
                <div class="row g-3">
                    @foreach($roles as $role)
                        <div class="col-md-6 col-lg-4">
                            <div class="border rounded p-3 bg-light">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role-{{ $role->id }}" {{ $role->slug === 'user' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold text-dark fs-7" for="role-{{ $role->id }}">
                                        {{ $role->name }}
                                        <span class="d-block text-muted fs-8 fw-normal">{{ $role->description ?: $role->slug }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.users.index') }}" class="btn btn-light px-4">{{ __('Annuler') }}</a>
            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-save me-1"></i> {{ __('Créer l\'utilisateur') }}
            </button>
        </div>
    </form>
@endsection

