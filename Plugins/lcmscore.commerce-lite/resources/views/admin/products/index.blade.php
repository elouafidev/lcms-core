@extends('core::admin.layouts.app')

@section('title', __('Catalogue Produits — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Catalogue Produits') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Gérez les articles vendus sur la boutique, leurs prix et leurs stocks') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ url('/admin/commerce/orders') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> {{ __('Commandes') }}
            </a>
            <a href="{{ url('/admin/commerce/products/create') }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-plus-lg"></i> {{ __('Nouveau Produit') }}
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
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Produits') }}</span>
                    <h3 class="fw-bold mb-0 text-dark">{{ $totalProducts }}</h3>
                </div>
                <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-boxes fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('En Stock') }}</span>
                    <h3 class="fw-bold mb-0 text-success">{{ $inStockCount }}</h3>
                </div>
                <div class="bg-success-subtle text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-check-circle fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Rupture de Stock') }}</span>
                    <h3 class="fw-bold mb-0 {{ $outOfStockCount > 0 ? 'text-danger' : 'text-dark' }}">{{ $outOfStockCount }}</h3>
                </div>
                <div class="bg-danger-subtle text-danger rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-exclamation-triangle fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3">
            <div class="d-flex justify-content-end">
                <form action="{{ url('/admin/commerce/products') }}" method="GET" class="d-flex gap-2">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm rounded-pill px-3" placeholder="{{ __('Rechercher nom, référence SKU...') }}" style="width: 280px;">
                    @if($search)
                        <a href="{{ url('/admin/commerce/products') }}" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-x"></i></a>
                    @endif
                    <button class="btn btn-sm btn-secondary rounded-pill px-3" type="submit"><i class="bi bi-search"></i></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light fs-7 text-uppercase text-muted">
                    <tr>
                        <th style="width: 35%;">{{ __('Produit') }}</th>
                        <th style="width: 15%;">{{ __('Référence (SKU)') }}</th>
                        <th style="width: 15%;">{{ __('Prix Unitaire') }}</th>
                        <th style="width: 15%;">{{ __('Stock') }}</th>
                        <th style="width: 10%;">{{ __('Statut') }}</th>
                        <th style="width: 10%;" class="text-end pe-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $prod)
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark">{{ $prod->name }}</div>
                                <div class="small text-muted font-monospace">/shop/{{ $prod->slug }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border font-monospace">{{ $prod->sku }}</span>
                            </td>
                            <td>
                                <span class="fw-bold text-primary font-monospace">{{ $prod->formattedPrice() }}</span>
                            </td>
                            <td>
                                @if($prod->inStock())
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace">
                                        {{ $prod->stock }} unités
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">
                                        {{ __('Rupture') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($prod->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                        {{ __('Actif') }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2 py-1">
                                        {{ __('Inactif') }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <form action="{{ url('/admin/commerce/products/'.$prod->id.'/toggle') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary" title="{{ $prod->is_active ? __('Désactiver') : __('Activer') }}">
                                            <i class="bi bi-power"></i>
                                        </button>
                                    </form>
                                    <a href="{{ url('/admin/commerce/products/'.$prod->id.'/edit') }}" class="btn btn-outline-primary" title="{{ __('Modifier') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ url('/admin/commerce/products/'.$prod->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Supprimer définitivement ce produit ?') }}');">
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
                                <i class="bi bi-boxes fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">{{ __('Aucun produit dans le catalogue.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                {{ $products->links() }}
            </div>
        @endif
    </div>
@endsection
