<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Site en maintenance') }} — {{ $siteName ?? config('app.name', 'LCMS Core') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col items-center justify-center bg-[#050914] text-slate-100 antialiased font-sans p-6 selection:bg-blue-500 selection:text-white">
    <!-- Background Glow Effects -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-lg mx-auto text-center">
        <!-- Logo & Brand -->
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-tr from-blue-600/20 to-indigo-500/20 border border-blue-500/30 shadow-2xl shadow-blue-500/10 mb-6">
            <img src="{{ asset('imgs/icon-dark.png') }}" alt="LCMS Core Logo" class="w-12 h-12 object-contain">
        </div>

        <!-- Badges -->
        <div class="flex items-center justify-center gap-2 mb-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                {{ __('Maintenance en cours') }}
            </span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-mono text-slate-400 bg-slate-900 border border-slate-800">
                HTTP 503
            </span>
        </div>

        <!-- Main Card -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-8 sm:p-10 shadow-2xl backdrop-blur-xl mb-6">
            <h1 class="text-3xl font-extrabold text-white tracking-tight mb-3">
                {{ __('Site temporairement indisponible') }}
            </h1>
            <p class="text-slate-400 text-sm sm:text-base leading-relaxed mb-6">
                {{ $maintenanceMessage ?? __('Notre plateforme fait l\'objet d\'une mise à jour technique afin d\'améliorer votre expérience. Nous serons de retour dans les plus brefs délais.') }}
            </p>

            <div class="pt-6 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ __('Actualisation automatique possible') }}</span>
                </div>
                <button onclick="window.location.reload();" class="text-blue-400 hover:text-blue-300 font-semibold transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ __('Rafraîchir la page') }}
                </button>
            </div>
        </div>

        <!-- Footer / Admin access link -->
        <div class="flex items-center justify-center gap-4 text-xs text-slate-500">
            <a href="{{ route('login') }}" class="hover:text-slate-300 transition-colors inline-flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                {{ __('Espace Administration') }}
            </a>
            <span>•</span>
            <span class="text-slate-600">{{ $siteName ?? 'LCMS Core' }}</span>
        </div>
    </div>
</body>
</html>

