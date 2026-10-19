@inject('navigationRegistry', 'App\Core\Admin\NavigationRegistry')

@php
    $navItems = array_filter($navigationRegistry->getItems(), fn ($item) => $item->getId() !== 'themes-management');
@endphp

<aside class="admin-sidebar" style="width: 250px; background-color: #ffffff; border-right: 1px solid var(--border-color, #e2e8f0); min-height: 100vh; padding: 1.5rem 1rem; box-sizing: border-box; flex-shrink: 0;">
    <div class="sidebar-brand" style="font-weight: 700; font-size: 1.15rem; color: #0f172a; margin-bottom: 1.5rem; padding-left: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
        <span>⚡</span> <span>CMS Control</span>
    </div>

    <nav class="sidebar-nav">
        <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 0.5rem; padding-left: 0.5rem; letter-spacing: 0.05em;">
            Système
        </div>
        <ul style="list-style: none; padding: 0; margin: 0 0 1.5rem 0;">
            <li style="margin-bottom: 0.25rem;">
                <a href="{{ route('admin.plugins.index') }}" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; border-radius: 0.375rem; text-decoration: none; color: {{ request()->routeIs('admin.plugins.*') ? '#2563eb' : '#334155' }}; background-color: {{ request()->routeIs('admin.plugins.*') ? '#eff6ff' : 'transparent' }}; font-weight: {{ request()->routeIs('admin.plugins.*') ? '600' : '400' }}; font-size: 0.9rem;">
                    <span class="nav-icon">🧩</span>
                    <span class="nav-label">Gestion des Plugins</span>
                </a>
            </li>
            <li style="margin-bottom: 0.25rem;">
                <a href="{{ route('admin.themes.index') }}" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; border-radius: 0.375rem; text-decoration: none; color: {{ request()->routeIs('admin.themes.*') ? '#2563eb' : '#334155' }}; background-color: {{ request()->routeIs('admin.themes.*') ? '#eff6ff' : 'transparent' }}; font-weight: {{ request()->routeIs('admin.themes.*') ? '600' : '400' }}; font-size: 0.9rem;">
                    <span class="nav-icon">🎨</span>
                    <span class="nav-label">Gestion des Thèmes</span>
                </a>
            </li>
        </ul>

        @if(! empty($navItems))
            <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 0.5rem; padding-left: 0.5rem; letter-spacing: 0.05em;">
                Extensions & Modules
            </div>
            <ul class="plugin-nav-list" style="list-style: none; padding: 0; margin: 0;">
                @foreach($navItems as $item)
                    <li style="margin-bottom: 0.25rem;" data-plugin-id="{{ $item->getPluginId() ?? '' }}">
                        <a href="{{ $item->getUrl() }}" class="plugin-nav-link" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; border-radius: 0.375rem; text-decoration: none; color: {{ request()->url() === url($item->getUrl()) ? '#2563eb' : '#334155' }}; background-color: {{ request()->url() === url($item->getUrl()) ? '#eff6ff' : 'transparent' }}; font-weight: {{ request()->url() === url($item->getUrl()) ? '600' : '400' }}; font-size: 0.9rem;">
                            @if($item->getIcon())
                                <span class="nav-icon">{{ $item->getIcon() }}</span>
                            @else
                                <span class="nav-icon">📌</span>
                            @endif
                            <span class="nav-label">{{ $item->getLabel() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </nav>
</aside>
