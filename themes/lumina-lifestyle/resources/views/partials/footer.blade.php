<footer class="lumina-footer">
    <div class="container">
        <div class="row g-4 mb-5">
            <!-- Col 1 : Brand & Mission -->
            <div class="col-lg-4 col-md-6">
                <h5 class="lumina-font-serif fw-bold mb-3 d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="28" height="28" fill="none">
                        <rect width="40" height="40" rx="8" fill="#c25e38"/>
                        <path d="M12 28V12L20 20L28 12V28" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Lumina Lifestyle</span>
                </h5>
                <p class="text-secondary small mb-3">
                    {{ __('Un magazine dédié au slow-living, à l\'artisanat d\'art et à la décoration durable, accompagné d\'une sélection soignée d\'objets façonnés pour durer.') }}
                </p>
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ url('/rss.xml') }}" class="small text-secondary hover-text-white d-inline-flex align-items-center gap-1" title="Flux RSS 2.0">
                        <i class="bi bi-rss-fill text-warning"></i> <span>Flux RSS</span>
                    </a>
                    <span class="text-secondary opacity-25">|</span>
                    <a href="{{ url('/api/v1/info') }}" class="small text-secondary hover-text-white d-inline-flex align-items-center gap-1" title="API REST">
                        <i class="bi bi-code-slash text-info"></i> <span>API REST v1</span>
                    </a>
                </div>
            </div>

            <!-- Col 2 : Menu & Liens Légaux -->
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-3 text-uppercase small letter-spacing-1 text-white">{{ __('Navigation') }}</h6>
                <ul class="list-unstyled d-flex flex-column gap-2 small">
                    <li><a href="{{ url('/') }}">{{ __('Accueil') }}</a></li>
                    <li><a href="{{ url('/shop') }}">{{ __('La Boutique') }}</a></li>
                    <li><a href="{{ url('/blog') }}">{{ __('Le Journal') }}</a></li>
                    <li><a href="{{ url('/page/a-propos') }}">{{ __('Notre Histoire') }}</a></li>
                    <li><a href="{{ url('/page/contact') }}">{{ __('Contact & Devis') }}</a></li>
                </ul>
            </div>

            <!-- Col 3 : Informations Légales via Hook Menu -->
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-3 text-uppercase small letter-spacing-1 text-white">{{ __('Engagements') }}</h6>
                @php
                    $renderedFooterMenu = \Core\Hooks\Facades\Hook::filter('cms.menu', '', 'footer-nav');
                @endphp
                @if(!empty(trim($renderedFooterMenu)))
                    {!! $renderedFooterMenu !!}
                @else
                    <ul class="list-unstyled d-flex flex-column gap-2 small">
                        <li><a href="{{ url('/page/mentions-legales') }}">{{ __('Mentions Légales') }}</a></li>
                        <li><a href="{{ url('/page/confidentialite') }}">{{ __('Confidentialité') }}</a></li>
                        <li><a href="{{ url('/page/cgv') }}">{{ __('Conditions Générales') }}</a></li>
                    </ul>
                @endif
            </div>

            <!-- Col 4 : Newsletter Integration -->
            <div class="col-lg-4 col-md-6">
                <h6 class="fw-bold mb-3 text-uppercase small letter-spacing-1 text-white">{{ __('Les Carnets de Saison') }}</h6>
                <p class="text-secondary small mb-3">
                    {{ __('Inscrivez-vous pour recevoir notre lettre mensuelle : inspirations, rencontres d\'artisans et éditions limitées.') }}
                </p>
                @if(array_key_exists('newsletterForm', Illuminate\Support\Facades\Blade::getCustomDirectives()))
                    <div class="lumina-newsletter-wrapper">
                        @newsletterForm
                    </div>
                @else
                    <form action="{{ url('/newsletter/subscribe') }}" method="POST" class="d-flex gap-2">
                        @csrf
                        <input type="email" name="email" required placeholder="votre.email@domaine.com" class="form-control form-control-sm bg-dark text-white border-secondary">
                        <button type="submit" class="btn btn-lumina-primary btn-sm rounded-pill px-3">{{ __('S\'inscrire') }}</button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Bottom Copyright -->
        <div class="pt-4 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-2 small text-secondary">
            <p class="mb-0">
                &copy; {{ date('Y') }} Lumina Lifestyle — {{ __('Propulsé par') }} <strong class="text-light">LCMS Core</strong>.
            </p>
            <div>
                {!! \Core\Hooks\Facades\Hook::filter('cms.footer', '') !!}
            </div>
        </div>
    </div>
</footer>
