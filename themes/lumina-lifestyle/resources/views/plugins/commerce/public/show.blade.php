@extends('theme::layouts.app')

@section('title', $product->name . ' — Boutique Lumina Lifestyle')

@section('content')
    <div class="container py-5" style="max-width: 1040px;">
        <!-- Breadcrumbs -->
        <nav class="mb-4 small text-muted d-flex align-items-center gap-2">
            <a href="{{ url('/') }}" class="text-decoration-none text-muted">{{ __('Accueil') }}</a>
            <span>/</span>
            <a href="{{ url('/shop') }}" class="text-decoration-none text-muted">{{ __('Boutique') }}</a>
            <span>/</span>
            <span class="text-dark fw-semibold">{{ $product->name }}</span>
        </nav>

        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 p-md-5">
            <div class="row g-5 align-items-center">
                <!-- Product Visual -->
                <div class="col-md-5">
                    <div class="position-relative rounded-4 overflow-hidden shadow-sm bg-light text-center p-3">
                        <img src="{{ $product->image ?: 'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&w=700&q=80' }}" alt="{{ $product->name }}" class="img-fluid rounded-3" style="max-height: 420px; object-fit: cover;">
                        @if($product->inStock())
                            <span class="lumina-stock-badge lumina-stock-in">
                                <i class="bi bi-check-circle-fill me-1"></i> {{ __('En stock') }}
                            </span>
                        @else
                            <span class="lumina-stock-badge lumina-stock-out">
                                {{ __('Épuisé') }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Product Details & Actions -->
                <div class="col-md-7">
                    <div class="text-muted small font-monospace mb-2">REF: {{ $product->sku }}</div>
                    <h1 class="display-6 fw-bold lumina-font-serif text-dark mb-3">{{ $product->name }}</h1>
                    
                    <div class="fs-2 fw-bold font-monospace text-primary mb-4">
                        {{ $product->formattedPrice() }}
                    </div>

                    <div class="text-muted mb-4 fs-6" style="line-height: 1.8;">
                        {!! nl2br(e($product->description)) !!}
                    </div>

                    <!-- Stock Status Alert -->
                    <div class="mb-4">
                        @if($product->inStock())
                            <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-success-subtle text-success small fw-semibold">
                                <i class="bi bi-box-seam"></i>
                                <span>{{ $product->stock }} {{ __('exemplaires disponibles à l\'atelier') }}</span>
                            </div>
                        @else
                            <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-danger-subtle text-danger small fw-semibold">
                                <i class="bi bi-hourglass-split"></i>
                                <span>{{ __('Création momentanément indisponible') }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Add to Cart Form -->
                    @if($product->inStock())
                        <form action="{{ url('/cart/add') }}" method="POST" class="d-flex gap-3 align-items-center flex-wrap">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <div style="width: 100px;">
                                <label class="visually-hidden" for="quantitySelect">{{ __('Quantité') }}</label>
                                <input type="number" id="quantitySelect" name="quantity" value="1" min="1" max="{{ $product->stock }}" class="form-control form-control-lg text-center font-monospace rounded-pill">
                            </div>
                            <button type="submit" class="btn btn-lumina-primary btn-lg rounded-pill px-4 flex-grow-1">
                                <i class="bi bi-bag-plus me-2"></i> {{ __('Ajouter au panier') }}
                            </button>
                        </form>
                    @endif

                    <div class="mt-4 pt-4 border-top d-flex gap-4 text-muted small">
                        <div><i class="bi bi-shield-check text-success me-1"></i> {{ __('Paiement sécurisé') }}</div>
                        <div><i class="bi bi-truck text-primary me-1"></i> {{ __('Expédition neutre') }}</div>
                        <div><i class="bi bi-arrow-counterclockwise text-secondary me-1"></i> {{ __('Retours 30 jours') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
