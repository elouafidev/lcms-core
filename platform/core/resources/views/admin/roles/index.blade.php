@extends('core::admin.layouts.app')

@section('title', 'Administration — ' . __('Gestion des rôles'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Gestion des rôles') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Définissez les niveaux d\'accès et les permissions attribuées aux utilisateurs.') }}
            </p>
        </div>
        <div>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-shield-plus"></i> {{ __('Nouveau rôle') }}
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

    <div class="card shadow-sm border-0" style="border-radius: 1rem;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light fs-7 text-uppercase text-secondary">
                        <tr>
                            <th class="ps-4">{{ __('Rôle') }}</th>
                            <th>{{ __('Identifiant (Slug)') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Permissions') }}</th>
                            <th>{{ __('Comptes liés') }}</th>
                            <th class="pe-4 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        @foreach($roles as $role)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                        <i class="bi bi-shield-lock text-primary"></i> {{ $role->name }}
                                    </div>
                                </td>
                                <td>
                                    <code>{{ $role->slug }}</code>
                                </td>
                                <td class="text-muted">
                                    {{ $role->description ?: '—' }}
                                </td>
                                <td>
                                    @if($role->is_system)
                                        <span class="badge bg-secondary-subtle text-secondary border fs-8">
                                            <i class="bi bi-lock me-1"></i> {{ __('Système') }}
                                        </span>
                                    @else
                                        <span class="badge bg-info-subtle text-info border fs-8">
                                            {{ __('Personnalisé') }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($role->slug === 'super_admin')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-star-fill me-1"></i> {{ __('Toutes (Accès total)') }}
                                        </span>
                                    @else
                                        <span class="badge bg-light text-dark border">
                                            {{ $role->permissions->count() }} {{ __('permission(s)') }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        {{ $role->users->count() }} {{ __('utilisateur(s)') }}
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Modifier') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        @if(! $role->is_system)
                                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Êtes-vous certain de vouloir supprimer ce rôle ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Supprimer') }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-light text-muted border-0" disabled title="{{ __('Les rôles système ne peuvent pas être supprimés.') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

