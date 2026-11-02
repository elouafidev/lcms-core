@extends('theme::layouts.app')

@section('title', ($post->meta_title ?: $post->title) . ' — Le Journal Lumina')

@section('content')
    <article class="py-5">
        <div class="container" style="max-width: 860px;">
            <!-- Breadcrumbs -->
            <nav class="mb-4 small text-muted d-flex align-items-center gap-2">
                <a href="{{ url('/') }}" class="text-decoration-none text-muted">{{ __('Accueil') }}</a>
                <span>/</span>
                <a href="{{ url('/blog') }}" class="text-decoration-none text-muted">{{ __('Le Journal') }}</a>
                @if($post->category)
                    <span>/</span>
                    <a href="{{ url('/blog/category/' . $post->category->slug) }}" class="text-decoration-none text-primary fw-semibold">{{ $post->category->name }}</a>
                @endif
            </nav>

            <!-- Article Header -->
            <header class="mb-5">
                <div class="d-flex align-items-center gap-2 text-muted small mb-3">
                    @if($post->category)
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-bold text-uppercase">
                            {{ $post->category->name }}
                        </span>
                    @endif
                    <span class="d-flex align-items-center gap-1">
                        <i class="bi bi-clock"></i> {{ $post->reading_time ?? 4 }} min {{ __('de lecture') }}
                    </span>
                    <span>•</span>
                    <span>{{ $post->published_at?->format('d F Y') }}</span>
                </div>

                <h1 class="display-5 fw-bold lumina-font-serif text-dark mb-4">
                    {{ $post->title }}
                </h1>

                @if($post->excerpt)
                    <p class="lead text-muted mb-4 fst-italic">
                        {{ $post->excerpt }}
                    </p>
                @endif

                <div class="d-flex align-items-center justify-content-between pt-3 border-top border-bottom py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 44px; height: 44px; border: 2px solid var(--lumina-primary-soft);">
                            {{ strtoupper(substr($post->author?->name ?? 'L', 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-bold small text-dark">{{ $post->author?->name ?? 'Rédaction Lumina' }}</div>
                            <div class="text-muted small">{{ __('Chroniqueur Slow-Living') }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->url()) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm rounded-circle p-2" title="Partager sur X">
                            <i class="bi bi-twitter-x"></i>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm rounded-circle p-2" title="Partager sur Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Featured Image -->
            @if($post->featured_image)
                <div class="mb-5 rounded-4 overflow-hidden shadow-sm">
                    <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="img-fluid w-100" style="max-height: 480px; object-fit: cover;">
                </div>
            @endif

            <!-- Article Body Content -->
            <div class="lumina-article-content fs-5 text-dark mb-5" style="line-height: 1.85;">
                {!! $post->content !!}
            </div>

            <!-- Tags -->
            @if($post->tags->isNotEmpty())
                <div class="mb-5 pt-4 border-top">
                    <h6 class="text-uppercase small fw-bold text-muted mb-3">{{ __('Thématiques associées') }}</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($post->tags as $tag)
                            <a href="{{ url('/blog/tag/' . $tag->slug) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                #{{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Author Box -->
            <div class="card border-0 shadow-sm rounded-4 bg-light p-4 mb-5">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white shadow-sm d-flex align-items-center justify-content-center text-primary fs-3 fw-bold shrink-0" style="width: 64px; height: 64px;">
                        {{ strtoupper(substr($post->author?->name ?? 'L', 0, 1)) }}
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 lumina-font-serif text-dark">{{ $post->author?->name ?? 'La Rédaction Lumina' }}</h6>
                        <p class="text-muted small mb-0">
                            {{ __('Passionnés de design organique, de création artisanale et d\'éthique environnementale, nos auteurs partagent leurs rencontres au fil des saisons.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Related Articles -->
            @if(isset($relatedPosts) && $relatedPosts->isNotEmpty())
                <div class="pt-5 border-top">
                    <h3 class="lumina-font-serif fw-bold mb-4">{{ __('Poursuivre votre lecture') }}</h3>
                    <div class="row g-4">
                        @foreach($relatedPosts as $related)
                            <div class="col-md-6">
                                <article class="lumina-post-card">
                                    <div class="lumina-post-img-wrap">
                                        <img src="{{ $related->featured_image ?: 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?auto=format&fit=crop&w=600&q=80' }}" alt="{{ $related->title }}">
                                    </div>
                                    <div class="p-4 d-flex flex-column flex-grow-1">
                                        <h6 class="fw-bold lumina-font-serif mb-2">
                                            <a href="{{ url('/blog/' . $related->slug) }}" class="text-dark text-decoration-none hover-primary">
                                                {{ $related->title }}
                                            </a>
                                        </h6>
                                        <p class="text-muted small mb-3">
                                            {{ Str::limit($related->excerpt ?: strip_tags($related->content), 80) }}
                                        </p>
                                        <div class="mt-auto">
                                            <a href="{{ url('/blog/' . $related->slug) }}" class="btn btn-sm btn-link text-primary p-0 text-decoration-none fw-semibold">
                                                {{ __('Lire') }} &rarr;
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </article>
@endsection
