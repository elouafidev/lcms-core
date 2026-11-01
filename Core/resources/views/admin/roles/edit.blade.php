@extends('core::admin.layouts.app')

@section('title', 'Administration — ' . __('Modifier le rôle') . ' : ' . $role->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Modifier le rôle') }} : {{ $role->name }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Modifiez le nom, la description ou les permissions associées à ce rôle.') }}
            </p>
        </div>
        <div>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> {{ __('Retour aux rôles') }}
            </a>
        </div>
    </div>

    @if($role->is_system)
        <div class="alert alert-info shadow-sm mb-4">
            <i class="bi bi-info-circle-fill me-2"></i>
            {{ __('Ce rôle est un rôle système LCMS Core. Son identifiant (slug) ne peut pas être modifié.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.roles.update', $role) }}">
        @csrf
        @method('PUT')

        <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title fw-bold mb-0 text-dark">
                    <i class="bi bi-info-circle text-primary me-2"></i> {{ __('Informations du rôle') }}
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-semibold small text-secondary">{{ __('Nom du rôle') }}</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $role->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-secondary">{{ __('Identifiant / Slug') }}</label>
                        <input type="text" class="form-control" value="{{ $role->slug }}" disabled>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold small text-secondary">{{ __('Description') }}</label>
                        <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $role->description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        @if($role->slug === 'super_admin')
            <div class="alert alert-warning mb-4 shadow-sm">
                <i class="bi bi-shield-lock-fill me-2"></i>
                <strong>{{ __('Super Administrateur :') }}</strong> {{ __('Ce rôle possède implicitement l\'intégralité des permissions présentes et futures du système.') }}
            </div>
        @else
            <!-- Permissions Matrix Card -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-key text-primary me-2"></i> {{ __('Permissions attribuées') }}
                    </h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-toggle-all">
                        {{ __('Tout sélectionner / Tout désélectionner') }}
                    </button>
                </div>
                <div class="card-body p-4">
                    @php
                        $rolePermissionIds = $role->permissions->pluck('id')->all();
                    @endphp
                    <div class="row g-4">
                        @foreach($permissionsGrouped as $group => $permissions)
                            <div class="col-md-6 col-xl-4">
                                <div class="border rounded p-3 h-100 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <h6 class="fw-bold text-uppercase fs-7 text-primary mb-0">
                                            <i class="bi bi-folder2 me-1"></i> {{ strtoupper($group) }}
                                        </h6>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fs-8" onclick="toggleGroup('{{ $group }}')">
                                            {{ __('Basculer') }}
                                        </button>
                                    </div>
                                    <div class="d-flex flex-column gap-2 mt-2">
                                        @foreach($permissions as $permission)
                                            <div class="form-check">
                                                <input class="form-check-input perm-checkbox group-{{ $group }}" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm-{{ $permission->id }}" {{ in_array($permission->id, $rolePermissionIds, true) ? 'checked' : '' }}>
                                                <label class="form-check-label fs-7 fw-semibold text-dark" for="perm-{{ $permission->id }}">
                                                    {{ $permission->name }}
                                                    <span class="d-block text-muted fs-8 fw-normal">{{ $permission->description ?: $permission->slug }}</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-light px-4">{{ __('Annuler') }}</a>
            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-save me-1"></i> {{ __('Mettre à jour le rôle') }}
            </button>
        </div>
    </form>

    <script>
        function toggleGroup(group) {
            const boxes = document.querySelectorAll('.group-' + group);
            const allChecked = Array.from(boxes).every(b => b.checked);
            boxes.forEach(b => b.checked = !allChecked);
        }

        const btnToggleAll = document.getElementById('btn-toggle-all');
        if (btnToggleAll) {
            btnToggleAll.addEventListener('click', function () {
                const allBoxes = document.querySelectorAll('.perm-checkbox');
                const allChecked = Array.from(allBoxes).every(b => b.checked);
                allBoxes.forEach(b => b.checked = !allChecked);
            });
        }
    </script>
@endsection

