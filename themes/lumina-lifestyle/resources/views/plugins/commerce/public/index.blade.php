@extends('theme::layouts.app')

@section('title', 'Boutique Éthique & Créations Artisanales — Lumina Lifestyle')

@section('content')
    <!-- Banner En-tête Boutique -->
    <section class="lumina-hero py-5 text-center">
        <div class="container py-3">
            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 text-uppercase fw-bold small mb-2">
                {{ __('Catalogue & Éco-Conception') }}
            </span>
            <h1 class="display-5 fw-bold lumina-font-serif text-dark mb-3">
                @if(!empty($search))
                    {{ __('Recherche Boutique : « ') }}{{ $search }}{{ __(' »') }}
                @else
                    {{ __('Objets & Créations pour le Quotidien') }}
                @endif
            </h1>
            <p class="lead text-muted mx-auto mb-4" style="max-width: 650px;">
                {{ __('Chaque pièce est sélectionnée pour son utilité, la noblesse de ses matériaux et l\'intégrité de ses artisans créateurs.') }}
            </p>
            <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
                <form action="{{ url('/shop') }}" method="GET" class="d-flex gap-2" style="max-width: 400px; width: 100%;">
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="{{ __('Rechercher un produit, une matière, un SKU...') }}" class="form-control lumina-input rounded-pill px-4">
                    <button type="submit" class="btn btn-lumina-primary rounded-pill px-4">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
                @if(!empty($search))
                    <a href="{{ url('/shop') }}" class="btn btn-outline-secondary rounded-pill px-3 btn-sm">
                        <i class="bi bi-x-circle me-1"></i> {{ __('Effacer') }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    <!-- Grille de Produits -->
    <div class="container py-5">
        <div class="row g-4">
            @forelse($products as $product)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="lumina-product-card">
                        <div class="lumina-product-img-wrap">
                            <img src="{{ $product->image ?: 'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&w=600&q=80' }}" alt="{{ $product->name }}">
                            @if($product->stock > 0)
                                <span class="lumina-stock-badge lumina-stock-in">
                                    <i class="bi bi-check-circle-fill me-1"></i> {{ __('En stock') }}
                                </span>
                            @else
                                <span class="lumina-stock-badge lumina-stock-out">
                                    {{ __('Épuisé') }}
                                </span>
                            @endif
                        </div>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="text-muted small font-monospace mb-1">{{ $product->sku }}</div>
                            <h6 class="fw-bold mb-2">
                                <a href="{{ url('/shop/' . $product->slug) }}" class="text-dark text-decoration-none hover-primary">
                                    {{ $product->name }}
                                </a>
                            </h6>
                            <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="fs-5 fw-bold font-monospace text-primary">
                                    {{ $product->formattedPrice() }}
                                </span>
                                @if($product->inStock())
                                    <form action="{{ url('/cart/add') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn btn-sm btn-lumina-primary rounded-pill px-3" title="{{ __('Ajouter au panier') }}">
                                            <i class="bi bi-bag-plus"></i>
                                        </button>
                                    </form>
                                @else
                                    <button class="btn btn-sm btn-light rounded-pill px-3 text-muted disabled">
                                        <i class="bi bi-x"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center py-5 bg-white rounded-4 border p-5">
                        <i class="bi bi-bag-x fs-1 text-muted mb-2"></i>
                        <h4 class="fw-bold mb-2">{{ __('Aucun produit trouvé') }}</h4>
                        <p class="text-muted mb-4">{{ __('Aucun article ne correspond à votre recherche dans la boutique.') }}</p>
                        <a href="{{ url('/shop') }}" class="btn btn-lumina-primary rounded-pill px-4">{{ __('Voir tout le catalogue') }}</a>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-5 d-flex justify-content-center">
            {{ $products->links() }}
        </div>
    </div>
@endsection
