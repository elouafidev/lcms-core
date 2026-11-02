<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($currentCategory) ? $currentCategory->name . ' — ' : (isset($currentTag) ? '#' . $currentTag->name . ' — ' : '') }}Blog & Actualités</title>
    <link rel="alternate" type="application/rss+xml" title="Flux RSS Blog" href="{{ url('/blog/feed.xml') }}" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background: #f8fafc; color: #1e293b; }
        .blog-header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff; padding: 4rem 1rem; margin-bottom: 3rem; }
        .post-card { border: none; border-radius: 1rem; overflow: hidden; transition: transform 0.2s ease, box-shadow 0.2s ease; background: #ffffff; height: 100%; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .post-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .post-thumbnail { height: 200px; object-fit: cover; width: 100%; }
        .category-pill { text-decoration: none; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 50rem; padding: 0.25rem 0.75rem; }
        .tag-pill { text-decoration: none; font-size: 0.8rem; background: #e2e8f0; color: #475569; border-radius: 50rem; padding: 0.25rem 0.6rem; transition: background 0.15s ease; }
        .tag-pill:hover { background: #cbd5e1; color: #1e293b; }
    </style>
</head>
<body>
    <!-- Blog Header Banner -->
    <header class="blog-header text-center">
        <div class="container">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 mb-3 text-uppercase fw-semibold" style="font-size: 0.8rem;">
                {{ __('Le Mag & Actualités') }}
            </span>
            <h1 class="display-5 fw-bold mb-3">
                @if(isset($currentCategory))
                    {{ __('Catégorie : ') }} {{ $currentCategory->name }}
                @elseif(isset($currentTag))
                    {{ __('Étiquette : #') }}{{ $currentTag->name }}
                @else
                    {{ __('Blog & Actualités') }}
                @endif
            </h1>
            <p class="lead text-light opacity-75 max-w-2xl mx-auto mb-4" style="max-width: 600px;">
                @if(isset($currentCategory) && $currentCategory->description)
                    {{ $currentCategory->description }}
                @else
                    {{ __('Découvrez nos derniers articles, analyses techniques et retours d\'expérience.') }}
                @endif
            </p>
            <div class="d-flex justify-content-center align-items-center gap-3">
                <a href="{{ url('/blog/feed.xml') }}" target="_blank" class="btn btn-outline-light btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-rss-fill text-warning"></i> {{ __('S\'abonner au flux RSS') }}
                </a>
                @if(isset($currentCategory) || isset($currentTag) || !empty($search))
                    <a href="{{ url('/blog') }}" class="btn btn-light btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="bi bi-arrow-left"></i> {{ __('Tous les articles') }}
                    </a>
                @endif
            </div>
        </div>
    </header>

    <div class="container pb-5">
        <div class="row g-4">
            <!-- Main Content: Articles -->
            <div class="col-lg-8">
                <!-- Search & Filters Info Bar -->
                @if(!empty($search))
                    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4 d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-search me-1"></i> {{ __('Résultats pour la recherche :') }} <strong>« {{ $search }} »</strong>
                        </div>
                        <a href="{{ url('/blog') }}" class="btn btn-sm btn-outline-secondary rounded-pill">{{ __('Effacer') }}</a>
                    </div>
                @endif

                <div class="row g-4">
                    @forelse($posts as $post)
                        <div class="col-md-6">
                            <article class="post-card d-flex flex-column">
                                <a href="{{ url('/blog/' . $post->slug) }}" class="text-decoration-none">
                                    @if($post->featured_image)
                                        <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="post-thumbnail">
                                    @else
                                        <div class="bg-light border-bottom d-flex align-items-center justify-content-center text-muted" style="height: 200px;">
                                            <i class="bi bi-newspaper fs-1 opacity-25"></i>
                                        </div>
                                    @endif
                                </a>
                                <div class="p-4 d-flex flex-column flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        @if($post->category)
                                            <a href="{{ url('/blog/category/' . $post->category->slug) }}" class="category-pill"
                                               style="background-color: {{ $post->category->color }}15; color: {{ $post->category->color }}; border: 1px solid {{ $post->category->color }}30;">
                                                {{ $post->category->name }}
                                            </a>
                                        @else
                                            <span></span>
                                        @endif
                                        <span class="text-muted small">
                                            <i class="bi bi-clock"></i> {{ $post->reading_time }} min
                                        </span>
                                    </div>

                                    <h2 class="h5 fw-bold mb-2">
                                        <a href="{{ url('/blog/' . $post->slug) }}" class="text-dark text-decoration-none">
                                            {{ $post->title }}
                                        </a>
                                    </h2>

                                    <p class="text-secondary small mb-4 flex-grow-1">
                                        {{ $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 120) }}
                                    </p>

                                    <div class="pt-3 border-top d-flex justify-content-between align-items-center mt-auto">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                {{ substr($post->author?->name ?? 'A', 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark small" style="line-height: 1.1;">{{ $post->author?->name ?? 'Auteur' }}</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">{{ $post->published_at ? $post->published_at->format('d/m/Y') : $post->created_at?->format('d/m/Y') }}</div>
                                            </div>
                                        </div>
                                        <a href="{{ url('/blog/' . $post->slug) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            {{ __('Lire') }} <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-journal-x fs-1 text-muted opacity-50 d-block mb-3"></i>
                            <h4 class="fw-bold text-dark">{{ __('Aucun article trouvé') }}</h4>
                            <p class="text-muted">{{ __('Revenez bientôt ou essayez une autre recherche.') }}</p>
                            <a href="{{ url('/blog') }}" class="btn btn-primary rounded-pill px-4">{{ __('Retourner au blog') }}</a>
                        </div>
                    @endforelse
                </div>

                @if($posts->hasPages())
                    <div class="mt-4 pt-3">
                        {{ $posts->links() }}
                    </div>
                @endif
            </div>

            <!-- Sidebar Column -->
            <div class="col-lg-4">
                <!-- Search Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-3">
                    <form action="{{ url('/blog') }}" method="GET">
                        <div class="input-group">
                            <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control border-end-0" placeholder="{{ __('Rechercher un article...') }}">
                            <button type="submit" class="btn btn-primary px-3"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>

                <!-- Categories Box -->
                @if(isset($categories) && $categories->isNotEmpty())
                    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-folder me-1 text-primary"></i> {{ __('Catégories') }}</h5>
                        </div>
                        <div class="list-group list-group-flush rounded-bottom-4">
                            @foreach($categories as $cat)
                                <a href="{{ url('/blog/category/' . $cat->slug) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3">
                                    <span class="d-flex align-items-center gap-2">
                                        <span class="badge rounded-circle p-1" style="background-color: {{ $cat->color }};"></span>
                                        {{ $cat->name }}
                                    </span>
                                    <span class="badge bg-light text-dark rounded-pill">{{ $cat->posts_count ?? 0 }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Tags Cloud Box -->
                @if(isset($tags) && $tags->isNotEmpty())
                    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-tags me-1 text-primary"></i> {{ __('Étiquettes populaires') }}</h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($tags as $t)
                                    <a href="{{ url('/blog/tag/' . $t->slug) }}" class="tag-pill">
                                        #{{ $t->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
