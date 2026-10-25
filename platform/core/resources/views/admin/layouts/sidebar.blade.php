@inject('navigationRegistry', 'App\Core\Admin\NavigationRegistry')

@php
    $navItems = array_filter($navigationRegistry->getItems(), fn ($item) => $item->getId() !== 'themes-management');
@endphp

<aside class="app-sidebar admin-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!-- Sidebar Brand -->
    <div class="sidebar-brand">
        <a href="{{ route('admin.dashboard') }}" class="brand-link text-decoration-none d-flex align-items-center gap-2 py-3 px-3">
            <img src="{{ asset('imgs/icon-dark.png') }}" alt="LCMS Core Logo" style="width: 2.15rem; height: 2.15rem; border-radius: 0.6rem; border: 1px solid rgba(59, 130, 246, 0.3); object-fit: contain;">
            <span class="brand-text fw-bold fs-5 text-white tracking-tight">LCMS <span style="color: #3b82f6;">Core</span></span>
        </a>
    </div>

    <!-- Sidebar Wrapper -->
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <li class="nav-header text-uppercase fs-7 text-muted fw-bold px-3 pt-2 pb-1">
                    {{ __('System') }}
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.plugins.index') }}" class="nav-link {{ request()->routeIs('admin.plugins.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-puzzle"></i>
                        <p class="mb-0">{{ __('Plugins Management') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.themes.index') }}" class="nav-link {{ request()->routeIs('admin.themes.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-palette"></i>
                        <p class="mb-0">{{ __('Themes Management') }}</p>
                    </a>
                </li>

                @if(! empty($navItems))
                    <li class="nav-header text-uppercase fs-7 text-muted fw-bold px-3 pt-3 pb-1">
                        {{ __('Extensions & Modules') }}
                    </li>
                    @foreach($navItems as $item)
                        @php
                            $isActive = (request()->url() === url($item->getUrl()));
                            $icon = $item->getIcon();
                        @endphp
                        <li class="nav-item" data-plugin-id="{{ $item->getPluginId() ?? '' }}">
                            <a href="{{ $item->getUrl() }}" class="nav-link plugin-nav-link {{ $isActive ? 'active' : '' }}">
                                @if($icon && (str_starts_with($icon, 'bi ') || str_starts_with($icon, 'bi-')))
                                    <i class="nav-icon {{ $icon }}"></i>
                                @elseif($icon)
                                    <span class="nav-icon me-1">{{ $icon }}</span>
                                @else
                                    <i class="nav-icon bi bi-app-indicator"></i>
                                @endif
                                <p class="mb-0">{{ __($item->getLabel()) }}</p>
                            </a>
                        </li>
                    @endforeach
                @endif
            </ul>
        </nav>
    </div>
</aside>
