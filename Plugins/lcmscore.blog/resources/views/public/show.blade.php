<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $post->meta_title ?: $post->title }} — Blog</title>
    @if($post->meta_description)
        <meta name="description" content="{{ $post->meta_description }}">
    @endif
    <meta property="og:title" content="{{ $post->meta_title ?: $post->title }}" />
    <meta property="og:description" content="{{ $post->meta_description ?: ($post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 150)) }}" />
    @if($post->featured_image)
        <meta property="og:image" content="{{ $post->featured_image }}" />
    @endif
    <meta property="og:type" content="article" />
    <meta property="og:url" content="{{ url('/blog/' . $post->slug) }}" />
    <link rel="alternate" type="application/rss+xml" title="Flux RSS Blog" href="{{ url('/blog/feed.xml') }}" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background: #ffffff; color: #1e293b; line-height: 1.7; }
        .post-article-content { font-size: 1.15rem; color: #334155; }
        .post-article-content p { margin-bottom: 1.5rem; }
        .post-article-content h2 { margin-top: 2.5rem; margin-bottom: 1rem; font-weight: 700; color: #0f172a; }
        .post-article-content h3 { margin-top: 2rem; margin-bottom: 0.75rem; font-weight: 600; color: #1e293b; }
        .post-article-content img { max-width: 100%; height: auto; border-radius: 0.75rem; margin: 1.5rem 0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .post-article-content blockquote { border-left: 4px solid #3b82f6; padding-left: 1.25rem; font-style: italic; color: #475569; margin: 1.5rem 0; }
        .post-article-content pre { background: #1e293b; color: #f8fafc; padding: 1.25rem; border-radius: 0.5rem; overflow-x: auto; }
        .tag-pill { text-decoration: none; font-size: 0.85rem; background: #f1f5f9; color: #475569; border-radius: 50rem; padding: 0.35rem 0.8rem; border: 1px solid #e2e8f0; }
        .tag-pill:hover { background: #e2e8f0; color: #0f172a; }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-dark d-flex align-items-center gap-2" href="{{ url('/') }}">
                <i class="bi bi-layers-fill text-primary"></i> {{ config('app.name', 'LCMS Core') }}
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="{{ url('/blog') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left"></i> {{ __('Retour au Blog') }}
                </a>
            </div>
        </div>
    </nav>

    <!-- Article Container -->
    <main class="container py-5" style="max-width: 860px;">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ url('/') }}" class="text-decoration-none">{{ __('Accueil') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/blog') }}" class="text-decoration-none">{{ __('Blog') }}</a></li>
                @if($post->category)
                    <li class="breadcrumb-item">
                        <a href="{{ url('/blog/category/' . $post->category->slug) }}" class="text-decoration-none">
                            {{ $post->category->name }}
                        </a>
                    </li>
                @endif
                <li class="breadcrumb-item active text-truncate" style="max-width: 250px;" aria-current="page">{{ $post->title }}</li>
            </ol>
        </nav>

        <article>
            <!-- Header Meta -->
            <header class="mb-4">
                @if($post->category)
                    <a href="{{ url('/blog/category/' . $post->category->slug) }}" class="badge text-decoration-none mb-3 px-3 py-1 rounded-pill"
                       style="background-color: {{ $post->category->color }}20; color: {{ $post->category->color }}; border: 1px solid {{ $post->category->color }}40; font-size: 0.85rem;">
                        {{ $post->category->name }}
                    </a>
                @endif

                <h1 class="display-5 fw-bold text-dark mb-3">{{ $post->title }}</h1>

                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 py-3 border-top border-bottom text-muted small">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                            {{ substr($post->author?->name ?? 'A', 0, 1) }}
                        </div>
                        <div>
                            <div class="fw-semibold text-dark">{{ $post->author?->name ?? 'Auteur' }}</div>
                            <div>{{ $post->published_at ? $post->published_at->format('d F Y') : $post->created_at?->format('d F Y') }}</div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <span><i class="bi bi-clock me-1"></i> {{ $post->reading_time }} {{ __('min de lecture') }}</span>
                        <span><i class="bi bi-eye me-1"></i> {{ $post->views_count }} {{ __('lectures') }}</span>
                    </div>
                </div>
            </header>

            <!-- Featured Image -->
            @if($post->featured_image)
                <div class="mb-5 rounded-4 overflow-hidden shadow-sm">
                    <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="img-fluid w-100" style="max-height: 480px; object-fit: cover;">
                </div>
            @endif

            <!-- Excerpt Callout if present -->
            @if($post->excerpt)
                <div class="p-4 mb-4 bg-light rounded-4 border-start border-primary border-4 lead text-secondary">
                    {{ $post->excerpt }}
                </div>
            @endif

            <!-- Body Content -->
            <div class="post-article-content mb-5">
                {!! $post->content !!}
            </div>

            <!-- Tags -->
            @if($post->tags->isNotEmpty())
                <div class="py-4 border-top border-bottom mb-5">
                    <span class="small fw-bold text-uppercase text-muted d-block mb-2">{{ __('Étiquettes :') }}</span>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($post->tags as $tag)
                            <a href="{{ url('/blog/tag/' . $tag->slug) }}" class="tag-pill">
                                #{{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Author Box -->
            <div class="card border-0 shadow-sm rounded-4 bg-light p-4 mb-5">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 56px; height: 56px;">
                        {{ substr($post->author?->name ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <span class="badge bg-secondary-subtle text-secondary mb-1">{{ __('Auteur') }}</span>
                        <h5 class="fw-bold text-dark mb-1">{{ $post->author?->name ?? 'Auteur' }}</h5>
                        <p class="text-muted small mb-0">{{ __('Rédacteur et contributeur sur LCMS Core.') }}</p>
                    </div>
                </div>
            </div>

            <!-- Related Articles -->
            @if($relatedPosts->isNotEmpty())
                <div class="mt-5 pt-4 border-top">
                    <h4 class="fw-bold text-dark mb-4">{{ __('Articles suggérés') }}</h4>
                    <div class="row g-3">
                        @foreach($relatedPosts as $related)
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white">
                                    @if($related->category)
                                        <span class="small fw-semibold mb-1" style="color: {{ $related->category->color }}; font-size: 0.75rem;">
                                            {{ $related->category->name }}
                                        </span>
                                    @endif
                                    <h6 class="fw-bold mb-2">
                                        <a href="{{ url('/blog/' . $related->slug) }}" class="text-dark text-decoration-none">
                                            {{ $related->title }}
                                        </a>
                                    </h6>
                                    <span class="text-muted small mt-auto"><i class="bi bi-clock"></i> {{ $related->reading_time }} min</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </article>
    </main>

    <footer class="bg-light border-top py-4 text-center text-muted small mt-5">
        <div class="container">
            <p class="mb-0">&copy; {{ date('Y') }} {{ config('app.name', 'LCMS Core') }} — {{ __('Tous droits réservés.') }}</p>
        </div>
    </footer>
</body>
</html>
