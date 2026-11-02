<div class="lcms-newsletter-widget" style="max-width: 480px;">
    @if(session('newsletter_success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 p-3 small mb-3 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('newsletter_success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('newsletter_error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 p-3 small mb-3 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('newsletter_error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ url('/newsletter/subscribe') }}" method="POST" class="lcms-newsletter-form">
        @csrf
        {{-- Anti-Spam Honeypot Field --}}
        <input type="text" name="_hp_email" value="" style="display:none !important;" tabindex="-1" autocomplete="off">

        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted">
                <i class="bi bi-envelope"></i>
            </span>
            <input type="email" name="email" required
                   class="form-control border-start-0 ps-0"
                   placeholder="{{ __('Votre adresse e-mail...') }}"
                   aria-label="{{ __('Votre adresse e-mail') }}">
            <button type="submit" class="btn btn-primary px-4 fw-semibold">
                {{ __('S\'inscrire') }}
            </button>
        </div>
        <p class="text-muted small mt-2 mb-0" style="font-size: 0.78rem;">
            <i class="bi bi-shield-check me-1"></i> {{ __('Vos données sont protégées. Désinscription possible à tout moment.') }}
        </p>
    </form>
</div>
