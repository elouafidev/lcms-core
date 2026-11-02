<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Boutique en ligne — LCMS Core') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; }
        .product-card { border: none; border-radius: 1rem; background: #ffffff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 10px 20px -3px rgba(0,0,0,0.08); }
    </style>
</head>
<body>
    <!-- Top Nav -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-dark d-flex align-items-center gap-2" href="{{ url('/') }}">
                <i class="bi bi-layers-fill text-primary"></i> LCMS Core
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                @include('commerce::components.cart-button')
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="h2 fw-bold text-dark mb-1">{{ __('Boutique en ligne') }}</h1>
                <p class="text-muted small mb-0">{{ __('Découvrez notre sélection de produits de qualité.') }}</p>
            </div>
            <form action="{{ url('/shop') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="search" value="{{ $search }}" class="form-control rounded-pill px-3" placeholder="{{ __('Rechercher un produit...') }}">
                <button type="submit" class="btn btn-secondary rounded-pill px-3"><i class="bi bi-search"></i></button>
            </form>
        </div>

        @if(session('commerce_success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 p-3 shadow-sm mb-4">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('commerce_success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            @forelse($products as $product)
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="card product-card h-100 overflow-hidden d-flex flex-column">
                        <div class="bg-light d-flex align-items-center justify-content-center p-4 text-center" style="height: 180px;">
                            @if($product->image)
                                <img src="{{ $product->image }}" alt="{{ $product->name }}" style="max-height: 140px; max-width: 100%; object-fit: contain;">
                            @else
                                <i class="bi bi-box-seam text-secondary opacity-50 fs-1"></i>
                            @endif
                        </div>
                        <div class="card-body p-4 d-flex flex-column flex-grow-1">
                            <span class="badge bg-light text-secondary border font-monospace mb-2 align-self-start">{{ $product->sku }}</span>
                            <h5 class="card-title fw-bold fs-6 mb-2">
                                <a href="{{ url('/shop/'.$product->slug) }}" class="text-decoration-none text-dark stretched-link">
                                    {{ $product->name }}
                                </a>
                            </h5>
                            <p class="card-text text-muted small flex-grow-1">
                                {{ \Illuminate\Support\Str::limit(strip_tags((string)$product->description), 80) }}
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <span class="fs-5 fw-bold text-primary">{{ $product->formattedPrice() }}</span>
                                <span class="badge {{ $product->inStock() ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} rounded-pill">
                                    {{ $product->inStock() ? __('En stock') : __('Épuisé') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5 text-muted">
                    <i class="bi bi-bag-x fs-1 opacity-25 d-block mb-3"></i>
                    <p>{{ __('Aucun produit disponible pour le moment.') }}</p>
                </div>
            @endforelse
        </div>

        @if($products->hasPages())
            <div class="d-flex justify-content-center mt-5">
                {{ $products->links() }}
            </div>
        @endif
    </main>
</body>
</html>
