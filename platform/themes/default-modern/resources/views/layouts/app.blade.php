<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'LCMS Core'))</title>

    {!! \App\Core\Hooks\Facades\Hook::filter('cms.head', '') !!}

    <link rel="stylesheet" href="{{ \App\Core\Themes\ThemeAssetManager::url('default-modern', 'css/theme.css') }}">
</head>
<body class="cms-theme-default-modern">
    <header class="cms-header">
        <div class="cms-container cms-header-inner">
            <a href="/" class="cms-brand">
                <span class="cms-logo-icon">❖</span>
                <span class="cms-brand-name">{{ config('app.name', 'LCMS Core') }}</span>
            </a>

            <nav class="cms-navbar" aria-label="Navigation principale">
                {!! \App\Core\Hooks\Facades\Hook::filter('cms.menu', '', 'main-nav') !!}
            </nav>
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

