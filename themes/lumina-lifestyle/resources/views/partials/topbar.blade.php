<div class="lumina-topbar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 small">
            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1">Info</span>
            <span>{{ __('Livraison neutre en carbone offerte dès 60 € • Fabrication artisanale certifiée') }}</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            @if(array_key_exists('langSwitcher', Illuminate\Support\Facades\Blade::getCustomDirectives()))
                <div class="d-inline-flex align-items-center">
                    @langSwitcher
                </div>
            @endif

            @auth
                <a href="{{ url('/admin') }}" class="small text-decoration-none text-light opacity-75 hover-opacity-100 d-inline-flex align-items-center gap-1">
                    <i class="bi bi-speedometer2"></i> {{ __('Administration') }}
                </a>
            @else
                <a href="{{ route('login') }}" class="small text-decoration-none text-light opacity-75 hover-opacity-100 d-inline-flex align-items-center gap-1">
                    <i class="bi bi-person"></i> {{ __('Connexion') }}
                </a>
            @endauth
        </div>
    </div>
</div>
