<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Finaliser ma commande — LCMS Core') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; }
        .checkout-box { border: none; border-radius: 1.25rem; background: #ffffff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-dark d-flex align-items-center gap-2" href="{{ url('/') }}">
                <i class="bi bi-layers-fill text-primary"></i> LCMS Core
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="{{ url('/cart') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left"></i> {{ __('Retour au panier') }}
                </a>
            </div>
        </div>
    </nav>

    <main class="container py-5" style="max-width: 960px;">
        <h1 class="h3 fw-bold text-dark mb-4">{{ __('Finalisation de la commande') }}</h1>

        @if($errors->any())
            <div class="alert alert-danger shadow-sm rounded-4 p-3 mb-4">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-4">
            <!-- Left Column: Customer and Shipping Info Form -->
            <div class="col-lg-7">
                <div class="card checkout-box p-4 p-md-5">
                    <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-person-lines-fill text-primary me-2"></i> {{ __('Vos coordonnées de livraison') }}</h5>
                    <form action="{{ url('/checkout') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">{{ __('Nom complet') }} <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" value="{{ old('customer_name') }}" required class="form-control" placeholder="Jean Dupont">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">{{ __('Adresse e-mail') }} <span class="text-danger">*</span></label>
                                <input type="email" name="customer_email" value="{{ old('customer_email') }}" required class="form-control" placeholder="jean.dupont@example.com">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">{{ __('Téléphone') }}</label>
                                <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" class="form-control" placeholder="+33 6 12 34 56 78">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small">{{ __('Adresse complète de livraison') }} <span class="text-danger">*</span></label>
                            <textarea name="shipping_address" required rows="3" class="form-control" placeholder="12 rue de la Paix, 75001 Paris, France">{{ old('shipping_address') }}</textarea>
                        </div>

                        <div class="alert alert-light border rounded-3 p-3 mb-4 small text-muted">
                            <i class="bi bi-lock-fill text-success me-1"></i>
                            {{ __('Règlement sécurisé par carte bancaire. Vos données sont chiffrées de bout en bout.') }}
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg rounded-pill w-100 fw-semibold shadow-sm">
                            <i class="bi bi-credit-card me-1"></i> {{ __('Confirmer et Régler la commande') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Order Summary -->
            <div class="col-lg-5">
                <div class="card checkout-box p-4">
                    <h5 class="fw-bold mb-3 text-dark">{{ __('Récapitulatif') }}</h5>
                    <ul class="list-group list-group-flush mb-3">
                        @foreach($items as $item)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                <div>
                                    <div class="fw-semibold text-dark small">{{ $item['product']->name }}</div>
                                    <div class="text-muted small">Qté : {{ $item['quantity'] }} &times; {{ $item['product']->formattedPrice() }}</div>
                                </div>
                                <span class="font-monospace fw-bold text-dark">{{ \Plugins\CommerceLite\Models\Product::formatPrice($item['subtotal']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <span class="fw-bold text-dark">{{ __('Total à payer :') }}</span>
                        <span class="fs-4 fw-bold text-primary font-monospace">{{ \Plugins\CommerceLite\Models\Product::formatPrice($total) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
