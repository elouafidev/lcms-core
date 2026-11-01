@extends('core::admin.layouts.app')

@section('title', 'Administration — ' . __('Gestion des comptes'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Gestion des comptes utilisateurs') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Administrez les utilisateurs du CMS, leurs rôles d\'accès, l\'état de leur 2FA et leurs sessions actives.') }}
            </p>
        </div>
        <div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-person-plus"></i> {{ __('Nouvel utilisateur') }}
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

    <!-- Search & Filters -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Rechercher par nom ou email..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">{{ __('Tous les statuts') }}</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Actifs uniquement') }}</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactifs uniquement') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">{{ __('Filtrer') }}</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-light text-muted border w-100">{{ __('Réinitialiser') }}</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card shadow-sm border-0" style="border-radius: 1rem;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light fs-7 text-uppercase text-secondary">
                        <tr>
                            <th class="ps-4">{{ __('Utilisateur') }}</th>
                            <th>{{ __('Rôles') }}</th>
                            <th>{{ __('2FA (TOTP)') }}</th>
                            <th>{{ __('Statut') }}</th>
                            <th>{{ __('Dernière connexion') }}</th>
                            <th class="pe-4 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        @forelse($users as $user)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 2.2rem; height: 2.2rem; font-size: 0.9rem;">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $user->name }}</div>
                                            <div class="small text-muted">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse($user->roles as $role)
                                            <span class="badge {{ $role->slug === 'super_admin' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle' }} fs-8">
                                                {{ $role->name }}
                                            </span>
                                        @empty
                                            <span class="text-muted small">Aucun rôle</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    @if($user->hasTwoFactorEnabled())
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fs-8">
                                            <i class="bi bi-shield-check me-1"></i> Activée
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border fs-8">
                                            <i class="bi bi-shield-x me-1"></i> Inactive
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->is_active)
                                        <span class="badge bg-success text-white fs-8">Actif</span>
                                    @else
                                        <span class="badge bg-danger text-white fs-8">Désactivé</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->last_login_at)
                                        <div class="text-dark">{{ $user->last_login_at->diffForHumans() }}</div>
                                        <div class="fs-8 text-muted font-monospace">{{ $user->last_login_ip ?? '127.0.0.1' }}</div>
                                    @else
                                        <span class="text-muted">Jamais connecté</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            {{ __('Actions') }}
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item py-2" href="{{ route('admin.users.edit', $user) }}">
                                                    <i class="bi bi-pencil text-primary me-2"></i> {{ __('Modifier') }}
                                                </a>
                                            </li>
                                            @if($user->hasTwoFactorEnabled())
                                                <li>
                                                    <form method="POST" action="{{ route('admin.users.reset-2fa', $user) }}" onsubmit="return confirm('Réinitialiser la 2FA pour ce compte ?');">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item py-2 text-warning">
                                                            <i class="bi bi-shield-slash me-2"></i> {{ __('Réinitialiser 2FA') }}
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                            <li>
                                                <form method="POST" action="{{ route('admin.users.revoke-sessions', $user) }}" onsubmit="return confirm('Révoquer toutes les sessions de cet utilisateur ?');">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item py-2 text-secondary">
                                                        <i class="bi bi-box-arrow-right me-2"></i> {{ __('Déconnecter sessions') }}
                                                    </button>
                                                </form>
                                            </li>
                                            @if($user->id !== auth()->id())
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item py-2 {{ $user->is_active ? 'text-danger' : 'text-success' }}">
                                                            <i class="bi {{ $user->is_active ? 'bi-person-slash' : 'bi-person-check' }} me-2"></i>
                                                            {{ $user->is_active ? __('Désactiver le compte') : __('Activer le compte') }}
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-2 d-block mb-2"></i>
                                    {{ __('Aucun utilisateur trouvé.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="p-3 border-top d-flex justify-content-end">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

