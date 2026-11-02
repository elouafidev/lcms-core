@extends('theme::layouts.app')

@section('title', 'Lumina Lifestyle — Journal Slow Living & Boutique Artisanale')

@section('content')
    <!-- 1. Section Hero (Via Bloc Modulaire ou Fallback éditorial) -->
    <section class="lumina-hero">
        <div class="container">
            @if(array_key_exists('renderBlock', Illuminate\Support\Facades\Blade::getCustomDirectives()))
                @renderBlock('home-hero')
            @else
                <div class="row align-items-center g-5">
                    <div class="col-lg-7 text-center text-lg-start">
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 text-uppercase fw-bold letter-spacing-1 small mb-3">
                            {{ __('Slow Living & Objets Durables') }}
                        </span>
                        <h1 class="display-4 fw-bold lumina-font-serif text-dark mb-3">
                            {{ __('L\'art de vivre avec simplicité, conscience et beauté.') }}
                        </h1>
                        <p class="lead text-muted mb-4 pe-lg-5">
                            {{ __('Découvrez notre sélection de créations artisanales façonnées avec des matières nobles, accompagnée de nos carnets d\'inspiration au quotidien.') }}
                        </p>
                        <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                            <a href="{{ url('/shop') }}" class="btn btn-lumina-primary btn-lg rounded-pill px-4">
                                <i class="bi bi-bag me-1"></i> {{ __('Explorer la boutique') }}
                            </a>
                            <a href="{{ url('/blog') }}" class="btn btn-lumina-secondary btn-lg rounded-pill px-4">
                                <i class="bi bi-journal-text me-1"></i> {{ __('Lire le Journal') }}
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="position-relative p-2 bg-white rounded-5 shadow-lg border">
                            <img src="https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=800&q=80" alt="Lumina Lifestyle Céramique et Décoration" class="img-fluid rounded-4 shadow-sm w-100" style="max-height: 440px; object-fit: cover;">
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <!-- 2. Section Produits Phares (Boutique) -->
    @php
        $featuredProducts = class_exists(\Plugins\CommerceLite\Models\Product::class)
            ? \Plugins\CommerceLite\Models\Product::query()->where('is_active', true)->orderBy('id', 'asc')->take(4)->get()
            : collect();
    @endphp

    <section class="py-5">
        <div class="container py-3">
            <div class="d-flex justify-content-between align-items-end mb-4 pb-2 border-bottom flex-wrap gap-2">
                <div>
                    <span class="text-uppercase small fw-bold text-muted letter-spacing-1">{{ __('La Boutique Éthique') }}</span>
                    <h2 class="h2 fw-bold lumina-font-serif text-dark mb-0">{{ __('Nos Créations Artisanales') }}</h2>
                </div>
                <div>
                    <a href="{{ url('/shop') }}" class="btn btn-outline-dark btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1">
                        <span>{{ __('Voir toute la boutique') }}</span> <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>

            @if($featuredProducts->isNotEmpty())
                <div class="row g-4">
                    @foreach($featuredProducts as $product)
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
                                        <a href="{{ url('/shop/' . $product->slug) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                            {{ __('Découvrir') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5 bg-white rounded-4 border">
                    <i class="bi bi-bag-x fs-1 text-muted mb-2"></i>
                    <p class="text-muted mb-0">{{ __('Les créations sont en cours de réapprovisionnement.') }}</p>
                </div>
            @endif
        </div>
    </section>

    <!-- 3. Section Engagements & Piliers (Via Bloc Modulaire ou Fallback) -->
    <section class="py-5 bg-white border-top border-bottom">
        <div class="container py-3">
            @if(array_key_exists('renderBlock', Illuminate\Support\Facades\Blade::getCustomDirectives()))
                @renderBlock('brand-values')
            @else
                <div class="row g-4 text-center">
                    <div class="col-md-4">
                        <div class="p-4 rounded-4 bg-light h-100">
                            <div class="mx-auto mb-3 text-primary fs-1">
                                <i class="bi bi-feather"></i>
                            </div>
                            <h5 class="fw-bold lumina-font-serif mb-2">{{ __('Artisanat Éthique') }}</h5>
                            <p class="text-muted small mb-0">{{ __('Chaque pièce est fabriquée à la main au sein d\'ateliers familiaux et rémunérée au juste prix.') }}</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-4 rounded-4 bg-light h-100">
                            <div class="mx-auto mb-3 text-primary fs-1">
                                <i class="bi bi-recycle"></i>
                            </div>
                            <h5 class="fw-bold lumina-font-serif mb-2">{{ __('Matières Durables') }}</h5>
                            <p class="text-muted small mb-0">{{ __('Coton bio certifié GOTS, lin européen, grès naturel et cires 100% végétales sans additifs.') }}</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-4 rounded-4 bg-light h-100">
                            <div class="mx-auto mb-3 text-primary fs-1">
                                <i class="bi bi-box2-heart"></i>
                            </div>
                            <h5 class="fw-bold lumina-font-serif mb-2">{{ __('Expédition Responsable') }}</h5>
                            <p class="text-muted small mb-0">{{ __('Colis protégés sans plastique, cartons recyclés et transport neutre en émissions de carbone.') }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <!-- 4. Section Dernières Chroniques du Journal (Blog) -->
    @php
        $latestPosts = class_exists(\Plugins\Blog\Models\Post::class)
            ? \Plugins\Blog\Models\Post::queryPublished()->latest('published_at')->take(3)->get()
            : collect();
    @endphp

    <section class="py-5">
        <div class="container py-3">
            <div class="d-flex justify-content-between align-items-end mb-4 pb-2 border-bottom flex-wrap gap-2">
                <div>
                    <span class="text-uppercase small fw-bold text-muted letter-spacing-1">{{ __('Le Journal Éditorial') }}</span>
                    <h2 class="h2 fw-bold lumina-font-serif text-dark mb-0">{{ __('Chroniques & Récits Slow Living') }}</h2>
                </div>
                <div>
                    <a href="{{ url('/blog') }}" class="btn btn-outline-dark btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1">
                        <span>{{ __('Lire toutes les chroniques') }}</span> <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>

            @if($latestPosts->isNotEmpty())
                <div class="row g-4">
                    @foreach($latestPosts as $post)
                        <div class="col-md-6 col-lg-4">
                            <article class="lumina-post-card">
                                <div class="lumina-post-img-wrap">
                                    <img src="{{ $post->featured_image ?: 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?auto=format&fit=crop&w=700&q=80' }}" alt="{{ $post->title }}">
                                    @if($post->category)
                                        <span class="lumina-post-badge">
                                            {{ $post->category->name }}
                                        </span>
                                    @endif
                                </div>
                                <div class="p-4 d-flex flex-column flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 text-muted small mb-2">
                                        <i class="bi bi-clock"></i>
                                        <span>{{ $post->reading_time ?? 4 }} min {{ __('de lecture') }}</span>
                                        <span>•</span>
                                        <span>{{ $post->published_at?->format('d M Y') }}</span>
                                    </div>
                                    <h5 class="fw-bold lumina-font-serif mb-2">
                                        <a href="{{ url('/blog/' . $post->slug) }}" class="text-dark text-decoration-none">
                                            {{ $post->title }}
                                        </a>
                                    </h5>
                                    <p class="text-muted small mb-4">
                                        {{ Str::limit($post->excerpt ?: strip_tags($post->content), 110) }}
                                    </p>
                                    <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                        <span class="small fw-semibold text-secondary">
                                            {{ $post->author?->name ?? 'Lumina Redaction' }}
                                        </span>
                                        <a href="{{ url('/blog/' . $post->slug) }}" class="btn btn-sm btn-link text-primary p-0 text-decoration-none fw-semibold">
                                            {{ __('Lire la suite') }} &rarr;
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5 bg-white rounded-4 border">
                    <i class="bi bi-journal-x fs-1 text-muted mb-2"></i>
                    <p class="text-muted mb-0">{{ __('Les chroniques sont en cours de rédaction.') }}</p>
                </div>
            @endif
        </div>
    </section>

    <!-- 5. Section Capture Newsletter -->
    <section class="py-5 bg-light border-top">
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 text-uppercase fw-bold small mb-2">
                        {{ __('La Lettre Lumina') }}
                    </span>
                    <h2 class="display-6 fw-bold lumina-font-serif text-dark mb-3">
                        {{ __('Recevez nos récits, recettes et tirages limités') }}
                    </h2>
                    <p class="text-muted mb-4 px-md-5">
                        {{ __('Une lettre bimensuelle pensée pour faire une pause : inspirations déco, interviews d\'artisans et accès prioritaire à nos collections capsules.') }}
                    </p>
                    <div class="col-md-8 mx-auto">
                        @if(array_key_exists('newsletterForm', Illuminate\Support\Facades\Blade::getCustomDirectives()))
                            @newsletterForm
                        @else
                            <form action="{{ url('/newsletter/subscribe') }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <input type="email" name="email" required placeholder="votre.email@exemple.com" class="form-control rounded-pill px-4">
                                <button type="submit" class="btn btn-lumina-primary rounded-pill px-4 fw-semibold text-nowrap">
                                    {{ __('Rejoindre') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
