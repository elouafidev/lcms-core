@extends('layouts.app')

@section('title', 'Connexion Administrateur — ' . config('app.name', 'LCMS Core'))

@section('content')
<div class="w-full max-w-md mx-auto">
    <!-- Header Logo & Title -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600/20 to-indigo-500/20 border border-blue-500/30 shadow-xl shadow-blue-500/10 mb-4">
            <img src="{{ asset('imgs/icon-dark.png') }}" alt="LCMS Core Logo" class="w-10 h-10 object-contain">
        </div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight">Connexion Administrateur</h1>
        <p class="text-sm text-slate-400 mt-1.5">Accédez au panneau de contrôle centralisé de LCMS Core</p>
    </div>

    <!-- Login Card -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-7 sm:p-8 shadow-2xl backdrop-blur-xl">
        <form class="space-y-4" method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email Input -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    Adresse e-mail de l'administrateur
                </label>
                <div class="relative">
                    <input 
                        id="email"
                        name="email"
                        type="email"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="admin@lcms.local" 
                        value="{{ old('email', 'admin@lcms.local') }}"
                        class="w-full px-4 py-2.5 rounded-xl bg-[#050914] border @error('email') border-rose-500 @else border-slate-800 @enderror text-sm text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 transition-colors"
                    >
                </div>
                @error('email')
                    <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <strong>{{ $message }}</strong>
                    </p>
                @enderror
            </div>

            <!-- Password Input -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-semibold text-slate-300">
                        Mot de passe
                    </label>
                </div>
                <div class="relative">
                    <input 
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••••••" 
                        class="w-full px-4 py-2.5 rounded-xl bg-[#050914] border @error('password') border-rose-500 @else border-slate-800 @enderror text-sm text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 transition-colors pr-11"
                    >
                    <button 
                        type="button" 
                        onclick="const p = document.getElementById('password'); const icon = document.getElementById('eye-icon'); if (p.type === 'password') { p.type = 'text'; icon.classList.add('text-blue-400'); } else { p.type = 'password'; icon.classList.remove('text-blue-400'); }"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-500 hover:text-slate-300 cursor-pointer focus:outline-none"
                        aria-label="Afficher ou masquer le mot de passe"
                    >
                        <svg id="eye-icon" class="w-4 h-4 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <strong>{{ $message }}</strong>
                    </p>
                @enderror
            </div>

            <!-- Remember me & Forgot Password -->
            <div class="flex items-center justify-between pt-1">
                <label for="remember" class="flex items-center gap-2 cursor-pointer select-none">
                    <input 
                        id="remember" 
                        name="remember" 
                        type="checkbox" 
                        class="w-4 h-4 rounded bg-[#050914] border border-slate-700 text-blue-600 focus:ring-0 focus:ring-offset-0 cursor-pointer"
                        {{ old('remember') ? 'checked' : '' }}
                    >
                    <span class="text-xs text-slate-400 hover:text-slate-300">Se souvenir de moi</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-blue-400 hover:text-blue-300 font-semibold transition-colors">
                        Mot de passe oublié ?
                    </a>
                @endif
            </div>

            <!-- Submit Button -->
            <button 
                type="submit" 
                class="w-full mt-2 py-3 px-4 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 active:bg-blue-700 shadow-lg shadow-blue-600/30 transition-all cursor-pointer flex items-center justify-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                <span>Se connecter</span>
            </button>
        </form>

        <!-- Admin Helper Note -->
        <div class="mt-6 pt-5 border-t border-slate-800/80">
            <div class="p-3 rounded-xl bg-blue-950/20 border border-blue-900/30 flex items-start gap-2.5 text-left">
                <svg class="w-4 h-4 text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-xs text-slate-400 leading-relaxed">
                    Identifiant racine par défaut : <code class="px-1.5 py-0.5 rounded bg-blue-950/60 text-blue-300 font-mono text-[11px] border border-blue-800/40">admin@lcms.local</code>
                </div>
            </div>
            <div class="mt-4 text-center">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-300 transition-colors">
                    <span>←</span> Retour à l'accueil du site
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
