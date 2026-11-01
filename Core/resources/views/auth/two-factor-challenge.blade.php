@extends('layouts.app')

@section('title', __('Two-Factor Authentication') . ' — ' . config('app.name', 'LCMS Core'))

@section('content')
<div class="w-full max-w-md mx-auto my-auto py-12">
    <!-- Header Logo & Title -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600/20 to-indigo-500/20 border border-blue-500/30 shadow-xl shadow-blue-500/10 mb-4">
            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight">{{ __('Two-Factor Authentication') }}</h1>
        <p class="text-sm text-slate-400 mt-1.5" id="auth-subtitle">
            {{ __('Please enter the 6-digit code from your authenticator application.') }}
        </p>
    </div>

    <!-- 2FA Card -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-7 sm:p-8 shadow-2xl backdrop-blur-xl">
        <form class="space-y-5" method="POST" action="{{ route('2fa.verify') }}">
            @csrf

            <!-- Code Input -->
            <div>
                <label for="code" class="block text-xs font-semibold text-slate-300 mb-2" id="code-label">
                    {{ __('Authentication Code (TOTP)') }}
                </label>
                <div class="relative">
                    <input 
                        id="code"
                        name="code"
                        type="text"
                        inputmode="numeric"
                        autofocus
                        autocomplete="one-time-code"
                        placeholder="123456"
                        class="w-full px-4 py-3 rounded-xl bg-[#050914] border @error('code') border-rose-500 @else border-slate-800 @enderror text-center font-mono text-xl tracking-widest text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 transition-colors"
                        required
                    >
                </div>
                @error('code')
                    <p class="mt-2 text-xs text-rose-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <strong>{{ $message }}</strong>
                    </p>
                @enderror
            </div>

            <!-- Submit Button -->
            <button 
                type="submit" 
                class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-sm shadow-lg shadow-blue-500/25 transition-all transform active:scale-[0.98] flex items-center justify-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>{{ __('Verify & Continue') }}</span>
            </button>

            <!-- Toggle Recovery Code Mode -->
            <div class="pt-4 border-t border-slate-800/80 text-center">
                <button 
                    type="button" 
                    id="toggle-recovery"
                    onclick="toggleRecoveryMode()"
                    class="text-xs text-blue-400 hover:text-blue-300 font-medium transition-colors"
                >
                    {{ __('Utiliser un code de récupération d’urgence') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Back to Login -->
    <div class="text-center mt-6">
        <a href="{{ route('login') }}" class="text-xs text-slate-500 hover:text-slate-300 transition-colors inline-flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            {{ __('Retour à la page de connexion') }}
        </a>
    </div>
</div>

<script>
    let isRecoveryMode = false;
    function toggleRecoveryMode() {
        isRecoveryMode = !isRecoveryMode;
        const input = document.getElementById('code');
        const label = document.getElementById('code-label');
        const subtitle = document.getElementById('auth-subtitle');
        const toggleBtn = document.getElementById('toggle-recovery');

        if (isRecoveryMode) {
            label.innerText = "{{ __('Emergency Recovery Code') }}";
            subtitle.innerText = "{{ __('Enter one of your emergency recovery codes (e.g. ABCDE-FGHIJ).') }}";
            input.placeholder = "XXXXX-XXXXX";
            input.setAttribute('inputmode', 'text');
            toggleBtn.innerText = "{{ __('Utiliser une application d’authentification (TOTP)') }}";
        } else {
            label.innerText = "{{ __('Authentication Code (TOTP)') }}";
            subtitle.innerText = "{{ __('Please enter the 6-digit code from your authenticator application.') }}";
            input.placeholder = "123456";
            input.setAttribute('inputmode', 'numeric');
            toggleBtn.innerText = "{{ __('Utiliser un code de récupération d’urgence') }}";
        }
        input.value = '';
        input.focus();
    }
</script>
@endsection

