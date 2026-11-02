@extends('core::admin.layouts.app')

@section('title', __('Gestion des Commandes — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Boutique & Commandes') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Suivez vos ventes, gérez vos commandes et consultez vos performances commerciales') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ url('/shop') }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-box-arrow-up-right"></i> {{ __('Voir la boutique') }}
            </a>
            <a href="{{ url('/admin/commerce/products') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-boxes"></i> {{ __('Gérer le catalogue') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- KPI Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Chiffre d\'Affaires') }}</span>
                    <h3 class="fw-bold mb-0 text-success">{{ \Plugins\CommerceLite\Models\Product::formatPrice($totalRevenue) }}</h3>
                </div>
                <div class="bg-success-subtle text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-currency-euro fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Commandes') }}</span>
                    <h3 class="fw-bold mb-0 text-dark">{{ $totalOrders }}</h3>
                </div>
                <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-bag-check fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Commandes Payées') }}</span>
                    <h3 class="fw-bold mb-0 text-info">{{ $paidOrdersCount }}</h3>
                </div>
                <div class="bg-info-subtle text-info rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-credit-card fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Panier Moyen') }}</span>
                    <h3 class="fw-bold mb-0 text-primary">{{ \Plugins\CommerceLite\Models\Product::formatPrice($averageBasket) }}</h3>
                </div>
                <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-calculator fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <ul class="nav nav-pills small">
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-3 py-1 {{ $status === 'all' ? 'active' : '' }}" href="{{ url('/admin/commerce/orders?status=all'.($search ? '&search='.$search : '')) }}">
                            {{ __('Toutes') }} ({{ $totalOrders }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-3 py-1 {{ $status === 'paid' ? 'active' : '' }}" href="{{ url('/admin/commerce/orders?status=paid'.($search ? '&search='.$search : '')) }}">
                            {{ __('Payées') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-3 py-1 {{ $status === 'pending' ? 'active' : '' }}" href="{{ url('/admin/commerce/orders?status=pending'.($search ? '&search='.$search : '')) }}">
                            {{ __('En attente') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-3 py-1 {{ $status === 'shipped' ? 'active' : '' }}" href="{{ url('/admin/commerce/orders?status=shipped'.($search ? '&search='.$search : '')) }}">
                            {{ __('Expédiées') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-3 py-1 {{ $status === 'cancelled' ? 'active' : '' }}" href="{{ url('/admin/commerce/orders?status=cancelled'.($search ? '&search='.$search : '')) }}">
                            {{ __('Annulées') }}
                        </a>
                    </li>
                </ul>

                <form action="{{ url('/admin/commerce/orders') }}" method="GET" class="d-flex gap-2">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <div class="input-group input-group-sm" style="max-width: 280px;">
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="{{ __('Rechercher commande, client...') }}">
                        @if($search)
                            <a href="{{ url('/admin/commerce/orders?status='.$status) }}" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
                        @endif
                        <button class="btn btn-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light fs-7 text-uppercase text-muted">
                    <tr>
                        <th style="width: 20%;">{{ __('N° Commande') }}</th>
                        <th style="width: 25%;">{{ __('Client') }}</th>
                        <th style="width: 15%;">{{ __('Montant Total') }}</th>
                        <th style="width: 15%;">{{ __('Statut') }}</th>
                        <th style="width: 15%;">{{ __('Date') }}</th>
                        <th style="width: 10%;" class="text-end pe-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $ord)
                        <tr>
                            <td>
                                <a href="{{ url('/admin/commerce/orders/'.$ord->id) }}" class="font-monospace fw-bold text-primary text-decoration-none">
                                    {{ $ord->order_number }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $ord->customer_name }}</div>
                                <div class="small text-muted">{{ $ord->customer_email }}</div>
                            </td>
                            <td>
                                <span class="font-monospace fw-bold text-dark fs-6">{{ $ord->formattedTotal() }}</span>
                            </td>
                            <td>
                                @if($ord->status === 'paid')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i> {{ __('Payée') }}
                                    </span>
                                @elseif($ord->status === 'shipped')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2 py-1">
                                        <i class="bi bi-truck me-1"></i> {{ __('Expédiée') }}
                                    </span>
                                @elseif($ord->status === 'pending')
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1">
                                        <i class="bi bi-hourglass-split me-1"></i> {{ __('En attente') }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2 py-1">
                                        <i class="bi bi-x-circle me-1"></i> {{ __('Annulée') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="small text-muted">{{ $ord->created_at ? $ord->created_at->format('d/m/Y H:i') : '—' }}</div>
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ url('/admin/commerce/orders/'.$ord->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    {{ __('Détails') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-bag-x fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">{{ __('Aucune commande trouvée.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
@endsection
