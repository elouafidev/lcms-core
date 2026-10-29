@extends('core::admin.layouts.app')

@section('title', 'Administration — ' . __('Mise à jour de LCMS Core'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Mise à jour de la plateforme') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Appliquez des mises à jour de sécurité et d\'évolution du noyau LCMS Core par téléversement d\'archive ZIP sécurisée.') }}
            </p>
        </div>
        <div>
            <span class="badge bg-primary px-3 py-2 fs-6 shadow-sm">
                <i class="bi bi-box-seam me-1"></i> Core v{{ $currentVersion }}
            </span>
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

    <!-- Readiness Checks Card -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="card-title fw-bold mb-0 text-dark">
                <i class="bi bi-shield-check text-primary me-2"></i> {{ __('Vérifications préalables du serveur') }}
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="p-3 border rounded bg-light text-center">
                        <small class="text-muted d-block mb-1">{{ __('Extension PHP ZipArchive') }}</small>
                        @if($checks['zip_extension'])
                            <span class="badge bg-success-subtle text-success fs-7"><i class="bi bi-check-circle me-1"></i> Disponible</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger fs-7"><i class="bi bi-x-circle me-1"></i> Manquante</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="p-3 border rounded bg-light text-center">
                        <small class="text-muted d-block mb-1">{{ __('Droit d\'écriture /storage') }}</small>
                        @if($checks['storage_writable'])
                            <span class="badge bg-success-subtle text-success fs-7"><i class="bi bi-check-circle me-1"></i> Inscriptible</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger fs-7"><i class="bi bi-x-circle me-1"></i> Verrouillé</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="p-3 border rounded bg-light text-center">
                        <small class="text-muted d-block mb-1">{{ __('Limite upload max') }}</small>
                        <span class="fw-bold text-dark fs-7"><code>{{ $checks['max_upload_size'] }}</code> (Post: {{ $checks['post_max_size'] }})</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="p-3 border rounded bg-light text-center">
                        <small class="text-muted d-block mb-1">{{ __('Mémoire allouée PHP') }}</small>
                        <span class="fw-bold text-dark fs-7"><code>{{ $checks['memory_limit'] }}</code></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload ZIP Update Card -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="card-title fw-bold mb-0 text-dark">
                <i class="bi bi-cloud-arrow-up text-primary me-2"></i> {{ __('Téléverser une archive de mise à jour (.zip)') }}
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-light border small text-muted mb-4">
                <i class="bi bi-info-circle text-primary me-2"></i>
                <strong>{{ __('Garantie d\'intégrité & Restauration automatique :') }}</strong>
                {{ __('Avant toute application de mise à jour, une sauvegarde complète du noyau et de la base de données est automatiquement générée. Si une erreur survient lors de la migration ou du déploiement, le système restaure instantanément l\'état initial.') }}
            </div>

            <form method="POST" action="{{ route('admin.settings.update.upload') }}" enctype="multipart/form-data" onsubmit="return confirm('Êtes-vous certain de vouloir lancer la mise à jour de LCMS Core ? Le CMS passera brièvement en maintenance le temps du déploiement.');">
                @csrf
                <div class="row align-items-center g-3">
                    <div class="col-md-8">
                        <input type="file" name="update_zip" class="form-control form-control-lg @error('update_zip') is-invalid @enderror" accept=".zip" required>
                        <div class="form-text small text-muted">
                            {{ __('Sélectionnez l\'archive officielle LCMS Core contenant le manifeste update.json (Max 50 Mo).') }}
                        </div>
                        @error('update_zip')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-lg w-100 d-inline-flex align-items-center justify-content-center gap-2 shadow-sm">
                            <i class="bi bi-arrow-repeat"></i> {{ __('Installer la mise à jour') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Updates History Card -->
    <div class="card shadow-sm border-0" style="border-radius: 1rem;">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="card-title fw-bold mb-0 text-dark">
                <i class="bi bi-clock-history text-primary me-2"></i> {{ __('Historique des mises à jour') }}
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light fs-7 text-uppercase text-secondary">
                        <tr>
                            <th class="ps-4">{{ __('Date') }}</th>
                            <th>{{ __('De la version') }}</th>
                            <th>{{ __('Vers la version') }}</th>
                            <th>{{ __('Statut') }}</th>
                            <th>{{ __('Initié par') }}</th>
                            <th class="pe-4 text-end">{{ __('Journal / Sauvegarde') }}</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        @forelse($history as $item)
                            <tr>
                                <td class="ps-4 fw-semibold text-dark">
                                    {{ $item->created_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td>
                                    <code>v{{ $item->from_version }}</code>
                                </td>
                                <td>
                                    <code>v{{ $item->to_version }}</code>
                                </td>
                                <td>
                                    @if($item->status === 'SUCCESS')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fs-8">
                                            <i class="bi bi-check-circle me-1"></i> Réussie
                                        </span>
                                    @elseif($item->status === 'ROLLED_BACK')
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-8">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Restaurée
                                        </span>
                                    @elseif($item->status === 'FAILED')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-8">
                                            <i class="bi bi-x-circle me-1"></i> Échouée
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary fs-8">
                                            {{ $item->status }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    {{ $item->user ? $item->user->name : 'Système / CLI' }}
                                </td>
                                <td class="pe-4 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalLog-{{ $item->id }}">
                                        <i class="bi bi-file-text me-1"></i> {{ __('Voir le log') }}
                                    </button>

                                    <!-- Modal View Log -->
                                    <div class="modal fade" id="modalLog-{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content text-start border-0 shadow">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold">
                                                        {{ __('Détails de mise à jour') }} : v{{ $item->from_version }} &rarr; v{{ $item->to_version }}
                                                    </h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-3">
                                                    @if($item->backup_path)
                                                        <div class="mb-2 small text-muted">
                                                            <strong>{{ __('Archive de sauvegarde :') }}</strong> <code>{{ $item->backup_path }}</code>
                                                        </div>
                                                    @endif
                                                    <pre class="bg-dark text-white p-3 rounded small font-monospace" style="max-height: 350px; overflow-y: auto;">{{ $item->log ?: 'Aucun détail enregistré.' }}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-clock-history fs-2 d-block mb-2"></i>
                                    {{ __('Aucune mise à jour appliquée pour le moment.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

