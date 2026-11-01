@extends('core::admin.layouts.app')

@section('title', 'Administration — ' . __('Nouveau rôle'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Nouveau rôle') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Créez un nouveau rôle et assignez-lui les autorisations souhaitées.') }}
            </p>
        </div>
        <div>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> {{ __('Retour aux rôles') }}
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.roles.store') }}">
        @csrf

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
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Ex: Modérateur de contenu" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="slug" class="form-label fw-semibold small text-secondary">{{ __('Identifiant / Slug (Optionnel)') }}</label>
                        <input type="text" id="slug" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="Ex: moderator">
                        <div class="form-text small">{{ __('Laissez vide pour générer automatiquement depuis le nom.') }}</div>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold small text-secondary">{{ __('Description') }}</label>
                        <textarea id="description" name="description" class="form-control" rows="2" placeholder="Description courte des responsabilités attribuées...">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

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
                                            <input class="form-check-input perm-checkbox group-{{ $group }}" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm-{{ $permission->id }}">
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

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-light px-4">{{ __('Annuler') }}</a>
            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-save me-1"></i> {{ __('Créer le rôle') }}
            </button>
        </div>
    </form>

    <script>
        function toggleGroup(group) {
            const boxes = document.querySelectorAll('.group-' + group);
            const allChecked = Array.from(boxes).every(b => b.checked);
            boxes.forEach(b => b.checked = !allChecked);
        }

        document.getElementById('btn-toggle-all').addEventListener('click', function () {
            const allBoxes = document.querySelectorAll('.perm-checkbox');
            const allChecked = Array.from(allBoxes).every(b => b.checked);
            allBoxes.forEach(b => b.checked = !allChecked);
        });
    </script>
@endsection

