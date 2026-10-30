@inject('navigationRegistry', 'App\Core\Admin\NavigationRegistry')

@php
    $navItems = $navigationRegistry->getItems();
    $isSettingsActive = request()->routeIs('admin.settings.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.users.*');
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
                    {{ __('Plateforme') }}
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.plugins.index') }}" class="nav-link {{ request()->routeIs('admin.plugins.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-puzzle"></i>
                        <p class="mb-0">{{ __('Gestion des Plugins') }}</p>
                    </a>
                </li>
                <!-- Appearance Treeview Menu -->
                @php
                    $isAppearanceActive = request()->routeIs('admin.themes.*') || request()->routeIs('admin.appearance.*');
                @endphp
                <li class="nav-item {{ $isAppearanceActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isAppearanceActive ? 'active' : '' }}">
                        <i class="nav-icon bi bi-palette"></i>
                        <p class="mb-0">
                            {{ __('Apparence') }}
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview {{ $isAppearanceActive ? 'd-block' : '' }}">
                        <li class="nav-item">
                            <a href="{{ route('admin.themes.index') }}" class="nav-link {{ request()->routeIs('admin.themes.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-collection"></i>
                                <p class="mb-0">{{ __('Gestion des Thèmes') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.appearance.customize') }}" class="nav-link {{ request()->routeIs('admin.appearance.customize*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-brush"></i>
                                <p class="mb-0">{{ __('Personnaliser apparence') }}</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Settings Treeview Menu -->
                <li class="nav-item {{ $isSettingsActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isSettingsActive ? 'active' : '' }}">
                        <i class="nav-icon bi bi-gear"></i>
                        <p class="mb-0">
                            {{ __('Paramètres') }}
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview {{ $isSettingsActive ? 'd-block' : '' }}">
                        <li class="nav-item">
                            <a href="{{ route('admin.settings.general') }}" class="nav-link {{ request()->routeIs('admin.settings.general*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-person-gear"></i>
                                <p class="mb-0">{{ __('Paramètres généraux') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.settings.site') }}" class="nav-link {{ request()->routeIs('admin.settings.site*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-sliders"></i>
                                <p class="mb-0">{{ __('Paramètres du site') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-shield-lock"></i>
                                <p class="mb-0">{{ __('Gestion des rôles') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-people"></i>
                                <p class="mb-0">{{ __('Gestion des comptes') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.settings.update') }}" class="nav-link {{ request()->routeIs('admin.settings.update*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-arrow-repeat"></i>
                                <p class="mb-0">{{ __('Mise à jour') }}</p>
                            </a>
                        </li>
                    </ul>
                </li>

                @if(! empty($navItems))
                    <li class="nav-header text-uppercase fs-7 text-muted fw-bold px-3 pt-3 pb-1">
                        {{ __('Extensions & Modules') }}
                    </li>
                    @foreach($navItems as $item)
                        @php
                            $children = $item->getChildren();
                            $hasChildren = ! empty($children);
                            $isActive = (request()->url() === url($item->getUrl()));
                            $icon = $item->getIcon();
                        @endphp
                        @if($hasChildren)
                            <li class="nav-item" data-plugin-id="{{ $item->getPluginId() ?? '' }}">
                                <a href="#" class="nav-link">
                                    @if($icon && (str_starts_with($icon, 'bi ') || str_starts_with($icon, 'bi-')))
                                        <i class="nav-icon {{ $icon }}"></i>
                                    @else
                                        <i class="nav-icon bi bi-folder"></i>
                                    @endif
                                    <p class="mb-0">
                                        {{ __($item->getLabel()) }}
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @foreach($children as $child)
                                        <li class="nav-item">
                                            <a href="{{ $child->getUrl() }}" class="nav-link {{ request()->url() === url($child->getUrl()) ? 'active' : '' }}">
                                                <i class="nav-icon {{ $child->getIcon() ?: 'bi bi-circle' }}"></i>
                                                <p class="mb-0">{{ __($child->getLabel()) }}</p>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
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
                        @endif
                    @endforeach
                @endif
            </ul>
        </nav>
    </div>
</aside>
