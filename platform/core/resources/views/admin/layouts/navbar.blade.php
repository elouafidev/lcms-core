@inject('safeModeManager', 'App\Core\Recovery\SafeModeManager')

<nav class="app-header navbar navbar-expand bg-body shadow-sm">
    <div class="container-fluid">
        <!-- Start Navbar Links -->
        <ul class="navbar-nav align-items-center">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Basculer le menu">
                    <i class="bi bi-list fs-4"></i>
                </a>
            </li>
            <li class="nav-item d-none d-md-block ms-2">
                <a href="{{ route('admin.dashboard') }}" class="nav-link fw-semibold">
                    <i class="bi bi-speedometer2 me-1"></i> Administration
                </a>
            </li>
            <li class="nav-item d-none d-md-block">
                <a href="{{ url('/') }}" class="nav-link text-muted" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Voir le site
                </a>
            </li>
        </ul>

        <!-- End Navbar Links -->
        <ul class="navbar-nav ms-auto align-items-center">
            @if($safeModeManager->isEnabled())
                <li class="nav-item me-2">
                    <span class="badge text-bg-warning p-2" title="Mode de secours actif : les extensions tierces sont neutralisées.">
                        <i class="bi bi-shield-exclamation me-1"></i> SAFE MODE ACTIF
                    </span>
                </li>
            @endif

            @auth
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span class="d-none d-md-inline fw-medium">{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold">{{ Auth::user()->name }}</div>
                            <small class="text-muted text-truncate d-block">{{ Auth::user()->email }}</small>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('admin.plugins.index') }}">
                                <i class="bi bi-puzzle me-2"></i> Extensions
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('admin.themes.index') }}">
                                <i class="bi bi-palette me-2"></i> Thèmes
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li class="p-2">
                            <form action="{{ route('logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            @endauth
        </ul>
    </div>
</nav>

