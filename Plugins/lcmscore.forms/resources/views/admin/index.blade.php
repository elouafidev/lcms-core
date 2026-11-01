@extends('core::admin.layouts.app')

@section('title', __('Gestion des Formulaires — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Formulaires') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Création et gestion des formulaires dynamiques de contact, devis et capture') }}
            </p>
        </div>
        <div>
            <a href="/admin/forms/create" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-plus-lg"></i> {{ __('Créer un formulaire') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form action="/admin/forms" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control border-start-0"
                               placeholder="{{ __('Rechercher par nom ou slug...') }}">
                        @if(!empty($search))
                            <a href="/admin/forms" class="btn btn-outline-secondary" title="{{ __('Réinitialiser') }}">
                                <i class="bi bi-x"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Forms List Table -->
    <div class="card card-outline card-secondary shadow-sm rounded-4 border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="card-title fw-bold fs-6 mb-0 text-secondary">
                <i class="bi bi-input-cursor-text me-1"></i> {{ __('Formulaires enregistrés') }}
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light fs-7 text-uppercase text-muted">
                        <tr>
                            <th style="width: 25%;">{{ __('Nom du formulaire') }}</th>
                            <th style="width: 25%;">{{ __('Intégration Blade') }}</th>
                            <th style="width: 15%;">{{ __('Champs') }}</th>
                            <th style="width: 15%;">{{ __('Soumissions') }}</th>
                            <th style="width: 10%;">{{ __('Statut') }}</th>
                            <th style="width: 10%;" class="text-end pe-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($forms as $form)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <a href="/admin/forms/{{ $form->id }}/edit" class="text-dark text-decoration-none hover-primary">
                                            {{ $form->name }}
                                        </a>
                                    </div>
                                    @if($form->recipient_email)
                                        <div class="small text-muted">
                                            <i class="bi bi-envelope me-1"></i>{{ $form->recipient_email }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="input-group input-group-sm" style="max-width: 260px;">
                                        <input type="text" class="form-control font-monospace" value="@@renderForm('{{ $form->slug }}')" readonly style="font-size: 0.75rem;">
                                        <button class="btn btn-outline-secondary btn-copy" type="button" data-clipboard="@@renderForm('{{ $form->slug }}')" title="{{ __('Copier le code') }}">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        {{ count($form->fields) }} {{ __('champs') }}
                                    </span>
                                </td>
                                <td>
                                    <a href="/admin/forms/{{ $form->id }}/submissions" class="btn btn-sm btn-light border py-1 px-2 text-decoration-none">
                                        <i class="bi bi-inbox me-1"></i>
                                        <strong>{{ $form->submissions_count }}</strong>
                                        @if($form->unread_submissions_count > 0)
                                            <span class="badge bg-danger rounded-pill ms-1">{{ $form->unread_submissions_count }} {{ __('non lues') }}</span>
                                        @endif
                                    </a>
                                </td>
                                <td>
                                    @if($form->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            {{ __('Actif') }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                            {{ __('Désactivé') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/forms/{{ $form->id }}/submissions" class="btn btn-outline-secondary" title="{{ __('Boîte de réception') }}">
                                            <i class="bi bi-inbox"></i>
                                        </a>
                                        <a href="/admin/forms/{{ $form->id }}/submissions/export" class="btn btn-outline-secondary" title="{{ __('Exporter CSV') }}">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <a href="/admin/forms/{{ $form->id }}/edit" class="btn btn-outline-primary" title="{{ __('Modifier') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="/admin/forms/{{ $form->id }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Supprimer définitivement ce formulaire et toutes ses soumissions ?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="{{ __('Supprimer') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-input-cursor-text fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                    <h5>{{ __('Aucun formulaire enregistré') }}</h5>
                                    <p class="small mb-3">{{ __('Créez votre premier formulaire de contact ou devis dès maintenant.') }}</p>
                                    <a href="/admin/forms/create" class="btn btn-primary btn-sm rounded-pill px-3">
                                        <i class="bi bi-plus-lg me-1"></i> {{ __('Créer un formulaire') }}
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.btn-copy').forEach(btn => {
    btn.addEventListener('click', function() {
        const text = this.getAttribute('data-clipboard');
        navigator.clipboard.writeText(text).then(() => {
            const icon = this.querySelector('i');
            icon.className = 'bi bi-check text-success';
            setTimeout(() => icon.className = 'bi bi-clipboard', 1500);
        });
    });
});
</script>
@endpush
