@extends('core::admin.layouts.app')

@section('title', 'Administration — ' . __('Paramètres généraux'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Paramètres généraux') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Gérez les informations personnelles de votre compte, vos identifiants de sécurité et vos sessions.') }}
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

    <div class="row g-4">
        <!-- Left Column: Profile & Password -->
        <div class="col-lg-6">
            <!-- Profile Card -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-person-circle text-primary"></i> {{ __('Informations du profil') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.settings.general.profile') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold small text-secondary">{{ __('Nom complet') }}</label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small text-secondary">{{ __('Adresse e-mail') }}</label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 shadow-sm">
                                <i class="bi bi-save"></i> {{ __('Enregistrer le profil') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Password Card -->
            <div class="card shadow-sm border-0" style="border-radius: 1rem;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock text-primary"></i> {{ __('Modifier le mot de passe') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if($user->last_password_change_at)
                        <div class="alert alert-light border small text-muted mb-3 py-2">
                            <i class="bi bi-clock-history me-1"></i> {{ __('Dernier changement de mot de passe :') }} {{ $user->last_password_change_at->diffForHumans() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.settings.general.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold small text-secondary">{{ __('Mot de passe actuel') }}</label>
                            <input type="password" id="current_password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold small text-secondary">{{ __('Nouveau mot de passe') }}</label>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            <div class="form-text small text-muted">{{ __('Minimum 8 caractères.') }}</div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold small text-secondary">{{ __('Confirmer le mot de passe') }}</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-4">
                                <i class="bi bi-key"></i> {{ __('Mettre à jour le mot de passe') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: 2FA & Active Sessions -->
        <div class="col-lg-6">
            <!-- 2FA Card -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-phone text-primary"></i> {{ __('Authentification à deux facteurs (2FA)') }}
                    </h5>
                    @if($user->hasTwoFactorEnabled())
                        <span class="badge bg-success-subtle text-success border border-success px-2.5 py-1.5 rounded-pill">
                            <i class="bi bi-shield-check me-1"></i> {{ __('Activée') }}
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary px-2.5 py-1.5 rounded-pill">
                            <i class="bi bi-shield-x me-1"></i> {{ __('Désactivée') }}
                        </span>
                    @endif
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-3">
                        {{ __('L\'authentification à deux facteurs renforce la sécurité de votre compte en exigeant un code temporaire TOTP lors de la connexion.') }}
                    </p>

                    @if($user->hasTwoFactorEnabled())
                        <div class="alert alert-success d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
                            <div>
                                <div class="fw-bold">{{ __('La 2FA est active sur votre compte.') }}</div>
                                <div class="small text-muted">{{ __('Confirmée le') }} {{ $user->two_factor_confirmed_at?->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalDisable2fa">
                            <i class="bi bi-shield-slash me-1"></i> {{ __('Désactiver la 2FA') }}
                        </button>
                    @else
                        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="btn-start-2fa">
                            <i class="bi bi-shield-plus"></i> {{ __('Configurer l\'authentification 2FA') }}
                        </button>
                    @endif
                </div>
            </div>

            <!-- Active Sessions Card -->
            <div class="card shadow-sm border-0" style="border-radius: 1rem;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-display text-primary"></i> {{ __('Sessions actives') }}
                    </h5>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalLogoutOthers">
                        <i class="bi bi-box-arrow-right me-1"></i> {{ __('Déconnecter les autres sessions') }}
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0">
                            <thead class="table-light fs-7 text-uppercase text-secondary">
                                <tr>
                                    <th class="ps-4">{{ __('Appareil / Navigateur') }}</th>
                                    <th>{{ __('Adresse IP') }}</th>
                                    <th class="pe-4 text-end">{{ __('Dernière activité') }}</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                @forelse($activeSessions as $sess)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold text-dark text-truncate" style="max-width: 200px;" title="{{ $sess['user_agent'] }}">
                                                {{ $sess['user_agent'] ?? 'Inconnu' }}
                                            </div>
                                            @if($sess['is_current'])
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-8">{{ __('Cette session (actuelle)') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <code>{{ $sess['ip_address'] ?? '127.0.0.1' }}</code>
                                        </td>
                                        <td class="pe-4 text-end text-muted">
                                            {{ $sess['last_active'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">
                                            {{ __('Gestion des sessions non activée ou aucune session active.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Setup 2FA -->
    <div class="modal fade" id="modalSetup2fa" tabindex="-1" aria-labelledby="modalSetup2faLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem;">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalSetup2faLabel">
                        <i class="bi bi-shield-plus text-primary me-2"></i> {{ __('Configuration du 2FA') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center" id="setup-step-1">
                    <p class="text-muted small mb-3">
                        {{ __('Scannez ce QR Code avec votre application d\'authentification (Google Authenticator, Authy, Microsoft Authenticator) :') }}
                    </p>
                    <div id="2fa-qr-container" class="d-flex justify-content-center my-3 p-2 bg-white rounded border d-inline-block">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">{{ __('Ou saisissez manuellement la clé secrète :') }}</small>
                        <code id="2fa-secret-text" class="fs-6 user-select-all fw-bold text-primary"></code>
                    </div>

                    <hr class="my-4">

                    <form id="form-confirm-2fa">
                        @csrf
                        <label for="totp_code_input" class="form-label fw-semibold small text-dark mb-1">
                            {{ __('Saisissez le code à 6 chiffres généré par l\'application :') }}
                        </label>
                        <div class="d-flex justify-content-center mb-3">
                            <input type="text" id="totp_code_input" name="code" class="form-control text-center font-monospace fs-4 fw-bold" style="max-width: 200px; letter-spacing: 4px;" maxlength="6" placeholder="000000" required>
                        </div>
                        <div id="2fa-error-alert" class="alert alert-danger py-2 small d-none"></div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="btn-submit-code">
                            <i class="bi bi-check-circle me-1"></i> {{ __('Confirmer et activer la 2FA') }}
                        </button>
                    </form>
                </div>

                <!-- Recovery Codes Step -->
                <div class="modal-body p-4 d-none" id="setup-step-2">
                    <div class="text-center mb-3">
                        <div class="d-inline-flex p-3 rounded-circle bg-success-subtle text-success mb-2">
                            <i class="bi bi-check-lg fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-success">{{ __('2FA activée avec succès !') }}</h5>
                        <p class="text-muted small">
                            {{ __('Conservez précieusement ces codes de récupération d\'urgence dans un endroit sécurisé. Chaque code ne peut être utilisé qu\'une seule fois.') }}
                        </p>
                    </div>

                    <div class="bg-light p-3 rounded border mb-3">
                        <div class="row g-2 text-center" id="recovery-codes-grid"></div>
                    </div>

                    <button type="button" class="btn btn-success w-100 py-2 fw-semibold" onclick="window.location.reload();">
                        {{ __('J\'ai bien sauvegardé mes codes') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Disable 2FA -->
    <div class="modal fade" id="modalDisable2fa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 1rem;">
                <form method="POST" action="{{ route('admin.settings.general.2fa.disable') }}">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i> {{ __('Désactiver la 2FA') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">
                            {{ __('Pour confirmer la désactivation de l\'authentification à deux facteurs, veuillez saisir votre mot de passe actuel :') }}
                        </p>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label fw-semibold small text-secondary">{{ __('Mot de passe') }}</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('Confirmer la désactivation') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Logout Other Sessions -->
    <div class="modal fade" id="modalLogoutOthers" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 1rem;">
                <form method="POST" action="{{ route('admin.settings.general.sessions.logout-others') }}">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-dark">
                            <i class="bi bi-box-arrow-right text-danger me-2"></i> {{ __('Déconnecter les autres sessions') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">
                            {{ __('Toutes les autres sessions sur les autres navigateurs ou ordinateurs seront fermées. Saisissez votre mot de passe pour valider :') }}
                        </p>
                        <div class="mb-3">
                            <label for="password_logout" class="form-label fw-semibold small text-secondary">{{ __('Mot de passe') }}</label>
                            <input type="password" id="password_logout" name="password_logout" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('Déconnecter les sessions') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnStart2fa = document.getElementById('btn-start-2fa');
            const modalEl = document.getElementById('modalSetup2fa');
            let bsModal = modalEl ? new bootstrap.Modal(modalEl) : null;

            if (btnStart2fa) {
                btnStart2fa.addEventListener('click', function () {
                    bsModal.show();
                    fetch("{{ route('admin.settings.general.2fa.setup') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        document.getElementById('2fa-qr-container').innerHTML = data.svg;
                        document.getElementById('2fa-secret-text').innerText = data.secret;
                    })
                    .catch(() => {
                        alert("Erreur lors de l'initialisation du 2FA.");
                    });
                });
            }

            const formConfirm = document.getElementById('form-confirm-2fa');
            if (formConfirm) {
                formConfirm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const code = document.getElementById('totp_code_input').value;
                    const errorAlert = document.getElementById('2fa-error-alert');
                    errorAlert.classList.add('d-none');

                    fetch("{{ route('admin.settings.general.2fa.confirm') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ code: code })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('setup-step-1').classList.add('d-none');
                            document.getElementById('setup-step-2').classList.remove('d-none');

                            const grid = document.getElementById('recovery-codes-grid');
                            grid.innerHTML = '';
                            data.recovery_codes.forEach(c => {
                                const col = document.createElement('div');
                                col.className = 'col-6 font-monospace fw-bold fs-7 py-1 text-dark bg-white border rounded';
                                col.innerText = c;
                                grid.appendChild(col);
                            });
                        } else {
                            errorAlert.innerText = data.message || "Code invalide.";
                            errorAlert.classList.remove('d-none');
                        }
                    })
                    .catch(() => {
                        errorAlert.innerText = "Erreur de communication avec le serveur.";
                        errorAlert.classList.remove('d-none');
                    });
                });
            }
        });
    </script>
@endsection

