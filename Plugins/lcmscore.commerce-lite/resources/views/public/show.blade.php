<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product->name }} — {{ __('Boutique') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; }
        .product-box { border: none; border-radius: 1.25rem; background: #ffffff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-dark d-flex align-items-center gap-2" href="{{ url('/') }}">
                <i class="bi bi-layers-fill text-primary"></i> LCMS Core
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="{{ url('/shop') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 me-2">
                    <i class="bi bi-arrow-left"></i> {{ __('Boutique') }}
                </a>
                @include('commerce::components.cart-button')
            </div>
        </div>
    </nav>

    <main class="container py-5" style="max-width: 960px;">
        @if(session('commerce_success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 p-3 shadow-sm mb-4">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('commerce_success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card product-box p-4 p-md-5">
            <div class="row g-5 align-items-center">
                <div class="col-md-5 text-center bg-light rounded-4 p-5 d-flex align-items-center justify-content-center" style="min-height: 280px;">
                    @if($product->image)
                        <img src="{{ $product->image }}" alt="{{ $product->name }}" class="img-fluid" style="max-height: 240px; object-fit: contain;">
                    @else
                        <i class="bi bi-box-seam text-secondary opacity-50" style="font-size: 5rem;"></i>
                    @endif
                </div>
                <div class="col-md-7">
                    <span class="badge bg-light text-secondary border font-monospace mb-2">{{ $product->sku }}</span>
                    <h1 class="h2 fw-bold text-dark mb-3">{{ $product->name }}</h1>
                    <div class="fs-3 fw-bold text-primary mb-3">{{ $product->formattedPrice() }}</div>

                    <div class="mb-4 text-muted">
                        {!! nl2br(e($product->description)) !!}
                    </div>

                    <div class="mb-4">
                        @if($product->inStock())
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                                <i class="bi bi-check-circle-fill me-1"></i> {{ __('En stock') }} ({{ $product->stock }} {{ __('disponibles') }})
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">
                                <i class="bi bi-x-circle-fill me-1"></i> {{ __('Rupture temporaire de stock') }}
                            </span>
                        @endif
                    </div>

                    @if($product->inStock())
                        <form action="{{ url('/cart/add') }}" method="POST" class="d-flex gap-3 align-items-center">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <div style="width: 90px;">
                                <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock }}" class="form-control form-control-lg text-center fw-bold">
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4 fw-semibold flex-grow-1 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-cart-plus"></i> {{ __('Ajouter au panier') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </main>
</body>
</html>
