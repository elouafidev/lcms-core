@extends('theme::layouts.app')

@section('title', 'Finaliser ma commande — Lumina Lifestyle')

@section('content')
    <div class="container py-5" style="max-width: 960px;">
        <div class="mb-4">
            <h1 class="h2 fw-bold lumina-font-serif text-dark mb-1">{{ __('Finalisation de votre Commande') }}</h1>
            <p class="text-muted small mb-0">{{ __('Renseignez vos coordonnées de livraison sécurisée.') }}</p>
        </div>

        <div class="row g-4">
            <!-- Formulaire de Coordonnées -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 p-md-5">
                    <h5 class="fw-bold mb-4 text-dark d-flex align-items-center gap-2 lumina-font-serif">
                        <i class="bi bi-geo-alt text-primary"></i> {{ __('Coordonnées & Livraison') }}
                    </h5>
                    
                    <form action="{{ url('/checkout') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">{{ __('Nom complet') }} <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" value="{{ old('customer_name') }}" required class="form-control lumina-input" placeholder="Jeanne Martin">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">{{ __('Adresse e-mail') }} <span class="text-danger">*</span></label>
                                <input type="email" name="customer_email" value="{{ old('customer_email') }}" required class="form-control lumina-input" placeholder="jeanne.martin@example.com">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">{{ __('Téléphone') }}</label>
                                <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" class="form-control lumina-input" placeholder="+33 6 12 34 56 78">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small">{{ __('Adresse complète de livraison') }} <span class="text-danger">*</span></label>
                            <textarea name="shipping_address" required rows="3" class="form-control lumina-input" placeholder="12 rue de la Paix, 75001 Paris, France">{{ old('shipping_address') }}</textarea>
                        </div>

                        <div class="p-3 rounded-4 bg-light mb-4 border d-flex align-items-center gap-3">
                            <i class="bi bi-shield-lock-fill text-success fs-3"></i>
                            <div class="small text-muted">
                                <strong class="text-dark d-block">{{ __('Paiement sécurisé et chiffré') }}</strong>
                                {{ __('Vos informations sont traitées dans le respect strict des normes bancaires européennes.') }}
                            </div>
                        </div>

                        <button type="submit" class="btn btn-lumina-primary btn-lg rounded-pill w-100 py-3">
                            <i class="bi bi-check2-circle me-1"></i> {{ __('Confirmer et régler ma commande') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Récapitulatif de Commande -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                    <h5 class="fw-bold mb-3 text-dark lumina-font-serif">{{ __('Récapitulatif') }}</h5>
                    <ul class="list-group list-group-flush mb-3">
                        @foreach($items as $item)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 bg-transparent">
                                <div>
                                    <div class="fw-semibold text-dark small">{{ $item['product']->name }}</div>
                                    <div class="text-muted small">Qté : {{ $item['quantity'] }} &times; {{ $item['product']->formattedPrice() }}</div>
                                </div>
                                <span class="font-monospace fw-bold text-dark">{{ \Plugins\CommerceLite\Models\Product::formatPrice($item['subtotal']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <span class="fw-bold text-dark">{{ __('Total à régler :') }}</span>
                        <span class="fs-4 fw-bold text-primary font-monospace">{{ \Plugins\CommerceLite\Models\Product::formatPrice($total) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
