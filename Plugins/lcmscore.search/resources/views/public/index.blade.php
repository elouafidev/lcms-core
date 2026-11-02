<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Recherche : ') . ($query ?: 'Tous les contenus') }} — {{ config('app.name', 'LCMS Core') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; }
        .search-card { border: none; border-radius: 1rem; background: #ffffff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: transform 0.15s ease; }
        .search-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08); }
        mark { background-color: #fef08a !important; color: #854d0e !important; padding: 0.1rem 0.3rem; border-radius: 0.25rem; }
    </style>
</head>
<body>
    <!-- Top Nav -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-dark d-flex align-items-center gap-2" href="{{ url('/') }}">
                <i class="bi bi-layers-fill text-primary"></i> {{ config('app.name', 'LCMS Core') }}
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="{{ url('/') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-house"></i> {{ __('Accueil') }}
                </a>
            </div>
        </div>
    </nav>

    <main class="container py-5" style="max-width: 860px;">
        <!-- Search Box Header -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white text-center">
            <h1 class="h3 fw-bold text-dark mb-3">{{ __('Recherche globale') }}</h1>
            <form action="{{ url('/search') }}" method="GET" class="mx-auto w-100" style="max-width: 600px;">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" value="{{ $query }}" class="form-control border-start-0"
                           placeholder="{{ __('Que recherchez-vous ?') }}" autofocus>
                    @if($type && $type !== 'all')
                        <input type="hidden" name="type" value="{{ $type }}">
                    @endif
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">{{ __('Rechercher') }}</button>
                </div>
            </form>
        </div>

        @if(!empty($query))
            <!-- Filter Tabs by Content Type -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ url('/search?q=' . urlencode($query) . '&type=all') }}"
                       class="btn btn-outline-secondary {{ $type === 'all' ? 'active fw-bold' : '' }}">
                        {{ __('Tous') }} ({{ $typeCounts['all'] }})
                    </a>
                    @if($typeCounts['page'] > 0)
                        <a href="{{ url('/search?q=' . urlencode($query) . '&type=page') }}"
                           class="btn btn-outline-secondary {{ $type === 'page' ? 'active fw-bold' : '' }}">
                            {{ __('Pages') }} ({{ $typeCounts['page'] }})
                        </a>
                    @endif
                    @if($typeCounts['post'] > 0)
                        <a href="{{ url('/search?q=' . urlencode($query) . '&type=post') }}"
                           class="btn btn-outline-secondary {{ $type === 'post' ? 'active fw-bold' : '' }}">
                            {{ __('Articles') }} ({{ $typeCounts['post'] }})
                        </a>
                    @endif
                    @if($typeCounts['block'] > 0)
                        <a href="{{ url('/search?q=' . urlencode($query) . '&type=block') }}"
                           class="btn btn-outline-secondary {{ $type === 'block' ? 'active fw-bold' : '' }}">
                            {{ __('Blocs') }} ({{ $typeCounts['block'] }})
                        </a>
                    @endif
                </div>
                <div class="small text-muted">
                    {{ count($results) }} {{ __('résultat(s) pour') }} <strong>« {{ $query }} »</strong>
                </div>
            </div>

            <!-- Results List -->
            <div class="d-flex flex-column gap-3 mb-5">
                @forelse($results as $result)
                    <article class="search-card p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge {{ $result->type === 'page' ? 'bg-primary-subtle text-primary' : ($result->type === 'post' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary') }} rounded-pill px-3 py-1 font-monospace" style="font-size: 0.75rem;">
                                {{ $result->typeLabel }}
                            </span>
                            @if($result->date)
                                <span class="text-muted small">{{ $result->date }}</span>
                            @endif
                        </div>
                        <h2 class="h5 fw-bold mb-2">
                            <a href="{{ url($result->url) }}" class="text-dark text-decoration-none">
                                {{ $result->title }}
                            </a>
                        </h2>
                        <p class="text-secondary small mb-3" style="line-height: 1.6;">
                            {!! $result->snippet !!}
                        </p>
                        <div>
                            <a href="{{ url($result->url) }}" class="btn btn-sm btn-link p-0 text-decoration-none text-primary fw-semibold">
                                {{ __('Consulter') }} &rarr;
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                        <i class="bi bi-search text-muted opacity-50 display-4 mb-3"></i>
                        <h4 class="fw-bold text-dark mb-1">{{ __('Aucun résultat trouvé') }}</h4>
                        <p class="text-muted small mb-3">
                            {{ __('Aucun contenu ne correspond exactement à vos termes de recherche.') }}
                        </p>
                        <div class="text-secondary small">
                            {{ __('Suggestions :') }}
                            <ul class="list-unstyled mt-2 text-muted">
                                <li>• {{ __('Vérifiez l\'orthographe des mots saisis.') }}</li>
                                <li>• {{ __('Essayez des termes plus généraux.') }}</li>
                                <li>• {{ __('Utilisez moins de mots-clés.') }}</li>
                            </ul>
                        </div>
                    </div>
                @endforelse
            </div>
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-search fs-1 opacity-25 d-block mb-3"></i>
                <p>{{ __('Saisissez un mot-clé ci-dessus pour lancer votre recherche.') }}</p>
            </div>
        @endif
    </main>
</body>
</html>
