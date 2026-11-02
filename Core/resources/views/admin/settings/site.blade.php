@extends('core::admin.layouts.app')

@section('title', 'Administration — ' . __('Paramètres du site'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Paramètres du site') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Configuration générale de LCMS Core, serveur de messagerie SMTP et diagnostics système.') }}
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Settings Navigation Tabs -->
    <ul class="nav nav-pills mb-4 gap-2" id="siteSettingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="tab-general" data-bs-toggle="pill" data-bs-target="#content-general" type="button" role="tab">
                <i class="bi bi-sliders"></i> {{ __('Général') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="tab-smtp" data-bs-toggle="pill" data-bs-target="#content-smtp" type="button" role="tab">
                <i class="bi bi-envelope-at"></i> {{ __('Configuration SMTP') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="tab-storage-cache" data-bs-toggle="pill" data-bs-target="#content-storage-cache" type="button" role="tab">
                <i class="bi bi-hdd-network"></i> {{ __('Stockage & Cache') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="tab-diagnostics" data-bs-toggle="pill" data-bs-target="#content-diagnostics" type="button" role="tab">
                <i class="bi bi-cpu"></i> {{ __('Diagnostic & Versions') }}
            </button>
        </li>
    </ul>

    <div class="tab-content" id="siteSettingsContent">
        <!-- Tab 1: General Settings -->
        <div class="tab-pane fade show active" id="content-general" role="tabpanel">
            <div class="card shadow-sm border-0" style="border-radius: 1rem;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-sliders text-primary me-2"></i> {{ __('Informations générales du CMS') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.settings.site.general') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="site_name" class="form-label fw-semibold small text-secondary">{{ __('Nom du site / Plateforme') }}</label>
                                <input type="text" id="site_name" name="site_name" class="form-control" value="{{ old('site_name', $settings['site_name'] ?? config('app.name', 'LCMS Core')) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label for="site_url" class="form-label fw-semibold small text-secondary">{{ __('URL publique du site') }}</label>
                                <input type="url" id="site_url" name="site_url" class="form-control" value="{{ old('site_url', $settings['site_url'] ?? config('app.url', 'http://localhost')) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label for="site_timezone" class="form-label fw-semibold small text-secondary">{{ __('Fuseau horaire') }}</label>
                                <select id="site_timezone" name="site_timezone" class="form-select">
                                    @php
                                        $selectedTz = old('site_timezone', $settings['site_timezone'] ?? config('app.timezone', 'UTC'));
                                    @endphp
                                    @foreach(timezone_identifiers_list() as $tz)
                                        <option value="{{ $tz }}" {{ $selectedTz === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="site_locale" class="form-label fw-semibold small text-secondary">{{ __('Langue par défaut') }}</label>
                                @php
                                    $selectedLocale = old('site_locale', $settings['site_locale'] ?? config('app.locale', 'fr'));
                                @endphp
                                <select id="site_locale" name="site_locale" class="form-select">
                                    <option value="fr" {{ $selectedLocale === 'fr' ? 'selected' : '' }}>Français (fr)</option>
                                    <option value="en" {{ $selectedLocale === 'en' ? 'selected' : '' }}>English (en)</option>
                                </select>
                            </div>

                            <div class="col-12 mt-4">
                                <div class="form-check form-switch p-0 d-flex align-items-center gap-3 mb-2">
                                    <input class="form-check-input ms-0 mt-0" type="checkbox" role="switch" id="maintenance_mode" name="maintenance_mode" value="1" {{ !empty($settings['maintenance_mode']) ? 'checked' : '' }} style="width: 2.5rem; height: 1.3rem;">
                                    <label class="form-check-label fw-semibold text-dark" for="maintenance_mode">
                                        {{ __('Mode Maintenance activé') }}
                                        <span class="d-block small text-muted fw-normal">{{ __('Affiche un écran de maintenance aux visiteurs publics non authentifiés.') }}</span>
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="maintenance_message" class="form-label fw-semibold small text-secondary">{{ __('Message personnalisé de maintenance (Optionnel)') }}</label>
                                <textarea id="maintenance_message" name="maintenance_message" class="form-control" rows="2" placeholder="Notre site est actuellement en maintenance pour des améliorations techniques...">{{ old('maintenance_message', $settings['maintenance_message'] ?? '') }}</textarea>
                                <div class="form-text small">{{ __('Ce message sera affiché au centre de la page de maintenance aux visiteurs publics.') }}</div>
                            </div>

                            <div class="col-12 text-end mt-4">
                                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                    <i class="bi bi-save me-1"></i> {{ __('Enregistrer les paramètres généraux') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tab 2: SMTP Settings -->
        <div class="tab-pane fade" id="content-smtp" role="tabpanel">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-envelope-at text-primary me-2"></i> {{ __('Configuration du serveur SMTP') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.settings.site.smtp') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="mail_host" class="form-label fw-semibold small text-secondary">{{ __('Hôte SMTP (Serveur)') }}</label>
                                <input type="text" id="mail_host" name="mail_host" class="form-control" value="{{ old('mail_host', $settings['mail_host'] ?? config('mail.mailers.smtp.host', '127.0.0.1')) }}" required>
                            </div>

                            <div class="col-md-2">
                                <label for="mail_port" class="form-label fw-semibold small text-secondary">{{ __('Port SMTP') }}</label>
                                <input type="number" id="mail_port" name="mail_port" class="form-control" value="{{ old('mail_port', $settings['mail_port'] ?? config('mail.mailers.smtp.port', 587)) }}" required>
                            </div>

                            <div class="col-md-2">
                                <label for="mail_encryption" class="form-label fw-semibold small text-secondary">{{ __('Chiffrement') }}</label>
                                @php
                                    $selectedEnc = old('mail_encryption', $settings['mail_encryption'] ?? config('mail.mailers.smtp.encryption', 'tls'));
                                @endphp
                                <select id="mail_encryption" name="mail_encryption" class="form-select">
                                    <option value="tls" {{ $selectedEnc === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ $selectedEnc === 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="none" {{ empty($selectedEnc) || $selectedEnc === 'none' ? 'selected' : '' }}>Aucun</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="mail_username" class="form-label fw-semibold small text-secondary">{{ __('Identifiant / Utilisateur SMTP') }}</label>
                                <input type="text" id="mail_username" name="mail_username" class="form-control" value="{{ old('mail_username', $settings['mail_username'] ?? config('mail.mailers.smtp.username')) }}">
                            </div>

                            <div class="col-md-6">
                                <label for="mail_password" class="form-label fw-semibold small text-secondary">{{ __('Mot de passe SMTP') }}</label>
                                <input type="password" id="mail_password" name="mail_password" class="form-control" placeholder="{{ !empty($settings['mail_password']) ? '•••••••••••• (Inchangé)' : 'Saisir le mot de passe' }}">
                            </div>

                            <div class="col-md-6">
                                <label for="mail_from_address" class="form-label fw-semibold small text-secondary">{{ __('Adresse e-mail expéditeur (From)') }}</label>
                                <input type="email" id="mail_from_address" name="mail_from_address" class="form-control" value="{{ old('mail_from_address', $settings['mail_from_address'] ?? config('mail.from.address', 'noreply@lcms.local')) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label for="mail_from_name" class="form-label fw-semibold small text-secondary">{{ __('Nom expéditeur affiché') }}</label>
                                <input type="text" id="mail_from_name" name="mail_from_name" class="form-control" value="{{ old('mail_from_name', $settings['mail_from_name'] ?? config('mail.from.name', 'LCMS Core')) }}" required>
                            </div>

                            <div class="col-12 text-end mt-4">
                                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                    <i class="bi bi-save me-1"></i> {{ __('Enregistrer la configuration SMTP') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card Test SMTP Live -->
            <div class="card shadow-sm border-0" style="border-radius: 1rem;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-send-check text-success me-2"></i> {{ __('Test d\'envoi en direct') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-3">
                        {{ __('Vérifiez immédiatement la connectivité et la configuration SMTP en envoyant un e-mail de test :') }}
                    </p>
                    <div class="row g-2 align-items-center" style="max-width: 600px;">
                        <div class="col-sm-8">
                            <input type="email" id="test_recipient_email" class="form-control" placeholder="destinataire@example.com" value="{{ auth()->user()->email ?? '' }}">
                        </div>
                        <div class="col-sm-4">
                            <button type="button" class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2" id="btn-test-smtp">
                                <i class="bi bi-send"></i> {{ __('Tester l\'envoi') }}
                            </button>
                        </div>
                    </div>
                    <div id="test-smtp-result" class="alert mt-3 d-none"></div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Environment Diagnostics -->
        <div class="tab-pane fade" id="content-diagnostics" role="tabpanel">
            <div class="card shadow-sm border-0" style="border-radius: 1rem;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-cpu text-primary me-2"></i> {{ __('État du serveur et versions du CMS') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-secondary text-uppercase fs-7 mb-3">{{ __('Environnement d\'exécution') }}</h6>
                            <ul class="list-group list-group-flush border rounded">
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                                    <span class="text-muted">{{ __('Version LCMS Core') }}</span>
                                    <span class="badge bg-primary px-2.5 py-1.5">{{ config('cms.version', '2.0.0') }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                                    <span class="text-muted">{{ __('Version PHP') }}</span>
                                    <code>PHP {{ $diagnostics['php_version'] }}</code>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                                    <span class="text-muted">{{ __('Version Laravel') }}</span>
                                    <code>Laravel {{ $diagnostics['laravel_version'] }}</code>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                                    <span class="text-muted">{{ __('Logiciel Serveur') }}</span>
                                    <span class="small text-dark">{{ $diagnostics['server_software'] }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-bold text-secondary text-uppercase fs-7 mb-3">{{ __('Services & Stockage') }}</h6>
                            <ul class="list-group list-group-flush border rounded">
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                                    <span class="text-muted">{{ __('Base de données') }}</span>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $diagnostics['database_driver'] }} ({{ $diagnostics['database_version'] }})</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                                    <span class="text-muted">{{ __('Pilote de Cache') }}</span>
                                    <code>{{ $diagnostics['cache_driver'] }}</code>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                                    <span class="text-muted">{{ __('Permissions /storage') }}</span>
                                    @if($diagnostics['storage_writable'])
                                        <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i> Inscriptible</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger"><i class="bi bi-x-circle me-1"></i> Non inscriptible</span>
                                    @endif
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                                    <span class="text-muted">{{ __('Mode Safe Mode (Récupération)') }}</span>
                                    @if($diagnostics['safe_mode_active'])
                                        <span class="badge bg-warning text-dark"><i class="bi bi-shield-slash me-1"></i> ACTIF</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Inactif</span>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Tab 4: Storage & Cache -->
        <div class="tab-pane fade" id="content-storage-cache" role="tabpanel">
            <div class="row g-4">
                <!-- 1. Stockage & Storage Link -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100" style="border-radius: 1rem;">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <h5 class="card-title fw-bold mb-0 text-dark">
                                <i class="bi bi-hdd-stack text-primary me-2"></i> {{ __('Espace de Stockage & Lien Public') }}
                            </h5>
                            @if($storageStatus['is_linked'])
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill">
                                    <i class="bi bi-check-circle-fill me-1"></i> {{ __('Lien Actif') }}
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1.5 rounded-pill">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ __('Lien Non Connecté') }}
                                </span>
                            @endif
                        </div>
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <p class="text-muted small mb-3">
                                    {{ __('Pour rendre les fichiers médias, téléversements et ressources publiques accessibles aux navigateurs (via /storage/...), le dossier public doit être relié au disque de stockage local via un lien symbolique.') }}
                                </p>

                                <div class="bg-light p-3 rounded border mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <span class="small text-muted">{{ __('Emplacement Public') }}</span>
                                        <code class="small text-dark">{{ $storageStatus['public_storage_path'] }}</code>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <span class="small text-muted">{{ __('Dossier Cible') }}</span>
                                        <code class="small text-dark">{{ $storageStatus['target_storage_path'] }}</code>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <span class="small text-muted">{{ __('Espace disque libre') }}</span>
                                        <span class="fw-semibold small text-dark">{{ $storageStatus['free_space_human'] }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small text-muted">{{ __('Droits d\'écriture') }}</span>
                                        @if($storageStatus['storage_writable'])
                                            <span class="badge bg-success-subtle text-success"><i class="bi bi-check me-1"></i> Inscriptible</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger"><i class="bi bi-x me-1"></i> Lecture seule</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-top">
                                <form method="POST" action="{{ route('admin.settings.site.storage.link') }}" class="storage-action-form">
                                    @csrf
                                    @if($storageStatus['is_linked'])
                                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                                            <div class="small text-muted">
                                                <i class="bi bi-info-circle text-info me-1"></i> {{ __('Le lien symbolique est déjà opérationnel.') }}
                                            </div>
                                            <button type="submit" name="force" value="1" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1.5">
                                                <i class="bi bi-arrow-repeat"></i> {{ __('Regénérer le lien (storage:link --force)') }}
                                            </button>
                                        </div>
                                    @else
                                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                                            <div class="small text-danger fw-semibold">
                                                <i class="bi bi-exclamation-circle me-1"></i> {{ __('Les fichiers médias ne sont pas encore exposés publiquement.') }}
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
                                                <i class="bi bi-link-45deg"></i> {{ __('Créer le lien public (storage:link)') }}
                                            </button>
                                        </div>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Gestion et Nettoyage du Cache -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100" style="border-radius: 1rem;">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <h5 class="card-title fw-bold mb-0 text-dark">
                                <i class="bi bi-lightning-charge text-warning me-2"></i> {{ __('Nettoyage du Cache du Site') }}
                            </h5>
                            <span class="badge bg-secondary-subtle text-dark border px-2.5 py-1 rounded-pill small">
                                Driver : {{ $cacheStatus['driver'] }}
                            </span>
                        </div>
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <p class="text-muted small mb-3">
                                    {{ __('Nettoyez les différents niveaux de cache du site pour appliquer immédiatement les modifications de templates, de routes, de configurations ou de données applicatives.') }}
                                </p>

                                <!-- Action principale: Vider Tous les Caches -->
                                <div class="bg-warning-subtle border border-warning-subtle rounded-3 p-3 mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div>
                                        <div class="fw-bold text-dark small mb-0.5">
                                            <i class="bi bi-stars text-warning me-1"></i> {{ __('Nettoyage Complet de l\'Application') }}
                                        </div>
                                        <div class="text-muted" style="font-size: 0.8rem;">
                                            {{ __('Exécute artisan optimize:clear (cache, vues, routes, config).') }}
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('admin.settings.site.cache.clear') }}" class="cache-action-form">
                                        @csrf
                                        <input type="hidden" name="type" value="all">
                                        <button type="submit" class="btn btn-warning text-dark fw-bold btn-sm rounded-pill px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1.5">
                                            <i class="bi bi-trash3-fill"></i> {{ __('Vider tous les caches') }}
                                        </button>
                                    </form>
                                </div>

                                <!-- Grille des Caches Granulaires -->
                                <div class="row g-2">
                                    <!-- Cache Applicatif -->
                                    <div class="col-sm-6">
                                        <div class="p-2.5 border rounded bg-light h-100 d-flex flex-column justify-content-between">
                                            <div class="mb-2">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <span class="fw-bold small text-dark"><i class="bi bi-database me-1 text-primary"></i> {{ __('Données & Objets') }}</span>
                                                </div>
                                                <p class="text-muted mb-0" style="font-size: 0.75rem;">
                                                    {{ __('Cache général des données applicatives.') }}
                                                </p>
                                            </div>
                                            <form method="POST" action="{{ route('admin.settings.site.cache.clear') }}" class="cache-action-form">
                                                @csrf
                                                <input type="hidden" name="type" value="cache">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100 rounded-pill py-1 d-flex align-items-center justify-content-center gap-1" style="font-size: 0.8rem;">
                                                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('cache:clear') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Cache des Vues Blade -->
                                    <div class="col-sm-6">
                                        <div class="p-2.5 border rounded bg-light h-100 d-flex flex-column justify-content-between">
                                            <div class="mb-2">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <span class="fw-bold small text-dark"><i class="bi bi-window-stack me-1 text-success"></i> {{ __('Vues Blade') }}</span>
                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">{{ $cacheStatus['views_count'] }} vues</span>
                                                </div>
                                                <p class="text-muted mb-0" style="font-size: 0.75rem;">
                                                    {{ __('Templates Blade compilés dans storage.') }}
                                                </p>
                                            </div>
                                            <form method="POST" action="{{ route('admin.settings.site.cache.clear') }}" class="cache-action-form">
                                                @csrf
                                                <input type="hidden" name="type" value="views">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100 rounded-pill py-1 d-flex align-items-center justify-content-center gap-1" style="font-size: 0.8rem;">
                                                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('view:clear') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Cache des Routes -->
                                    <div class="col-sm-6">
                                        <div class="p-2.5 border rounded bg-light h-100 d-flex flex-column justify-content-between">
                                            <div class="mb-2">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <span class="fw-bold small text-dark"><i class="bi bi-signpost-split me-1 text-info"></i> {{ __('Routes URL') }}</span>
                                                    @if($cacheStatus['is_routes_cached'])
                                                        <span class="badge bg-success-subtle text-success" style="font-size: 0.7rem;">Compilé</span>
                                                    @else
                                                        <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">À la volée</span>
                                                    @endif
                                                </div>
                                                <p class="text-muted mb-0" style="font-size: 0.75rem;">
                                                    {{ __('Fichier de compilation des routes.') }}
                                                </p>
                                            </div>
                                            <form method="POST" action="{{ route('admin.settings.site.cache.clear') }}" class="cache-action-form">
                                                @csrf
                                                <input type="hidden" name="type" value="routes">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100 rounded-pill py-1 d-flex align-items-center justify-content-center gap-1" style="font-size: 0.8rem;">
                                                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('route:clear') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Cache de Configuration -->
                                    <div class="col-sm-6">
                                        <div class="p-2.5 border rounded bg-light h-100 d-flex flex-column justify-content-between">
                                            <div class="mb-2">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <span class="fw-bold small text-dark"><i class="bi bi-gear me-1 text-danger"></i> {{ __('Configuration') }}</span>
                                                    @if($cacheStatus['is_config_cached'])
                                                        <span class="badge bg-success-subtle text-success" style="font-size: 0.7rem;">Compilé</span>
                                                    @else
                                                        <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">À la volée</span>
                                                    @endif
                                                </div>
                                                <p class="text-muted mb-0" style="font-size: 0.75rem;">
                                                    {{ __('Variables d\'environnement et fichiers config.') }}
                                                </p>
                                            </div>
                                            <form method="POST" action="{{ route('admin.settings.site.cache.clear') }}" class="cache-action-form">
                                                @csrf
                                                <input type="hidden" name="type" value="config">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100 rounded-pill py-1 d-flex align-items-center justify-content-center gap-1" style="font-size: 0.8rem;">
                                                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('config:clear') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 mt-3 border-top text-muted small d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-terminal me-1"></i> Commandes Artisan directes</span>
                                <span class="badge bg-light text-secondary border">PHP {{ PHP_VERSION }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Hash tab activation
            const currentHash = window.location.hash;
            if (currentHash) {
                const activeBtn = document.querySelector(`button[data-bs-target="${currentHash}"]`);
                if (activeBtn) {
                    bootstrap.Tab.getOrCreateInstance(activeBtn).show();
                }
            }
            document.querySelectorAll('button[data-bs-toggle="pill"]').forEach(btn => {
                btn.addEventListener('shown.bs.tab', e => {
                    const target = e.target.getAttribute('data-bs-target');
                    if (target) {
                        history.replaceState(null, null, target);
                    }
                });
            });

            // SMTP Test Handler
            const btnTest = document.getElementById('btn-test-smtp');
            const resultAlert = document.getElementById('test-smtp-result');

            if (btnTest) {
                btnTest.addEventListener('click', function () {
                    const email = document.getElementById('test_recipient_email').value;
                    if (!email) {
                        alert("Veuillez saisir une adresse e-mail destinataire.");
                        return;
                    }

                    btnTest.disabled = true;
                    btnTest.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Envoi...';
                    resultAlert.className = 'alert mt-3 d-none';

                    fetch("{{ route('admin.settings.site.smtp.test') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ test_email: email })
                    })
                    .then(res => res.json())
                    .then(data => {
                        btnTest.disabled = false;
                        btnTest.innerHTML = '<i class="bi bi-send"></i> {{ __("Tester l\'envoi") }}';
                        resultAlert.classList.remove('d-none');

                        if (data.success) {
                            resultAlert.className = 'alert alert-success mt-3 shadow-sm';
                            resultAlert.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> ' + data.message;
                        } else {
                            resultAlert.className = 'alert alert-danger mt-3 shadow-sm';
                            resultAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i> ' + data.message;
                        }
                    })
                    .catch(() => {
                        btnTest.disabled = false;
                        btnTest.innerHTML = '<i class="bi bi-send"></i> {{ __("Tester l\'envoi") }}';
                        resultAlert.className = 'alert alert-danger mt-3 shadow-sm';
                        resultAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i> Erreur lors de la requête de test SMTP.';
                        resultAlert.classList.remove('d-none');
                    });
                });
            }

            // AJAX handlers for Cache and Storage forms
            document.querySelectorAll('.cache-action-form, .storage-action-form').forEach(form => {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalHtml = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Exécution...';

                    const formData = new FormData(form);

                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;

                        const alertBox = document.createElement('div');
                        alertBox.className = `alert ${data.success ? 'alert-success' : 'alert-danger'} alert-dismissible fade show shadow-sm mb-3`;
                        alertBox.innerHTML = `
                            <i class="bi ${data.success ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} me-2"></i>
                            ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;

                        const container = document.getElementById('content-storage-cache');
                        container.insertBefore(alertBox, container.firstChild);
                        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                        setTimeout(() => {
                            alertBox.classList.remove('show');
                            setTimeout(() => alertBox.remove(), 300);
                        }, 5000);
                    })
                    .catch(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                        form.submit();
                    });
                });
            });
        });
    </script>
@endsection

