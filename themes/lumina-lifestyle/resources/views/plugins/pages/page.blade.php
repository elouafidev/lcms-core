@extends('theme::layouts.app')

@section('title', $page->title . ' — Lumina Lifestyle')

@section('content')
    <div class="container py-5" style="max-width: 860px;">
        <!-- Breadcrumbs -->
        <nav class="mb-4 small text-muted d-flex align-items-center gap-2">
            <a href="{{ url('/') }}" class="text-decoration-none text-muted">{{ __('Accueil') }}</a>
            @if($page->parent)
                <span>/</span>
                <a href="{{ url('/page/' . $page->parent->slug) }}" class="text-decoration-none text-muted">{{ $page->parent->title }}</a>
            @endif
            <span>/</span>
            <span class="text-dark fw-semibold">{{ $page->title }}</span>
        </nav>

        <article class="card border-0 shadow-sm rounded-4 bg-white p-4 p-md-5">
            <header class="mb-4 pb-3 border-bottom">
                <h1 class="display-5 fw-bold lumina-font-serif text-dark mb-2">{{ $page->title }}</h1>
                <div class="text-muted small">
                    {{ __('Dernière mise à jour le') }} {{ $page->updated_at?->format('d/m/Y') }}
                </div>
            </header>

            @if($page->featured_image)
                <div class="mb-4 rounded-4 overflow-hidden shadow-sm">
                    <img src="{{ $page->featured_image }}" alt="{{ $page->title }}" class="img-fluid w-100" style="max-height: 400px; object-fit: cover;">
                </div>
            @endif

            <div class="lumina-page-content fs-5 text-dark" style="line-height: 1.85;">
                {!! $page->content !!}
            </div>
        </article>
    </div>
@endsection
