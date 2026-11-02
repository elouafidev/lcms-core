@extends('core::admin.layouts.app')

@section('title', __('Gestion des Abonnés Newsletter — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Newsletter & Abonnés') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Gérez vos listes de diffusion et suivez vos inscriptions avec conformité RGPD') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ url('/admin/newsletter/export?status='.$status) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-download"></i> {{ __('Exporter CSV') }}
            </a>
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addSubscriberModal">
                <i class="bi bi-plus-lg"></i> {{ __('Ajouter un abonné') }}
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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

    <!-- KPI Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Inscrits') }}</span>
                    <h3 class="fw-bold mb-0 text-dark">{{ $totalCount }}</h3>
                </div>
                <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-envelope-paper fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Confirmés (Actifs)') }}</span>
                    <h3 class="fw-bold mb-0 text-success">{{ $confirmedCount }}</h3>
                </div>
                <div class="bg-success-subtle text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-check-circle fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('En attente (Opt-In)') }}</span>
                    <h3 class="fw-bold mb-0 text-warning">{{ $pendingCount }}</h3>
                </div>
                <div class="bg-warning-subtle text-warning rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-hourglass-split fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Taux Confirmation') }}</span>
                    <h3 class="fw-bold mb-0 text-info">{{ $confirmationRate }}%</h3>
                </div>
                <div class="bg-info-subtle text-info rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-percent fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <!-- Status Tabs -->
                <ul class="nav nav-pills small">
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-3 py-1 {{ $status === 'all' ? 'active' : '' }}" href="{{ url('/admin/newsletter?status=all'.($search ? '&search='.$search : '')) }}">
                            {{ __('Tous') }} ({{ $totalCount }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-3 py-1 {{ $status === 'confirmed' ? 'active' : '' }}" href="{{ url('/admin/newsletter?status=confirmed'.($search ? '&search='.$search : '')) }}">
                            {{ __('Confirmés') }} ({{ $confirmedCount }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-3 py-1 {{ $status === 'pending' ? 'active' : '' }}" href="{{ url('/admin/newsletter?status=pending'.($search ? '&search='.$search : '')) }}">
                            {{ __('En attente') }} ({{ $pendingCount }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-3 py-1 {{ $status === 'unsubscribed' ? 'active' : '' }}" href="{{ url('/admin/newsletter?status=unsubscribed'.($search ? '&search='.$search : '')) }}">
                            {{ __('Désabonnés') }} ({{ $unsubscribedCount }})
                        </a>
                    </li>
                </ul>

                <!-- Search form -->
                <form action="{{ url('/admin/newsletter') }}" method="GET" class="d-flex gap-2">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <div class="input-group input-group-sm" style="max-width: 280px;">
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="{{ __('Rechercher un email...') }}">
                        @if($search)
                            <a href="{{ url('/admin/newsletter?status='.$status) }}" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
                        @endif
                        <button class="btn btn-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Subscribers Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light fs-7 text-uppercase text-muted">
                    <tr>
                        <th style="width: 35%;">{{ __('Adresse E-mail') }}</th>
                        <th style="width: 15%;">{{ __('Statut') }}</th>
                        <th style="width: 15%;">{{ __('Source') }}</th>
                        <th style="width: 20%;">{{ __('Dates') }}</th>
                        <th style="width: 15%;" class="text-end pe-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $sub)
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark">{{ $sub->email }}</div>
                                @if($sub->ip_address)
                                    <div class="text-muted small font-monospace" style="font-size: 0.75rem;">IP: {{ $sub->ip_address }}</div>
                                @endif
                            </td>
                            <td>
                                @if($sub->isConfirmed())
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i> {{ __('Confirmé') }}
                                    </span>
                                @elseif($sub->isPending())
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1">
                                        <i class="bi bi-hourglass-split me-1"></i> {{ __('En attente') }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2 py-1">
                                        <i class="bi bi-dash-circle me-1"></i> {{ __('Désabonné') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border font-monospace">{{ $sub->source }}</span>
                            </td>
                            <td>
                                <div class="small text-muted">
                                    <span>{{ __('Inscrit le :') }} {{ $sub->created_at ? $sub->created_at->format('d/m/Y H:i') : '—' }}</span>
                                    @if($sub->confirmed_at)
                                        <br><span class="text-success">{{ __('Validé le :') }} {{ $sub->confirmed_at->format('d/m/Y H:i') }}</span>
                                    @endif
                                    @if($sub->unsubscribed_at)
                                        <br><span class="text-danger">{{ __('Désabonné :') }} {{ $sub->unsubscribed_at->format('d/m/Y H:i') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    @if($sub->isPending())
                                        <form action="{{ url('/admin/newsletter/subscribers/'.$sub->id.'/resend') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-warning" title="{{ __('Renvoyer l\'email de confirmation') }}">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ url('/admin/newsletter/subscribers/'.$sub->id.'/toggle') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary" title="{{ $sub->isConfirmed() ? __('Marquer comme désabonné') : __('Confirmer manuellement') }}">
                                            <i class="bi bi-power"></i>
                                        </button>
                                    </form>

                                    <form action="{{ url('/admin/newsletter/subscribers/'.$sub->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Supprimer définitivement cet abonné ?') }}');">
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
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-envelope-slash fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">{{ __('Aucun abonné trouvé dans cette liste.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subscribers->hasPages())
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                {{ $subscribers->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: Add Subscriber Manually -->
    <div class="modal fade" id="addSubscriberModal" tabindex="-1" aria-labelledby="addSubscriberModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <form action="{{ url('/admin/newsletter/subscribers') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold fs-6" id="addSubscriberModalLabel">
                            <i class="bi bi-person-plus me-1 text-primary"></i> {{ __('Ajouter un abonné') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">{{ __('Adresse E-mail') }} <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required placeholder="contact@example.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">{{ __('Statut initial') }} <span class="text-danger">*</span></label>
                            <select name="status" class="form-select">
                                <option value="confirmed" selected>{{ __('Confirmé directement (Opt-In validé)') }}</option>
                                <option value="pending">{{ __('En attente (Envoyer un e-mail de confirmation)') }}</option>
                            </select>
                            <div class="form-text small">
                                {{ __('En sélectionnant "Confirmé", l\'adresse est immédiatement active sans email de validation.') }}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">{{ __('Enregistrer') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
