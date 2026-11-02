@extends('core::admin.layouts.app')

@section('title', __('Commande :order — Administration', ['order' => $order->order_number]))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">
                {{ __('Commande') }} <span class="font-monospace text-primary">{{ $order->order_number }}</span>
            </h1>
            <p class="text-muted small mb-0">
                {{ __('Passée le') }} {{ $order->created_at ? $order->created_at->format('d/m/Y à H:i') : '' }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ url('/admin/commerce/orders') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> {{ __('Retour aux commandes') }}
            </a>
            <form action="{{ url('/admin/commerce/orders/'.$order->id) }}" method="POST" onsubmit="return confirm('{{ __('Supprimer cette commande ?') }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-trash"></i> {{ __('Supprimer') }}
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left: Line items -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-dark">
                        <i class="bi bi-box-seam text-primary me-1"></i> {{ __('Articles commandés') }}
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light fs-7 text-uppercase text-muted">
                            <tr>
                                <th>{{ __('Produit') }}</th>
                                <th class="text-center">{{ __('Prix unitaire') }}</th>
                                <th class="text-center">{{ __('Quantité') }}</th>
                                <th class="text-end pe-3">{{ __('Sous-total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $item->product_name }}</div>
                                    </td>
                                    <td class="text-center font-monospace">{{ \Plugins\CommerceLite\Models\Product::formatPrice($item->price) }}</td>
                                    <td class="text-center font-monospace">{{ $item->quantity }}</td>
                                    <td class="text-end pe-3 font-monospace fw-bold text-dark">{{ $item->formattedTotal() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end fw-bold">{{ __('Montant Total Réglé :') }}</td>
                                <td class="text-end pe-3 font-monospace fs-5 fw-bold text-primary">{{ $order->formattedTotal() }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right: Customer, shipping, and status manager -->
        <div class="col-lg-4">
            <!-- Status Update Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                <h5 class="fw-bold fs-6 mb-3 text-dark">{{ __('Statut de la commande') }}</h5>
                <form action="{{ url('/admin/commerce/orders/'.$order->id.'/status') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <select name="status" class="form-select font-monospace">
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>En attente de paiement</option>
                            <option value="paid" {{ $order->status === 'paid' ? 'selected' : '' }}>Payée</option>
                            <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>Expédiée</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Annulée</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100 fw-semibold">
                        {{ __('Mettre à jour le statut') }}
                    </button>
                </form>
            </div>

            <!-- Customer Details Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <h5 class="fw-bold fs-6 mb-3 text-dark"><i class="bi bi-person me-1 text-primary"></i> {{ __('Client & Livraison') }}</h5>
                <div class="mb-2">
                    <span class="text-muted small d-block">{{ __('Nom :') }}</span>
                    <span class="fw-semibold text-dark">{{ $order->customer_name }}</span>
                </div>
                <div class="mb-2">
                    <span class="text-muted small d-block">{{ __('E-mail :') }}</span>
                    <a href="mailto:{{ $order->customer_email }}" class="text-decoration-none text-primary">{{ $order->customer_email }}</a>
                </div>
                @if($order->customer_phone)
                    <div class="mb-2">
                        <span class="text-muted small d-block">{{ __('Téléphone :') }}</span>
                        <span>{{ $order->customer_phone }}</span>
                    </div>
                @endif
                <div class="mt-3 pt-3 border-top">
                    <span class="text-muted small d-block mb-1">{{ __('Adresse de livraison :') }}</span>
                    <div class="small p-2 bg-light rounded-3 text-dark">{{ $order->shipping_address }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
