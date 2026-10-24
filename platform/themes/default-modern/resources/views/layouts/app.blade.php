<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'LCMS Core') . ' — Plugin-First CMS')</title>

    <!-- Point d'ancrage Hook Core : injection de balises par lcmscore.seo -->
    {!! \App\Core\Hooks\Facades\Hook::filter('cms.head', '') !!}

    <link rel="stylesheet" href="{{ \App\Core\Themes\ThemeAssetManager::url('default-modern', 'css/theme.css') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
</head>
<body class="cms-theme-default-modern">
    <header class="cms-header">
        <div class="cms-container cms-header-inner">
            <a href="/" class="cms-brand">
                <img src="{{ \App\Core\Themes\ThemeAssetManager::url('default-modern', 'imgs/icon-dark.png') }}" alt="LCMS Core Logo" class="cms-logo-img">
                <span class="cms-brand-name">LCMS <span class="text-blue-500">Core</span></span>
            </a>

            <nav class="cms-navbar" aria-label="Navigation principale">
                {!! \App\Core\Hooks\Facades\Hook::filter('cms.menu', '', 'main-nav') !!}
            </nav>

            <div class="cms-auth-actions">
                @auth
                    <a href="{{ url('/admin/plugins') }}" class="cms-btn-auth">Administration</a>
                @else
                    <a href="{{ route('login') }}" class="cms-btn-auth">Connexion</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="cms-main">
        <div class="cms-container">
            @yield('content')
        </div>
    </main>

    <footer class="cms-footer">
        <div class="cms-container cms-footer-inner">
            <p class="cms-copyright">&copy; {{ date('Y') }} {{ config('app.name', 'LCMS Core') }} — Propulsé par LCMS Core.</p>
            {!! \App\Core\Hooks\Facades\Hook::filter('cms.footer', '') !!}
        </div>
    </footer>
</body>
</html>