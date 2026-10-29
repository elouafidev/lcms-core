@inject('safeModeManager', 'App\Core\Recovery\SafeModeManager')

<nav class="app-header navbar navbar-expand bg-body shadow-sm">
    <div class="container-fluid">
        <!-- Start Navbar Links -->
        <ul class="navbar-nav align-items-center">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="{{ __('Toggle navigation') }}">
                    <i class="bi bi-list fs-4"></i>
                </a>
            </li>
            <li class="nav-item d-none d-md-block ms-2">
                <a href="{{ route('admin.dashboard') }}" class="nav-link fw-semibold">
                    <i class="bi bi-speedometer2 me-1 text-primary"></i> {{ __('Administration') }}
                </a>
            </li>
            <li class="nav-item d-none d-md-block">
                <a href="{{ url('/') }}" class="nav-link text-muted" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('View Website') }}
                </a>
            </li>
        </ul>

        <!-- End Navbar Links -->
        <ul class="navbar-nav ms-auto align-items-center">
            <!-- Language Switcher -->
            <li class="nav-item dropdown me-2">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-1 px-2.5 py-1 rounded-pill border border-slate-200 bg-light text-secondary fs-7 fw-semibold" href="#" data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration: none;">
                    <i class="bi bi-translate text-primary"></i>
                    <span class="text-uppercase">{{ app()->getLocale() }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 9rem; border-radius: 0.75rem;">
                    <li>
                        <a class="dropdown-item d-flex align-items-center justify-content-between py-2 {{ app()->getLocale() === 'fr' ? 'active fw-bold' : '' }}" href="{{ route('admin.locale', 'fr') }}">
                            <span>Français</span>
                            <span class="badge bg-secondary-subtle text-secondary fs-8">FR</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center justify-content-between py-2 {{ app()->getLocale() === 'en' ? 'active fw-bold' : '' }}" href="{{ route('admin.locale', 'en') }}">
                            <span>English</span>
                            <span class="badge bg-secondary-subtle text-secondary fs-8">EN</span>
                        </a>
                    </li>
                </ul>
            </li>

            @if($safeModeManager->isEnabled())
                <li class="nav-item me-2">
                    <span class="badge text-bg-warning p-2" title="{{ __('Safe mode active: third-party extensions are neutralized.') }}">
                        <i class="bi bi-shield-exclamation me-1"></i> {{ __('SAFE MODE ACTIVE') }}
                    </span>
                </li>
            @endif

            @auth
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 text-primary"></i>
                        <span class="d-none d-md-inline fw-medium text-dark">{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" style="border-radius: 0.75rem; border: 1px solid #e2e8f0;">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold text-dark">{{ Auth::user()->name }}</div>
                            <small class="text-muted text-truncate d-block">{{ Auth::user()->email }}</small>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('admin.settings.general') }}">
                                <i class="bi bi-person-gear me-2 text-primary"></i> {{ __('Mon compte') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('admin.plugins.index') }}">
                                <i class="bi bi-puzzle me-2 text-primary"></i> {{ __('Plugins') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('admin.themes.index') }}">
                                <i class="bi bi-palette me-2 text-primary"></i> {{ __('Themes') }}
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li class="p-2">
                            <form action="{{ route('logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-box-arrow-right"></i> {{ __('Logout') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            @endauth
        </ul>
    </div>
</nav>
