<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'LCMS Core') . ' — Authentification')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col bg-[#050914] text-slate-100 antialiased font-sans">
    <div id="app" class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="border-b border-slate-800 bg-[#050914]/80 backdrop-blur-md sticky top-0 z-50 px-6 py-4">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center gap-3 shrink-0 group">
                    <img src="{{ asset('imgs/icon-dark.png') }}" alt="LCMS Core Logo" class="w-10 h-10 rounded-xl shadow-lg border border-blue-500/20 group-hover:border-blue-500/50 transition-colors">
                    <span class="text-xl font-bold tracking-tight text-white whitespace-nowrap">
                        LCMS <span class="text-blue-500">Core</span>
                    </span>
                </a>

                <div class="flex items-center gap-4 text-sm font-medium">
                    @guest
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="text-slate-300 hover:text-white transition-colors">
                                {{ __('Connexion') }}
                            </a>
                        @endif

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-semibold transition-all">
                                {{ __('Inscription') }}
                            </a>
                        @endif
                    @else
                        <a href="{{ url('/admin/plugins') }}" class="text-slate-300 hover:text-white transition-colors">
                            Panneau d'administration
                        </a>
                        <span class="text-slate-500">•</span>
                        <span class="text-slate-300">{{ Auth::user()->name }}</span>
                        <a href="{{ route('logout') }}"
                           class="text-rose-400 hover:text-rose-300 transition-colors"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            {{ __('Déconnexion') }}
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    @endguest
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col justify-center items-center py-12 px-4 sm:px-6">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-800/80 py-6 text-center text-xs text-slate-500">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'LCMS Core') }} — Tous droits réservés.</p>
                <div class="flex items-center gap-4 text-slate-400">
                    <a href="{{ url('/') }}" class="hover:text-white transition-colors">Accueil</a>
                    <span>•</span>
                    <a href="{{ url('/admin/plugins') }}" class="hover:text-white transition-colors">Administration</a>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
