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
                                    <span class="badge bg-primary px-2.5 py-1.5">{{ config('cms.version', '1.1.0') }}</span>
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
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
        });
    </script>
@endsection

