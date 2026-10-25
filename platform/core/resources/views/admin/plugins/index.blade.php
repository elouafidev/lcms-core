@extends('core::admin.layouts.app')

@section('title', 'Administration — ' . __('CMS Plugins Management'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('CMS Plugins Management') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Decoupled core, logical isolation and plugin lifecycle') }}
            </p>
        </div>
        <div>
            @if($isSafeMode)
                <span class="badge text-bg-danger fs-6 px-3 py-2 shadow-sm">
                    <i class="bi bi-shield-slash me-1"></i> {{ __('SAFE MODE ACTIVE') }}
                </span>
            @endif
        </div>
    </div>

    @if($isSafeMode)
        <div class="alert alert-warning shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning"></i>
                <div>
                    <strong>{{ __('Warning:') }}</strong> {{ __('Safe mode active: third-party extensions are neutralized.') }}
                </div>
            </div>
        </div>
    @endif

    @php
        $quarantinedPlugins = $plugins->filter(fn ($p) => strtoupper($p->getState()) === 'ERROR');
    @endphp

    @if($quarantinedPlugins->isNotEmpty())
        <div class="alert alert-danger alert-error shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-bug-fill fs-4 me-3 text-danger"></i>
                <div>
                    <strong>{{ __('Critical warning:') }}</strong> {{ __('Faulty extensions have been quarantined') }} ({{ $quarantinedPlugins->map(fn ($p) => $p->getManifest()->getName())->join(', ') }}).
                </div>
            </div>
        </div>
    @endif

    <!-- Card Upload ZIP Plugin -->
    <div class="card card-outline card-primary shadow-sm mb-4" style="border-radius: 1rem; border-color: #e2e8f0;">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-6">
                    <h5 class="fw-bold mb-1 text-primary d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-zip fs-4"></i> {{ __('Import a plugin (.zip)') }}
                    </h5>
                    <p class="text-muted small mb-0">
                        {{ __('Select a compliant LCMS ZIP archive containing its plugin.json manifest (max 50 MB).') }}
                    </p>
                </div>
                <div class="col-lg-6">
                    <form method="POST" action="{{ route('admin.plugins.upload') }}" enctype="multipart/form-data" class="d-flex align-items-center gap-2 flex-wrap justify-content-lg-end">
                        @csrf
                        <input type="file" name="plugin_archive" accept=".zip,application/zip,application/x-zip,application/x-zip-compressed" required class="form-control form-control-sm" style="max-width: 250px; border-radius: 0.6rem;">
                        <div class="form-check me-2">
                            <input class="form-check-input" type="checkbox" name="overwrite" value="1" id="overwritePlugin">
                            <label class="form-check-label small text-muted" for="overwritePlugin">
                                {{ __('Overwrite if existing') }}
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm">
                            <i class="bi bi-upload"></i> {{ __('Install ZIP') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Plugins Table Card -->
    <div class="card card-outline card-secondary shadow-sm" style="border-radius: 1rem; border-color: #e2e8f0;">
        <div class="card-header bg-white py-3 border-bottom border-light">
            <h3 class="card-title fw-bold fs-6 mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-puzzle text-primary"></i> {{ __('Installed System Plugins') }}
            </h3>
        </div>
        <div class="card-body p-0">
            @if($plugins->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-folder-x fs-1 d-block mb-2 text-muted"></i>
                    <h4>{{ __('No plugins detected') }}</h4>
                    <p class="small">{{ __('Deposit compliant plugins in platform/plugins/ with their plugin.json manifest.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7 text-muted">
                            <tr>
                                <th style="width: 45%;" class="ps-3">{{ __('Plugin') }}</th>
                                <th style="width: 15%;">{{ __('Version') }}</th>
                                <th style="width: 15%;">{{ __('Status') }}</th>
                                <th style="width: 25%;" class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plugins as $plugin)
                                @php
                                    $manifest = $plugin->getManifest();
                                    $state = strtoupper($plugin->getState());
                                    $pluginId = $manifest->getId();
                                    $badgeClass = match($state) {
                                        'AVAILABLE' => 'badge-available',
                                        'INSTALLED' => 'badge-installed',
                                        'ACTIVE' => 'badge-active',
                                        'DISABLED' => 'badge-disabled',
                                        'ERROR' => 'badge-error',
                                        default => 'badge-available',
                                    };
                                    $bsBadgeClass = match($state) {
                                        'AVAILABLE' => 'text-bg-secondary',
                                        'INSTALLED' => 'text-bg-primary',
                                        'ACTIVE' => 'text-bg-success',
                                        'DISABLED' => 'text-bg-warning',
                                        'ERROR' => 'text-bg-danger',
                                        default => 'text-bg-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold">
                                            <a href="{{ route('admin.plugins.show', $pluginId) }}" class="text-primary text-decoration-none" title="{{ __('Configure') }}">
                                                {{ $manifest->getName() }}
                                            </a>
                                        </div>
                                        <div class="font-monospace small text-muted">{{ $pluginId }}</div>
                                        @if($state === 'ERROR' && $plugin instanceof \App\Core\PluginRegistry\Models\PluginRecord && $plugin->error_message)
                                            <div class="text-danger small font-monospace mt-1">
                                                <i class="bi bi-x-circle me-1"></i> {{ $plugin->error_message }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace">{{ $manifest->getVersion() }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeClass }} {{ $bsBadgeClass }} px-2 py-1">{{ $state }}</span>
                                    </td>
                                    <td class="text-end pe-3">
                                        @if($state === 'AVAILABLE')
                                            <form method="POST" action="{{ route('admin.plugins.enable', $pluginId) }}" class="actions-form">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">{{ __('Activate') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.plugins.delete', $pluginId) }}" class="actions-form" onsubmit="return confirm('{{ __('Are you sure you want to permanently delete this uninstalled plugin and all its files? This action is irreversible.') }}');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                            </form>
                                        @elseif($state === 'INSTALLED' || $state === 'DISABLED')
                                            <form method="POST" action="{{ route('admin.plugins.enable', $pluginId) }}" class="actions-form">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">{{ __('Activate') }}</button>
                                            </form>
                                        @elseif($state === 'ACTIVE')
                                            <form method="POST" action="{{ route('admin.plugins.disable', $pluginId) }}" class="actions-form">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary">{{ __('Deactivate') }}</button>
                                            </form>
                                        @elseif($state === 'ERROR')
                                            <form method="POST" action="{{ route('admin.plugins.disable', $pluginId) }}" class="actions-form">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary">{{ __('Quarantine') }}</button>
                                            </form>
                                        @endif

                                        @if($state !== 'AVAILABLE')
                                            <form method="POST" action="{{ route('admin.plugins.uninstall', $pluginId) }}" class="actions-form" onsubmit="return confirm('{{ __('Confirm uninstallation?') }}');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Uninstall') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
