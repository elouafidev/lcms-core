<div class="offcanvas offcanvas-end" tabindex="-1" id="mobileNavDrawer" aria-labelledby="mobileNavDrawerLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title lumina-font-serif fw-bold" id="mobileNavDrawerLabel">
            Lumina <span class="lumina-brand-accent">Lifestyle</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('Fermer') }}"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column justify-content-between">
        <div>
            <!-- Search bar on mobile -->
            @if(array_key_exists('searchBar', Illuminate\Support\Facades\Blade::getCustomDirectives()))
                <div class="mb-4">
                    @searchBar
                </div>
            @endif

            <nav class="nav flex-column gap-2" aria-label="{{ __('Navigation mobile') }}">
                <a href="{{ url('/') }}" class="lumina-nav-link {{ request()->is('/') ? 'active' : '' }}">
                    <i class="bi bi-house me-2"></i> {{ __('Accueil') }}
                </a>
                <a href="{{ url('/shop') }}" class="lumina-nav-link {{ request()->is('shop*') ? 'active' : '' }}">
                    <i class="bi bi-bag me-2"></i> {{ __('Boutique & Créations') }}
                </a>
                <a href="{{ url('/blog') }}" class="lumina-nav-link {{ request()->is('blog*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text me-2"></i> {{ __('Le Journal / Blog') }}
                </a>
                <a href="{{ url('/page/a-propos') }}" class="lumina-nav-link {{ request()->is('page/a-propos') ? 'active' : '' }}">
                    <i class="bi bi-info-circle me-2"></i> {{ __('Notre Histoire') }}
                </a>
                <a href="{{ url('/page/contact') }}" class="lumina-nav-link {{ request()->is('page/contact') ? 'active' : '' }}">
                    <i class="bi bi-envelope me-2"></i> {{ __('Contact') }}
                </a>
                <a href="{{ url('/cart') }}" class="lumina-nav-link {{ request()->is('cart') ? 'active' : '' }}">
                    <i class="bi bi-cart3 me-2"></i> {{ __('Panier d\'achats') }}
                </a>
            </nav>
        </div>

        <div class="border-top pt-3">
            @auth
                <a href="{{ url('/admin') }}" class="btn btn-outline-dark rounded-pill w-100 btn-sm mb-2">
                    <i class="bi bi-speedometer2 me-1"></i> {{ __('Administration') }}
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-lumina-primary rounded-pill w-100 btn-sm mb-2">
                    <i class="bi bi-person me-1"></i> {{ __('Espace Connexion') }}
                </a>
            @endauth
            <p class="text-muted small text-center mb-0">&copy; {{ date('Y') }} Lumina Lifestyle</p>
        </div>
    </div>
</div>
