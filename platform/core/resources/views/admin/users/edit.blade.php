@extends('core::admin.layouts.app')

@section('title', 'Administration — ' . __('Modifier le compte') . ' : ' . $user->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Modifier le compte') }} : {{ $user->name }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Modifiez les informations, réinitialisez le mot de passe ou ajustez les rôles de cet utilisateur.') }}
            </p>
        </div>
        <div>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> {{ __('Retour aux utilisateurs') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-8">
                <!-- User Profile Info -->
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title fw-bold mb-0 text-dark">
                            <i class="bi bi-person-circle text-primary me-2"></i> {{ __('Informations du compte') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold small text-secondary">{{ __('Nom complet') }}</label>
                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold small text-secondary">{{ __('Adresse e-mail') }}</label>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold small text-secondary">{{ __('Nouveau mot de passe (Optionnel)') }}</label>
                                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Laissez vide pour conserver l'actuel">
                                <div class="form-text small">{{ __('Minimum 8 caractères si modifié.') }}</div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label fw-semibold small text-secondary">{{ __('Confirmer le nouveau mot de passe') }}</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
                            </div>

                            <div class="col-12 mt-3">
                                <div class="form-check form-switch p-0 d-flex align-items-center gap-3">
                                    <input class="form-check-input ms-0 mt-0" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }} style="width: 2.5rem; height: 1.3rem;">
                                    <label class="form-check-label fw-semibold text-dark" for="is_active">
                                        {{ __('Compte actif') }}
                                        <span class="d-block small text-muted fw-normal">{{ __('Désactiver ce compte bloque immédiatement ses connexions et révoque ses sessions.') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Roles Assignment -->
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title fw-bold mb-0 text-dark">
                            <i class="bi bi-shield-check text-primary me-2"></i> {{ __('Rôles attribués') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        @php
                            $userRoleIds = $user->roles->pluck('id')->all();
                        @endphp
                        <div class="row g-3">
                            @foreach($roles as $role)
                                <div class="col-md-6">
                                    <div class="border rounded p-3 bg-light">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role-{{ $role->id }}" {{ in_array($role->id, $userRoleIds, true) ? 'checked' : '' }}>
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
            </div>

            <!-- Right Column: Security Overview -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title fw-bold mb-0 text-dark">
                            <i class="bi bi-shield-lock text-primary me-2"></i> {{ __('État de sécurité') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <span class="text-muted small d-block">{{ __('Authentification 2FA') }}</span>
                            @if($user->hasTwoFactorEnabled())
                                <span class="badge bg-success-subtle text-success border border-success-subtle fs-7">
                                    <i class="bi bi-shield-check me-1"></i> Activée
                                </span>
                            @else
                                <span class="badge bg-light text-muted border fs-7">
                                    <i class="bi bi-shield-x me-1"></i> Non configurée
                                </span>
                            @endif
                        </div>

                        <div class="mb-3">
                            <span class="text-muted small d-block">{{ __('Dernier changement de mot de passe') }}</span>
                            <span class="small text-dark">{{ $user->last_password_change_at ? $user->last_password_change_at->diffForHumans() : 'Non enregistré' }}</span>
                        </div>

                        <div class="mb-3">
                            <span class="text-muted small d-block">{{ __('Dernière connexion enregistrée') }}</span>
                            <span class="small text-dark">{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') . ' (' . ($user->last_login_ip ?? '127.0.0.1') . ')' : 'Jamais' }}</span>
                        </div>

                        <div class="border-top pt-3 mt-3">
                            <span class="text-muted small d-block mb-1">{{ __('Date de création du compte') }}</span>
                            <span class="small text-muted">{{ $user->created_at?->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.users.index') }}" class="btn btn-light px-4">{{ __('Annuler') }}</a>
            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-save me-1"></i> {{ __('Mettre à jour le compte') }}
            </button>
        </div>
    </form>
@endsection

