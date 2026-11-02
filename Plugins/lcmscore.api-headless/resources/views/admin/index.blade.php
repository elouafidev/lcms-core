@extends('core::admin.layouts.app')

@section('title', __('API REST & Jetons d\'Accès — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('API REST & Mode Headless') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Générez des jetons d\'accès sécurisés pour alimenter vos applications mobiles et frontends découplés') }}
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1"
                    data-bs-toggle="modal" data-bs-target="#createTokenModal">
                <i class="bi bi-key-fill"></i> {{ __('Créer un Jeton API') }}
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- One-time Token Display Banner --}}
    @if(session('new_plain_token'))
        <div class="alert alert-warning shadow border-warning-subtle rounded-4 p-4 mb-4">
            <div class="d-flex align-items-start gap-3">
                <div class="bg-warning text-dark rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1 text-dark">{{ __('Nouveau Jeton API créé :') }} {{ session('new_token_name') }}</h5>
                    <p class="small text-dark mb-3">
                        {{ __('Veuillez copier et stocker ce jeton en lieu sûr dès maintenant. Pour des raisons de sécurité, il ne sera plus jamais affiché.') }}
                    </p>
                    <div class="input-group">
                        <input type="text" id="newTokenInput" value="{{ session('new_plain_token') }}"
                               class="form-control font-monospace bg-white fw-bold text-dark" readonly>
                        <button class="btn btn-dark px-4" type="button" onclick="navigator.clipboard.writeText(document.getElementById('newTokenInput').value); alert('{{ __('Jeton copié dans le presse-papier !') }}');">
                            <i class="bi bi-clipboard me-1"></i> {{ __('Copier le jeton') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- KPI Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Jetons') }}</span>
                    <h3 class="fw-bold mb-0 text-dark">{{ $totalTokens }}</h3>
                </div>
                <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-key fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Jetons Valides') }}</span>
                    <h3 class="fw-bold mb-0 text-success">{{ $activeTokens }}</h3>
                </div>
                <div class="bg-success-subtle text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-shield-check fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Jetons Expirés') }}</span>
                    <h3 class="fw-bold mb-0 text-secondary">{{ $expiredTokens }}</h3>
                </div>
                <div class="bg-secondary-subtle text-secondary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-hourglass-bottom fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">{{ __('Statut API') }}</span>
                    <h4 class="fw-bold mb-0 text-success fs-6"><i class="bi bi-hdd-network-fill me-1"></i> REST v1 Actif</h4>
                    <span class="text-muted font-monospace" style="font-size: 0.72rem;">{{ url('/api/v1') }}</span>
                </div>
                <div class="bg-info-subtle text-info rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-cpu fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3">
            <ul class="nav nav-pills small">
                <li class="nav-item">
                    <a class="nav-link rounded-pill px-3 py-1 {{ $tab === 'tokens' ? 'active' : '' }}" href="{{ url('/admin/api-tokens?tab=tokens') }}">
                        <i class="bi bi-shield-lock me-1"></i> {{ __('Jetons d\'Accès') }} ({{ $totalTokens }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill px-3 py-1 {{ $tab === 'docs' ? 'active' : '' }}" href="{{ url('/admin/api-tokens?tab=docs') }}">
                        <i class="bi bi-journal-code me-1"></i> {{ __('Documentation & Endpoints REST') }}
                    </a>
                </li>
            </ul>
        </div>
    </div>

    @if($tab === 'tokens')
        <!-- Tokens Table -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light fs-7 text-uppercase text-muted">
                        <tr>
                            <th style="width: 30%;">{{ __('Client / Nom du Jeton') }}</th>
                            <th style="width: 20%;">{{ __('Permissions (Abilities)') }}</th>
                            <th style="width: 15%;">{{ __('Dernier Usage') }}</th>
                            <th style="width: 20%;">{{ __('Expiration') }}</th>
                            <th style="width: 15%;" class="text-end pe-3">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tokens as $tok)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $tok->name }}</div>
                                    <span class="text-muted font-monospace small" style="font-size: 0.75rem;">SHA256: {{ substr($tok->token, 0, 12) }}...</span>
                                </td>
                                <td>
                                    @if(in_array('*', $tok->abilities ?? [], true))
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace">Accès Complet (*)</span>
                                    @else
                                        @foreach($tok->abilities ?? [] as $ab)
                                            <span class="badge bg-light text-secondary border font-monospace me-1">{{ $ab }}</span>
                                        @endforeach
                                    @endif
                                </td>
                                <td>
                                    <div class="small text-muted">
                                        {{ $tok->last_used_at ? $tok->last_used_at->diffForHumans() : __('Jamais') }}
                                    </div>
                                </td>
                                <td>
                                    @if($tok->expires_at === null)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                            {{ __('Sans expiration') }}
                                        </span>
                                    @elseif($tok->isExpired())
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">
                                            {{ __('Expiré le') }} {{ $tok->expires_at->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 font-monospace">
                                            {{ $tok->expires_at->format('d/m/Y') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <form action="{{ url('/admin/api-tokens/'.$tok->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Révoquer immédiatement ce jeton API ? Les clients utilisant ce jeton perdront l\'accès.') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-trash"></i> {{ __('Révoquer') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-key fs-1 d-block mb-2 opacity-25"></i>
                                    <p class="mb-0">{{ __('Aucun jeton API configuré. Cliquez ci-dessus pour en créer un.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tokens->hasPages())
                <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                    {{ $tokens->links() }}
                </div>
            @endif
        </div>
    @else
        <!-- Interactive Documentation Tab -->
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
            <h4 class="fw-bold mb-3 text-dark">{{ __('Guide d\'Intégration & Spécification REST v1') }}</h4>
            <p class="text-muted small mb-4">
                {{ __('L\'API REST de LCMS Core vous permet de récupérer l\'intégralité des contenus pour vos frontends découplés (Next.js, Nuxt, Astro, SvelteKit) ou applications mobiles (Flutter, React Native).') }}
            </p>

            <div class="alert alert-light border rounded-3 p-3 mb-4">
                <span class="fw-semibold text-dark"><i class="bi bi-shield-lock me-1"></i> Authentification des requêtes :</span>
                <p class="small text-muted mb-2 mt-1">
                    {{ __('Passez votre jeton API dans l\'en-tête HTTP :') }}
                </p>
                <code class="d-block p-2 bg-dark text-white rounded font-monospace small">
                    Authorization: Bearer lcms_votre_jeton_secret...
                </code>
            </div>

            <div class="accordion" id="apiDocAccordion">
                <!-- Info -->
                <div class="accordion-item border rounded-3 mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#docInfo">
                            <span class="badge bg-primary font-monospace me-2">GET</span> /api/v1/info
                            <span class="text-muted ms-auto small me-3">{{ __('Métadonnées et statut du CMS') }}</span>
                        </button>
                    </h2>
                    <div id="docInfo" class="accordion-collapse collapse" data-bs-parent="#apiDocAccordion">
                        <div class="accordion-body bg-light">
                            <p class="small text-muted mb-2">{{ __('Retourne le statut de l\'API et la liste des modules disponibles.') }}</p>
                            <pre class="bg-dark text-white p-3 rounded font-monospace small mb-0">{ "success": true, "data": { "name": "LCMS Core", "api_version": "v1", "features": { "pages": true, "blog": true, "menus": true, "blocks": true } } }</pre>
                        </div>
                    </div>
                </div>

                <!-- Pages List -->
                <div class="accordion-item border rounded-3 mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#docPages">
                            <span class="badge bg-primary font-monospace me-2">GET</span> /api/v1/pages
                            <span class="text-muted ms-auto small me-3">{{ __('Liste des pages publiées (Paginée)') }}</span>
                        </button>
                    </h2>
                    <div id="docPages" class="accordion-collapse collapse" data-bs-parent="#apiDocAccordion">
                        <div class="accordion-body bg-light">
                            <p class="small text-muted mb-2">Paramètres optionnels : <code>?search=terme</code>, <code>?page=1</code></p>
                            <pre class="bg-dark text-white p-3 rounded font-monospace small mb-0">{ "success": true, "data": [ { "id": 1, "title": "Accueil", "slug": "accueil", "content": "..." } ], "meta": { "total": 1 } }</pre>
                        </div>
                    </div>
                </div>

                <!-- Single Page -->
                <div class="accordion-item border rounded-3 mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#docPage">
                            <span class="badge bg-primary font-monospace me-2">GET</span> /api/v1/pages/{slug}
                            <span class="text-muted ms-auto small me-3">{{ __('Détail d\'une page par son slug') }}</span>
                        </button>
                    </h2>
                    <div id="docPage" class="accordion-collapse collapse" data-bs-parent="#apiDocAccordion">
                        <div class="accordion-body bg-light">
                            <pre class="bg-dark text-white p-3 rounded font-monospace small mb-0">{ "success": true, "data": { "id": 1, "title": "À Propos", "slug": "a-propos", "content": "<p>Contenu...</p>" } }</pre>
                        </div>
                    </div>
                </div>

                <!-- Posts List -->
                <div class="accordion-item border rounded-3 mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#docPosts">
                            <span class="badge bg-primary font-monospace me-2">GET</span> /api/v1/posts
                            <span class="text-muted ms-auto small me-3">{{ __('Liste des articles de blog publiés') }}</span>
                        </button>
                    </h2>
                    <div id="docPosts" class="accordion-collapse collapse" data-bs-parent="#apiDocAccordion">
                        <div class="accordion-body bg-light">
                            <p class="small text-muted mb-2">Paramètres optionnels : <code>?category=slug</code>, <code>?search=mot-cle</code>, <code>?page=1</code></p>
                            <pre class="bg-dark text-white p-3 rounded font-monospace small mb-0">{ "success": true, "data": [ { "id": 1, "title": "Titre Article", "slug": "titre-article", "excerpt": "...", "reading_time": 3, "category": { "name": "Tech" } } ] }</pre>
                        </div>
                    </div>
                </div>

                <!-- Single Post -->
                <div class="accordion-item border rounded-3 mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#docPost">
                            <span class="badge bg-primary font-monospace me-2">GET</span> /api/v1/posts/{slug}
                            <span class="text-muted ms-auto small me-3">{{ __('Détail d\'un article de blog par son slug') }}</span>
                        </button>
                    </h2>
                    <div id="docPost" class="accordion-collapse collapse" data-bs-parent="#apiDocAccordion">
                        <div class="accordion-body bg-light">
                            <pre class="bg-dark text-white p-3 rounded font-monospace small mb-0">{ "success": true, "data": { "id": 1, "title": "Architecture", "slug": "architecture", "content": "Corps complet...", "category": { ... }, "author": { "name": "Admin" } } }</pre>
                        </div>
                    </div>
                </div>

                <!-- Menu -->
                <div class="accordion-item border rounded-3 mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#docMenu">
                            <span class="badge bg-primary font-monospace me-2">GET</span> /api/v1/menus/{slug}
                            <span class="text-muted ms-auto small me-3">{{ __('Arborescence d\'un menu de navigation') }}</span>
                        </button>
                    </h2>
                    <div id="docMenu" class="accordion-collapse collapse" data-bs-parent="#apiDocAccordion">
                        <div class="accordion-body bg-light">
                            <pre class="bg-dark text-white p-3 rounded font-monospace small mb-0">{ "success": true, "data": { "name": "Menu Principal", "slug": "main", "items": [ { "title": "Accueil", "url": "/" } ] } }</pre>
                        </div>
                    </div>
                </div>

                <!-- Block -->
                <div class="accordion-item border rounded-3 mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#docBlock">
                            <span class="badge bg-primary font-monospace me-2">GET</span> /api/v1/blocks/{slug}
                            <span class="text-muted ms-auto small me-3">{{ __('Données JSON d\'un bloc visuel') }}</span>
                        </button>
                    </h2>
                    <div id="docBlock" class="accordion-collapse collapse" data-bs-parent="#apiDocAccordion">
                        <div class="accordion-body bg-light">
                            <pre class="bg-dark text-white p-3 rounded font-monospace small mb-0">{ "success": true, "data": { "name": "Hero Accueil", "slug": "hero-accueil", "type": "hero", "content": { "title": "Bienvenue", "badge": "Nouveau" } } }</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal: Create API Token -->
    <div class="modal fade" id="createTokenModal" tabindex="-1" aria-labelledby="createTokenModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <form action="{{ url('/admin/api-tokens') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold fs-6" id="createTokenModalLabel">
                            <i class="bi bi-key-fill text-primary me-1"></i> {{ __('Créer un Jeton API') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">{{ __('Nom du client / Application') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="Ex: Application Mobile iOS, Frontend Next.js">
                            <div class="form-text small">{{ __('Ce nom vous permet d\'identifier facilement qui consomme l\'API.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">{{ __('Durée de validité (Expiration)') }}</label>
                            <select name="days_valid" class="form-select">
                                <option value="" selected>{{ __('Illimitée (Sans expiration)') }}</option>
                                <option value="30">{{ __('30 jours') }}</option>
                                <option value="90">{{ __('90 jours') }}</option>
                                <option value="180">{{ __('6 mois') }}</option>
                                <option value="365">{{ __('1 an') }}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">{{ __('Permissions accordées (Capacités)') }}</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="abilities[]" value="*" id="abAll" checked>
                                <label class="form-check-label small" for="abAll">
                                    <strong>Accès universel complet (*)</strong>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold">{{ __('Générer le jeton') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
