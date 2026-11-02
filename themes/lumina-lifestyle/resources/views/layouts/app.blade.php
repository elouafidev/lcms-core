<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Lumina Lifestyle — Magazine Slow Living & Boutique Artisanale')</title>

    <!-- Point d'ancrage Hook Core : injection automatique des balises par lcmscore.seo & lcmscore.i18n -->
    {!! \Core\Hooks\Facades\Hook::filter('cms.head', '') !!}

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Feuille de style du thème Lumina Lifestyle -->
    <link rel="stylesheet" href="{{ \Core\Themes\ThemeAssetManager::url('lumina-lifestyle', 'css/lumina.css') }}">
</head>
<body class="lumina-theme">
    <!-- Barre supérieure d'annonces & sélecteur de langue -->
    @include('theme::partials.topbar')

    <!-- En-tête principal & Navigation -->
    @include('theme::partials.navbar')

    <!-- Tiroir de navigation mobile (Offcanvas) -->
    @include('theme::partials.mobile-nav')

    <!-- Notifications & Messages Flash globaux -->
    @if(session('success') || session('commerce_success') || session('newsletter_success'))
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                <div>
                    {{ session('success') ?? session('commerce_success') ?? session('newsletter_success') }}
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if(session('error') || session('commerce_error') || (isset($errors) && $errors->any()))
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                <div>
                    {{ session('error') ?? session('commerce_error') ?? 'Une erreur est survenue lors du traitement de votre requête.' }}
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <!-- Contenu Principal -->
    <main class="flex-grow-1">
        @yield('content')
    </main>

    <!-- Pied de page Universel -->
    @include('theme::partials.footer')

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
