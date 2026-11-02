@extends('theme::layouts.app')

@section('title', (isset($currentCategory) ? $currentCategory->name . ' — ' : (isset($currentTag) ? '#' . $currentTag->name . ' — ' : '')) . 'Le Journal & Chroniques — Lumina Lifestyle')

@section('content')
    <!-- Banner En-tête Blog -->
    <section class="lumina-hero py-5 text-center">
        <div class="container py-3">
            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 text-uppercase fw-bold small mb-2">
                {{ __('Le Journal Slow Living') }}
            </span>
            <h1 class="display-5 fw-bold lumina-font-serif text-dark mb-3">
                @if(isset($currentCategory))
                    {{ __('Dossier : ') }} {{ $currentCategory->name }}
                @elseif(isset($currentTag))
                    {{ __('Chroniques : #') }}{{ $currentTag->name }}
                @elseif(!empty($search))
                    {{ __('Recherche : « ') }}{{ $search }}{{ __(' »') }}
                @else
                    {{ __('Inspirations, Savoir-faire & Récits') }}
                @endif
            </h1>
            <p class="lead text-muted mx-auto mb-4" style="max-width: 650px;">
                @if(isset($currentCategory) && $currentCategory->description)
                    {{ $currentCategory->description }}
                @else
                    {{ __('Explorez nos articles de fond dédiés à la décoration apaisante, aux artisans d\'exception et aux gestes durables au quotidien.') }}
                @endif
            </p>
            <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
                <a href="{{ url('/rss.xml') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1">
                    <i class="bi bi-rss-fill text-warning"></i> {{ __('Flux RSS 2.0') }}
                </a>
                @if(isset($currentCategory) || isset($currentTag) || !empty($search))
                    <a href="{{ url('/blog') }}" class="btn btn-lumina-primary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1">
                        <i class="bi bi-arrow-left"></i> {{ __('Toutes les chroniques') }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    <!-- Main Content & Sidebar -->
    <div class="container py-5">
        <div class="row g-5">
            <!-- Articles Column -->
            <div class="col-lg-8">
                @if($posts->isNotEmpty())
                    <div class="row g-4">
                        @foreach($posts as $post)
                            <div class="col-md-6">
                                <article class="lumina-post-card">
                                    <div class="lumina-post-img-wrap">
                                        <img src="{{ $post->featured_image ?: 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?auto=format&fit=crop&w=700&q=80' }}" alt="{{ $post->title }}">
                                        @if($post->category)
                                            <a href="{{ url('/blog/category/' . $post->category->slug) }}" class="lumina-post-badge text-decoration-none">
                                                {{ $post->category->name }}
                                            </a>
                                        @endif
                                    </div>
                                    <div class="p-4 d-flex flex-column flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 text-muted small mb-2">
                                            <i class="bi bi-clock"></i>
                                            <span>{{ $post->reading_time ?? 4 }} min {{ __('de lecture') }}</span>
                                            <span>•</span>
                                            <span>{{ $post->published_at?->format('d/m/Y') }}</span>
                                        </div>
                                        <h5 class="fw-bold lumina-font-serif mb-2">
                                            <a href="{{ url('/blog/' . $post->slug) }}" class="text-dark text-decoration-none hover-primary">
                                                {{ $post->title }}
                                            </a>
                                        </h5>
                                        <p class="text-muted small mb-3">
                                            {{ Str::limit($post->excerpt ?: strip_tags($post->content), 120) }}
                                        </p>
                                        <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                            <span class="small fw-semibold text-secondary">
                                                {{ $post->author?->name ?? 'Lumina Redaction' }}
                                            </span>
                                            <a href="{{ url('/blog/' . $post->slug) }}" class="btn btn-sm btn-link text-primary p-0 text-decoration-none fw-semibold">
                                                {{ __('Lire l\'article') }} &rarr;
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-5 d-flex justify-content-center">
                        {{ $posts->links() }}
                    </div>
                @else
                    <div class="text-center py-5 bg-white rounded-4 border p-5">
                        <i class="bi bi-journal-x fs-1 text-muted mb-2"></i>
                        <h4 class="fw-bold mb-2">{{ __('Aucune chronique trouvée') }}</h4>
                        <p class="text-muted mb-4">{{ __('Aucun article ne correspond à vos critères actuels.') }}</p>
                        <a href="{{ url('/blog') }}" class="btn btn-lumina-primary rounded-pill px-4">{{ __('Réinitialiser les filtres') }}</a>
                    </div>
                @endif
            </div>

            <!-- Sidebar Column -->
            <div class="col-lg-4">
                <!-- Search widget -->
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                    <h6 class="fw-bold lumina-font-serif mb-3">{{ __('Rechercher dans le Journal') }}</h6>
                    <form action="{{ url('/blog') }}" method="GET" class="d-flex gap-2">
                        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Ex: café, lin, grès..." class="form-control lumina-input">
                        <button type="submit" class="btn btn-lumina-primary px-3 rounded-pill">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>

                <!-- Categories -->
                @if($categories->isNotEmpty())
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                        <h6 class="fw-bold lumina-font-serif mb-3">{{ __('Thématiques') }}</h6>
                        <div class="list-group list-group-flush">
                            @foreach($categories as $category)
                                <a href="{{ url('/blog/category/' . $category->slug) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2 border-0 {{ (isset($currentCategory) && $currentCategory->id === $category->id) ? 'fw-bold text-primary' : 'text-dark' }}">
                                    <span>{{ $category->name }}</span>
                                    <span class="badge bg-light text-muted rounded-pill">{{ $category->posts_count ?? 0 }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Tags -->
                @if($tags->isNotEmpty())
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                        <h6 class="fw-bold lumina-font-serif mb-3">{{ __('Mots-clés') }}</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                <a href="{{ url('/blog/tag/' . $tag->slug) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 {{ (isset($currentTag) && $currentTag->id === $tag->id) ? 'active' : '' }}">
                                    #{{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Boutique Callout Widget -->
                <div class="card border-0 shadow-sm rounded-4 text-white p-4 text-center position-relative overflow-hidden" style="background: linear-gradient(135deg, #1c1917 0%, #292524 100%);">
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 small mb-2 mx-auto">
                        {{ __('La Boutique Lumina') }}
                    </span>
                    <h5 class="lumina-font-serif fw-bold text-white mb-2">{{ __('Objets façonnés à la main') }}</h5>
                    <p class="small text-light opacity-75 mb-3">{{ __('Prolongez la lecture en découvrant nos séries limitées conçues par des artisans passionnés.') }}</p>
                    <div>
                        <a href="{{ url('/shop') }}" class="btn btn-lumina-primary btn-sm rounded-pill px-4">
                            {{ __('Découvrir le catalogue') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
