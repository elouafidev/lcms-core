@extends('theme::layouts.app')

@section('title', $page->title . ' — Lumina Lifestyle')

@section('content')
    <div class="container py-5" style="max-width: 1040px;">
        <!-- Breadcrumbs -->
        <nav class="mb-4 small text-muted d-flex align-items-center gap-2">
            <a href="{{ url('/') }}" class="text-decoration-none text-muted">{{ __('Accueil') }}</a>
            <span>/</span>
            <span class="text-dark fw-semibold">{{ $page->title }}</span>
        </nav>

        <div class="row g-5">
            <!-- Left Column: Studio Info -->
            <div class="col-lg-5">
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 text-uppercase fw-bold small mb-2">
                    {{ __('Prendre Contact') }}
                </span>
                <h1 class="display-6 fw-bold lumina-font-serif text-dark mb-3">{{ $page->title }}</h1>
                <p class="text-muted mb-4">
                    {{ __('Une question sur nos créations artisanales, un projet sur mesure ou une commande spéciale ? Notre équipe d\'artisans vous répond sous 24 à 48 heures.') }}
                </p>

                <div class="d-flex flex-column gap-3 mb-4">
                    <div class="d-flex align-items-start gap-3 p-3 bg-white rounded-4 border">
                        <i class="bi bi-geo-alt text-primary fs-4 shrink-0"></i>
                        <div>
                            <strong class="d-block text-dark small">{{ __('Atelier & Galerie') }}</strong>
                            <span class="text-muted small">14 rue des Artisans, 75003 Paris, France</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 p-3 bg-white rounded-4 border">
                        <i class="bi bi-envelope text-primary fs-4 shrink-0"></i>
                        <div>
                            <strong class="d-block text-dark small">{{ __('Courrier Électronique') }}</strong>
                            <span class="text-muted small">bonjour@lumina-lifestyle.local</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 p-3 bg-white rounded-4 border">
                        <i class="bi bi-telephone text-primary fs-4 shrink-0"></i>
                        <div>
                            <strong class="d-block text-dark small">{{ __('Atelier Téléphonique') }}</strong>
                            <span class="text-muted small">+33 (0)1 42 68 00 00 (Du Mardi au Samedi)</span>
                        </div>
                    </div>
                </div>

                @if($page->content)
                    <div class="text-muted small">
                        {!! $page->content !!}
                    </div>
                @endif
            </div>

            <!-- Right Column: Interactive Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 p-md-5">
                    <h5 class="fw-bold mb-3 lumina-font-serif text-dark">{{ __('Envoyez-nous un message') }}</h5>
                    <p class="text-muted small mb-4">{{ __('Remplissez le formulaire ci-dessous. Tous les champs marqués d\'un astérisque sont requis.') }}</p>

                    @if(array_key_exists('renderForm', Illuminate\Support\Facades\Blade::getCustomDirectives()))
                        @renderForm('contact')
                    @else
                        <form action="{{ url('/forms/contact/submit') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">{{ __('Nom complet') }} *</label>
                                <input type="text" name="name" required class="form-control lumina-input">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">{{ __('Adresse email') }} *</label>
                                <input type="email" name="email" required class="form-control lumina-input">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">{{ __('Votre message') }} *</label>
                                <textarea name="message" required rows="4" class="form-control lumina-input"></textarea>
                            </div>
                            <button type="submit" class="btn btn-lumina-primary rounded-pill px-4">{{ __('Envoyer') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
