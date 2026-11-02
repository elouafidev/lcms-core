@extends('theme::layouts.app')

@section('title', 'Votre Panier d\'Achats — Lumina Lifestyle')

@section('content')
    <div class="container py-5" style="max-width: 960px;">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h2 fw-bold lumina-font-serif text-dark mb-1">{{ __('Votre Panier') }}</h1>
                <p class="text-muted small mb-0">{{ __('Vérifiez vos créations sélectionnées avant de finaliser votre commande.') }}</p>
            </div>
            <div>
                <a href="{{ url('/shop') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i> {{ __('Poursuivre mes achats') }}
                </a>
            </div>
        </div>

        @if(count($items) > 0)
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light fs-7 text-uppercase text-muted">
                            <tr>
                                <th style="width: 45%;">{{ __('Création') }}</th>
                                <th style="width: 15%;" class="text-center">{{ __('Prix unitaire') }}</th>
                                <th style="width: 20%;" class="text-center">{{ __('Quantité') }}</th>
                                <th style="width: 15%;" class="text-end">{{ __('Sous-total') }}</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $item['product']->name }}</div>
                                        <div class="text-muted small font-monospace">REF: {{ $item['product']->sku }}</div>
                                    </td>
                                    <td class="text-center font-monospace">{{ $item['product']->formattedPrice() }}</td>
                                    <td class="text-center">
                                        <form action="{{ url('/cart/update') }}" method="POST" class="d-inline-flex align-items-center gap-1 justify-content-center">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                                            <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="99" class="form-control form-control-sm text-center font-monospace rounded-pill" style="width: 60px;">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-circle p-1" title="{{ __('Mettre à jour') }}">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-dark">
                                        {{ \Plugins\CommerceLite\Models\Product::formatPrice($item['subtotal']) }}
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ url('/cart/remove/'.$item['product']->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-circle p-1" title="{{ __('Supprimer') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top flex-wrap gap-3">
                    <form action="{{ url('/cart/clear') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('{{ __('Vider entièrement votre panier ?') }}');">
                            <i class="bi bi-trash me-1"></i> {{ __('Vider le panier') }}
                        </button>
                    </form>
                    <div class="text-end">
                        <span class="text-muted small me-2">{{ __('Total TTC :') }}</span>
                        <span class="fs-4 fw-bold text-primary font-monospace">
                            {{ \Plugins\CommerceLite\Models\Product::formatPrice($total) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <a href="{{ url('/checkout') }}" class="btn btn-lumina-primary btn-lg rounded-pill px-5">
                    {{ __('Finaliser ma commande') }} <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        @else
            <div class="card border-0 shadow-sm rounded-4 bg-white p-5 text-center text-muted">
                <i class="bi bi-cart-x fs-1 d-block mb-3 text-secondary opacity-50"></i>
                <h4 class="h5 fw-bold text-dark lumina-font-serif mb-2">{{ __('Votre panier est vide') }}</h4>
                <p class="small text-muted mb-4">{{ __('Parcourez notre collection d\'objets pour remplir votre panier.') }}</p>
                <div>
                    <a href="{{ url('/shop') }}" class="btn btn-lumina-primary rounded-pill px-4">
                        <i class="bi bi-bag me-1"></i> {{ __('Explorer la boutique') }}
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection
