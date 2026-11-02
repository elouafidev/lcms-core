<header class="lumina-header py-3">
    <div class="container d-flex align-items-center justify-content-between gap-3">
        <!-- Brand Logo & Name -->
        <a href="{{ url('/') }}" class="lumina-brand">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="34" height="34" fill="none" class="shrink-0">
                <rect width="40" height="40" rx="10" fill="#c25e38"/>
                <path d="M12 28V12L20 20L28 12V28" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Lumina <span class="lumina-brand-accent">Lifestyle</span></span>
        </a>

        <!-- Desktop Navigation via Menu Plugin Hook or Fallback -->
        <nav class="d-none d-lg-flex align-items-center gap-1" aria-label="{{ __('Navigation principale') }}">
            @php
                $renderedMenu = \Core\Hooks\Facades\Hook::filter('cms.menu', '', 'main-nav');
            @endphp

            @if(!empty(trim($renderedMenu)))
                {!! $renderedMenu !!}
            @else
                <a href="{{ url('/') }}" class="lumina-nav-link {{ request()->is('/') ? 'active' : '' }}">{{ __('Accueil') }}</a>
                <a href="{{ url('/shop') }}" class="lumina-nav-link {{ request()->is('shop*') ? 'active' : '' }}">{{ __('Boutique') }}</a>
                <a href="{{ url('/blog') }}" class="lumina-nav-link {{ request()->is('blog*') ? 'active' : '' }}">{{ __('Le Journal') }}</a>
                <a href="{{ url('/page/a-propos') }}" class="lumina-nav-link {{ request()->is('page/a-propos') ? 'active' : '' }}">{{ __('Notre Histoire') }}</a>
                <a href="{{ url('/page/contact') }}" class="lumina-nav-link {{ request()->is('page/contact') ? 'active' : '' }}">{{ __('Contact') }}</a>
            @endif
        </nav>

        <!-- Right Quick Actions: Search, Cart, Mobile Toggle -->
        <div class="d-flex align-items-center gap-2">
            <!-- Global Search Component -->
            @if(array_key_exists('searchBar', Illuminate\Support\Facades\Blade::getCustomDirectives()))
                <div class="d-none d-sm-block" style="min-width: 200px;">
                    @searchBar
                </div>
            @endif

            <!-- Shopping Cart Button -->
            @if(array_key_exists('cartButton', Illuminate\Support\Facades\Blade::getCustomDirectives()))
                @cartButton
            @else
                <a href="{{ url('/cart') }}" class="btn btn-outline-secondary rounded-pill btn-sm px-3 d-inline-flex align-items-center gap-1">
                    <i class="bi bi-bag"></i> <span>{{ __('Panier') }}</span>
                </a>
            @endif

            <!-- Mobile Hamburger Toggle -->
            <button class="btn btn-light d-lg-none rounded-circle p-2 shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavDrawer" aria-controls="mobileNavDrawer" aria-label="{{ __('Ouvrir le menu mobile') }}">
                <i class="bi bi-list fs-5"></i>
            </button>
        </div>
    </div>
</header>
