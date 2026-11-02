<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Mon Panier — LCMS Core') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; }
        .cart-box { border: none; border-radius: 1.25rem; background: #ffffff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-dark d-flex align-items-center gap-2" href="{{ url('/') }}">
                <i class="bi bi-layers-fill text-primary"></i> LCMS Core
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="{{ url('/shop') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left"></i> {{ __('Continuer mes achats') }}
                </a>
            </div>
        </div>
    </nav>

    <main class="container py-5" style="max-width: 900px;">
        <h1 class="h3 fw-bold text-dark mb-4">{{ __('Mon Panier') }}</h1>

        @if(session('commerce_success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 p-3 shadow-sm mb-4">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('commerce_success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('commerce_error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-4 p-3 shadow-sm mb-4">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('commerce_error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(count($items) > 0)
            <div class="card cart-box p-4 mb-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light fs-7 text-uppercase text-muted">
                            <tr>
                                <th style="width: 45%;">{{ __('Produit') }}</th>
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
                                        <div class="text-muted small font-monospace">{{ $item['product']->sku }}</div>
                                    </td>
                                    <td class="text-center font-monospace">{{ $item['product']->formattedPrice() }}</td>
                                    <td class="text-center">
                                        <form action="{{ url('/cart/update') }}" method="POST" class="d-inline-flex align-items-center gap-1 justify-content-center">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                                            <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="99" class="form-control form-control-sm text-center font-monospace" style="width: 60px;">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="{{ __('Mettre à jour') }}"><i class="bi bi-arrow-clockwise"></i></button>
                                        </form>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-dark">
                                        {{ \Plugins\CommerceLite\Models\Product::formatPrice($item['subtotal']) }}
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ url('/cart/remove/'.$item['product']->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="{{ __('Supprimer') }}"><i class="bi bi-trash"></i></button>
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
                        <span class="fs-4 fw-bold text-primary font-monospace">{{ \Plugins\CommerceLite\Models\Product::formatPrice($total) }}</span>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <a href="{{ url('/checkout') }}" class="btn btn-primary btn-lg rounded-pill px-5 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                    {{ __('Passer la commande') }} <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        @else
            <div class="card cart-box p-5 text-center text-muted">
                <i class="bi bi-cart-x fs-1 d-block mb-3 opacity-25"></i>
                <h4 class="h5 fw-bold text-dark">{{ __('Votre panier est vide') }}</h4>
                <p class="small mb-4">{{ __('Parcourez notre catalogue et découvrez nos produits.') }}</p>
                <div>
                    <a href="{{ url('/shop') }}" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-bag me-1"></i> {{ __('Voir la boutique') }}
                    </a>
                </div>
            </div>
        @endif
    </main>
</body>
</html>
