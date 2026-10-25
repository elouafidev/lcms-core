<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'LCMS Core') }} — {{ __('The Modular Plugin-First CMS') }}</title>

    <!-- Point d'ancrage Hook Core : injection de balises par lcmscore.seo -->
    {!! \App\Core\Hooks\Facades\Hook::filter('cms.head', '') !!}

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col bg-[#050914] text-slate-100 antialiased font-sans selection:bg-blue-600 selection:text-white">
    <!-- Header -->
    <header class="border-b border-slate-800/80 bg-[#050914]/90 backdrop-blur-md sticky top-0 z-50 px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            <a href="/" class="flex items-center gap-3 shrink-0 group">
                <img src="{{ asset('imgs/icon-dark.png') }}" alt="LCMS Core Logo" class="w-10 h-10 rounded-xl shadow-lg border border-blue-500/20 group-hover:border-blue-500/50 transition-colors">
                <span class="text-xl font-bold tracking-tight text-white whitespace-nowrap">
                    LCMS <span class="text-blue-500">Core</span>
                </span>
            </a>

            <!-- Nav Links -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-400">
                <a href="#features" class="hover:text-white transition-colors">{{ __('Architecture Pillars') }}</a>
                <a href="#plugins" class="hover:text-white transition-colors">{{ __('Integrated Plugins') }}</a>
                <a href="#status" class="hover:text-white transition-colors">{{ __('System Status') }}</a>
            </nav>

            <div class="flex items-center gap-3">
                <!-- Language Switcher -->
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-800 bg-slate-900/60 text-xs text-slate-400">
                    <a href="{{ route('locale.switch', 'fr') }}" class="{{ app()->getLocale() === 'fr' ? 'text-blue-400 font-bold' : 'hover:text-white transition-colors' }}">FR</a>
                    <span class="text-slate-700">|</span>
                    <a href="{{ route('locale.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'text-blue-400 font-bold' : 'hover:text-white transition-colors' }}">EN</a>
                </div>

                @auth
                    <a href="{{ url('/admin/plugins') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-semibold transition-all shadow-md shadow-blue-600/30 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span>{{ __('Administration') }}</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-semibold transition-all shadow-md shadow-blue-600/30 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        <span>{{ __('Login') }}</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1">
        <section class="relative py-20 lg:py-28 px-6 text-center overflow-hidden">
            <!-- Glow background effects -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[350px] bg-blue-600/10 blur-[130px] rounded-full pointer-events-none -z-10"></div>
            <div class="absolute top-1/3 left-1/4 w-[300px] h-[250px] bg-indigo-600/10 blur-[100px] rounded-full pointer-events-none -z-10"></div>

            <div class="max-w-4xl mx-auto">
                <!-- Pill Badge -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-medium bg-blue-950/60 text-blue-300 border border-blue-800/50 shadow-sm mb-6">
                    <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                    <span>{{ __('Lean Core Architecture & Modularity') }} • Laravel 11/12 & AdminLTE 4</span>
                </div>

                <!-- Main Title -->
                <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold text-white tracking-tight leading-tight">
                    {{ __('The Modular CMS') }} <br>
                    <span class="bg-gradient-to-r from-blue-400 via-indigo-300 to-blue-500 bg-clip-text text-transparent">
                        Plugin-First
                    </span>
                </h1>

                <!-- Subtitle -->
                <p class="text-base sm:text-xl text-slate-400 mt-6 max-w-2xl mx-auto leading-relaxed">
                    {{ __('Absolute logical isolation, deterministic topological ordering of plugins, and an elegant and ergonomic administration interface.') }}
                </p>

                <!-- CTA Buttons -->
                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    <a href="{{ url('/admin/plugins') }}" class="px-6 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-xl shadow-blue-600/30 transition-all flex items-center gap-2.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>{{ __('Administration Panel') }}</span>
                    </a>
                    <a href="{{ route('login') }}" class="px-6 py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800 font-semibold text-sm transition-all flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span>{{ __('Login Space') }}</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Features Grid -->
        <section id="features" class="py-16 px-6 max-w-7xl mx-auto border-t border-slate-900">
            <div class="text-center mb-12">
                <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">{{ __('4 Core Engineering Pillars') }}</h2>
                <p class="text-sm text-slate-400 mt-2">{{ __('An architecture respecting the highest standards of software modularity and security.') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Card 1 -->
                <div class="bg-slate-900/40 border border-slate-800/80 rounded-2xl p-6 hover:border-blue-500/40 transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-blue-600/10 border border-blue-500/20 text-blue-400 flex items-center justify-center text-xl mb-4 group-hover:scale-105 transition-transform">
                        🧩
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">{{ __('Lean Core') }}</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        {{ __('No hardcoded business logic in the core. The core only handles lifecycle, security, and public contracts.') }}
                    </p>
                </div>

                <!-- Card 2 -->
                <div class="bg-slate-900/40 border border-slate-800/80 rounded-2xl p-6 hover:border-blue-500/40 transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl mb-4 group-hover:scale-105 transition-transform">
                        ⚡
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">{{ __('Plugin Engine & Kahn') }}</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        {{ __('Deterministic ordering via Kahn topological algorithm. Isolated database migrations and tables per extension.') }}
                    </p>
                </div>

                <!-- Card 3 -->
                <div class="bg-slate-900/40 border border-slate-800/80 rounded-2xl p-6 hover:border-blue-500/40 transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-purple-600/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-xl mb-4 group-hover:scale-105 transition-transform">
                        🎨
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">{{ __('Decoupled Theme Engine') }}</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        {{ __('Dynamic Blade overriding cascade, secure ZIP importer and front-end customization without modifying source code.') }}
                    </p>
                </div>

                <!-- Card 4 -->
                <div class="bg-slate-900/40 border border-slate-800/80 rounded-2xl p-6 hover:border-blue-500/40 transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-600/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl mb-4 group-hover:scale-105 transition-transform">
                        🛡️
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">{{ __('Resilience & Safe Mode') }}</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        {{ __('Protection against fatal errors: targeted quarantine of faulty extensions and command-line recovery mode.') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Extensions intégrées Section -->
        <section id="plugins" class="py-16 px-6 max-w-7xl mx-auto border-t border-slate-900">
            <div class="text-center mb-12">
                <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">{{ __('Ready-to-Use Core Extensions') }}</h2>
                <p class="text-sm text-slate-400 mt-2">{{ __('Each feature is packaged according to strict LCMS Core specifications.') }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-xl bg-slate-900/30 border border-slate-800">
                    <span class="text-xs font-mono text-blue-400">lcmscore.pages</span>
                    <h4 class="font-bold text-white text-base mt-1">{{ __('Static Pages') }}</h4>
                    <p class="text-xs text-slate-400 mt-1.5">{{ __('Editorial content management, publishing and dynamic routing with publication control.') }}</p>
                </div>

                <div class="p-5 rounded-xl bg-slate-900/30 border border-slate-800">
                    <span class="text-xs font-mono text-indigo-400">lcmscore.menus</span>
                    <h4 class="font-bold text-white text-base mt-1">{{ __('Dynamic Menus') }}</h4>
                    <p class="text-xs text-slate-400 mt-1.5">{{ __('Multi-level navigation tree and automatic injection via Hook filters.') }}</p>
                </div>

                <div class="p-5 rounded-xl bg-slate-900/30 border border-slate-800">
                    <span class="text-xs font-mono text-emerald-400">lcmscore.media-library</span>
                    <h4 class="font-bold text-white text-base mt-1">{{ __('Media Library') }}</h4>
                    <p class="text-xs text-slate-400 mt-1.5">{{ __('Secure file uploads and public MediaStorageInterface contract.') }}</p>
                </div>

                <div class="p-5 rounded-xl bg-slate-900/30 border border-slate-800">
                    <span class="text-xs font-mono text-amber-400">lcmscore.seo</span>
                    <h4 class="font-bold text-white text-base mt-1">{{ __('SEO Engine') }}</h4>
                    <p class="text-xs text-slate-400 mt-1.5">{{ __('Meta tags, OpenGraph and canonical URLs injected via the Hook interception bus.') }}</p>
                </div>
            </div>
        </section>

        <!-- System Status Banner -->
        <section id="status" class="py-12 px-6 max-w-5xl mx-auto">
            <div class="p-6 rounded-2xl bg-gradient-to-r from-blue-950/30 via-slate-900/60 to-indigo-950/30 border border-blue-900/40 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <div>
                        <div class="text-sm font-bold text-white">{{ __('System Operational & Compliant') }}</div>
                        <div class="text-xs text-slate-400">PHP 8.3+ • Laravel 11/12 • AdminLTE 4 • {{ __('100% Tests Passing') }}</div>
                    </div>
                </div>
                <a href="{{ url('/admin/plugins') }}" class="px-4 py-2 rounded-xl bg-blue-600/30 hover:bg-blue-600/50 text-blue-300 text-xs font-semibold border border-blue-500/30 transition-colors whitespace-nowrap">
                    {{ __('Open Administration →') }}
                </a>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-900 py-8 px-6 text-slate-500 text-xs">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <img src="{{ asset('imgs/icon-dark.png') }}" alt="LCMS Core" class="w-5 h-5 rounded">
                <span>&copy; {{ date('Y') }} {{ config('app.name', 'LCMS Core') }} — {{ __('All rights reserved.') }}</span>
            </div>
            <div class="flex items-center gap-4 text-slate-400">
                <a href="{{ url('/') }}" class="hover:text-white transition-colors">{{ __('Home') }}</a>
                <span>•</span>
                <a href="{{ route('login') }}" class="hover:text-white transition-colors">{{ __('Login') }}</a>
                <span>•</span>
                <a href="{{ url('/admin/plugins') }}" class="hover:text-white transition-colors">{{ __('Administration') }}</a>
            </div>
        </div>
    </footer>
</body>
</html>
