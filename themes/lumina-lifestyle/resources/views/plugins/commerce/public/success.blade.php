@extends('theme::layouts.app')

@section('title', 'Confirmation de commande — Lumina Lifestyle')

@section('content')
    <div class="container py-5" style="max-width: 720px;">
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 p-md-5 text-center mb-4">
            <div class="mx-auto mb-3 bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
                <i class="bi bi-check-lg fs-1"></i>
            </div>
            <h1 class="h3 fw-bold lumina-font-serif text-dark mb-2">{{ __('Merci pour votre commande !') }}</h1>
            <p class="text-muted mb-4">
                {{ __('Votre commande a été validée avec succès. Un e-mail récapitulatif a été envoyé à') }} <strong>{{ $order->customer_email }}</strong>.
            </p>

            <div class="card border rounded-4 p-4 text-start bg-light mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <span class="text-muted small">{{ __('Numéro de commande :') }}</span>
                    <span class="font-monospace fw-bold text-primary">{{ $order->order_number }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <span class="text-muted small">{{ __('Statut :') }}</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                        <i class="bi bi-check-circle-fill me-1"></i> {{ __('Payée') }}
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <span class="text-muted small">{{ __('Client :') }}</span>
                    <span class="fw-semibold text-dark">{{ $order->customer_name }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <span class="text-muted small">{{ __('Adresse de livraison :') }}</span>
                    <span class="small text-dark text-end">{{ $order->shipping_address }}</span>
                </div>

                <h6 class="fw-bold mt-3 mb-2 small text-uppercase text-muted">{{ __('Articles commandés :') }}</h6>
                <ul class="list-group list-group-flush mb-2">
                    @foreach($order->items as $item)
                        <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-2 small">
                            <span>{{ $item->product_name }} &times; {{ $item->quantity }}</span>
                            <span class="font-monospace fw-bold">{{ $item->formattedTotal() }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <span class="fw-bold text-dark">{{ __('Total réglé :') }}</span>
                    <span class="fs-4 fw-bold text-success font-monospace">{{ $order->formattedTotal() }}</span>
                </div>
            </div>

            <div>
                <a href="{{ url('/shop') }}" class="btn btn-lumina-primary rounded-pill px-4">
                    <i class="bi bi-bag me-1"></i> {{ __('Retourner à la boutique') }}
                </a>
            </div>
        </div>
    </div>
@endsection
