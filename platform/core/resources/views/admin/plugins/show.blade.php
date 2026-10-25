@extends('core::admin.layouts.app')

@section('title', __('Administration') . ' - ' . __('Plugin Details') . ' : ' . $manifest->getName())

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.plugins.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i> &larr; {{ __('Back to plugins list') }}
        </a>
    </div>

    @php
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

    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h3 fw-bold mb-0">{{ $manifest->getName() }}</h1>
                <span class="badge {{ $badgeClass }} {{ $bsBadgeClass }} px-2 py-1">{{ $state }}</span>
            </div>
            <p class="font-monospace text-muted small mb-0">{{ $pluginId }}</p>
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
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>{{ __('Warning:') }}</strong> {{ __('Emergency Safe Mode is active. This third-party extension is currently neutralized at boot.') }}
        </div>
    @endif

    @if($state === 'ERROR' && $record?->error_message)
        <div class="alert alert-danger alert-error shadow-sm mb-4">
            <h6 class="fw-bold"><i class="bi bi-bug-fill me-1"></i> {{ __('Isolated Execution Error:') }}</h6>
            <pre class="mb-0 font-monospace small bg-white p-2 rounded border border-danger-subtle">{{ $record->error_message }}</pre>
        </div>
    @endif

    @if(!empty($activeDependents))
        <div class="alert alert-warning shadow-sm mb-4">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <strong>{{ __('Dependency Warning:') }}</strong> {{ __('This extension is required by :count active extension(s):', ['count' => count($activeDependents)]) }}
            <strong>{{ implode(', ', array_map(fn ($p) => $p->getManifest()->getName() . ' (' . $p->getManifest()->getId() . ')', $activeDependents)) }}</strong>.
            {{ __('Its deactivation or uninstallation without forcing will be blocked to ensure system stability.') }}
        </div>
    @endif

    <!-- Actions Toolbar Card -->
    <div class="card card-outline card-primary shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="fw-bold mb-1 text-primary">
                        <i class="bi bi-sliders me-1"></i> {{ __('Lifecycle Actions') }}
                    </h5>
                    <p class="text-muted small mb-0">
                        {{ __('Direct control of extension state with dependency checking') }}
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if($state === 'AVAILABLE')
                        <form method="POST" action="{{ route('admin.plugins.enable', $pluginId) }}" class="actions-form">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-play-circle me-1"></i> {{ __('Install and Activate') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.plugins.delete', $pluginId) }}" class="actions-form" onsubmit="return confirm('{{ __('Warning: Are you sure you want to permanently delete this extension files? This action is irreversible.') }}');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash me-1"></i> {{ __('Permanently Delete') }}
                            </button>
                        </form>
                    @elseif($state === 'INSTALLED' || $state === 'DISABLED')
                        <form method="POST" action="{{ route('admin.plugins.enable', $pluginId) }}" class="actions-form">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-play-circle me-1"></i> {{ __('Activate extension') }}
                            </button>
                        </form>
                    @elseif($state === 'ACTIVE')
                        <form method="POST" action="{{ route('admin.plugins.disable', $pluginId) }}" class="actions-form" onsubmit="return confirm('{{ __('Confirm deactivation of :name?', ['name' => $manifest->getName()]) }}');">
                            @csrf
                            @if(!empty($activeDependents))
                                <input type="hidden" name="force" value="1">
                            @endif
                            <button type="submit" class="btn btn-sm btn-secondary">
                                <i class="bi bi-pause-circle me-1"></i> {{ __('Deactivate extension') }}
                                @if(!empty($activeDependents)) ({{ __('Force') }}) @endif
                            </button>
                        </form>
                    @elseif($state === 'ERROR')
                        <form method="POST" action="{{ route('admin.plugins.disable', $pluginId) }}" class="actions-form">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-secondary">
                                <i class="bi bi-shield-x me-1"></i> {{ __('Quarantine / Disable') }}
                            </button>
                        </form>
                    @endif

                    @if($state !== 'AVAILABLE')
                        <form method="POST" action="{{ route('admin.plugins.uninstall', $pluginId) }}" class="actions-form d-flex align-items-center gap-2" onsubmit="return confirm('{{ __('Warning: Are you sure you want to uninstall this extension?') }}');">
                            @csrf
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" name="purge_data" value="1" id="purgeData">
                                <label class="form-check-label small text-muted" for="purgeData">
                                    {{ __('Purge data') }}
                                </label>
                            </div>
                            @if(!empty($activeDependents))
                                <input type="hidden" name="force" value="1">
                            @endif
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="bi bi-x-circle me-1"></i> {{ __('Uninstall') }}
                                @if(!empty($activeDependents)) ({{ __('Force') }}) @endif
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Metadata Grid -->
    <div class="row g-4 mb-4">
        <!-- General Info -->
        <div class="col-lg-6">
            <div class="card card-outline card-secondary h-100 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold fs-6 mb-0">
                        <i class="bi bi-info-circle me-1"></i> {{ __('General Information') }}
                    </h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0 small">
                        <tbody>
                            <tr>
                                <th style="width: 35%;" class="bg-light ps-3">{{ __('Identifier') }}</th>
                                <td class="font-monospace"><code>{{ $pluginId }}</code></td>
                            </tr>
                            <tr>
                                <th class="bg-light ps-3">{{ __('Readable Name') }}</th>
                                <td class="fw-semibold">{{ $manifest->getName() }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light ps-3">{{ __('Version') }}</th>
                                <td><span class="badge text-bg-primary px-2 py-1">{{ $manifest->getVersion() }}</span></td>
                            </tr>
                            <tr>
                                <th class="bg-light ps-3">{{ __('Current Status') }}</th>
                                <td><span class="badge {{ $badgeClass }} {{ $bsBadgeClass }} px-2 py-1">{{ $state }}</span></td>
                            </tr>
                            <tr>
                                <th class="bg-light ps-3">{{ __('Installation Date') }}</th>
                                <td>
                                    @if($record?->created_at)
                                        {{ $record->created_at->format('d/m/Y H:i:s') }}
                                    @else
                                        <span class="text-muted fst-italic">{{ __('Not recorded / Not installed') }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light ps-3">{{ __('Access Path') }}</th>
                                <td><code class="small text-break">{{ $plugin->getPath() }}</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Description & Author -->
        <div class="col-lg-6">
            <div class="card card-outline card-secondary h-100 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold fs-6 mb-0">
                        <i class="bi bi-person-lines-fill me-1"></i> {{ __('Author & Description') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-uppercase text-muted fw-bold fs-7 mb-1">{{ __('Description') }}</h6>
                        <p class="small mb-0">
                            {{ $manifest->getDescription() ?: __('No description provided.') }}
                        </p>
                    </div>
                    @php $author = $manifest->getAuthor(); @endphp
                    <div>
                        <h6 class="text-uppercase text-muted fw-bold fs-7 mb-2">{{ __('Publisher / Author') }}</h6>
                        <table class="table table-sm table-bordered align-middle mb-0 small">
                            <tbody>
                                <tr>
                                    <th style="width: 30%;" class="bg-light">{{ __('Name') }}</th>
                                    <td>{{ $author['name'] ?? __('Not specified') }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">{{ __('Email') }}</th>
                                    <td>
                                        @if(!empty($author['email']))
                                            <a href="mailto:{{ $author['email'] }}" class="text-decoration-none">{{ $author['email'] }}</a>
                                        @else
                                            <span class="text-muted fst-italic">{{ __('Not specified') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">{{ __('Website') }}</th>
                                    <td>
                                        @if(!empty($author['homepage']))
                                            <a href="{{ $author['homepage'] }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                                {{ $author['homepage'] }} <i class="bi bi-box-arrow-up-right small"></i>
                                            </a>
                                        @else
                                            <span class="text-muted fst-italic">{{ __('Not specified') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Technical Declarations Grid -->
    <div class="row g-4 mb-4">
        <!-- Compatibility & Providers -->
        <div class="col-lg-6">
            <div class="card card-outline card-secondary h-100 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold fs-6 mb-0">
                        <i class="bi bi-cpu me-1"></i> {{ __('Compatibility & Providers') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-uppercase text-muted fw-bold fs-7 mb-1">{{ __('System Compatibility') }}</h6>
                        @php $compat = $manifest->getCompatibility(); @endphp
                        @if(empty($compat))
                            <p class="text-muted small fst-italic mb-0">{{ __('No declared constraints') }}</p>
                        @else
                            <ul class="list-unstyled mb-0 small">
                                @foreach($compat as $key => $version)
                                    <li><i class="bi bi-check2-circle text-success me-1"></i> <strong>{{ $key }}</strong>: <code>{{ $version }}</code></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div>
                        <h6 class="text-uppercase text-muted fw-bold fs-7 mb-1">{{ __('Service Providers') }}</h6>
                        @php $providers = $manifest->getProviders(); @endphp
                        @if(empty($providers))
                            <p class="text-muted small fst-italic mb-0">{{ __('No ServiceProvider declared') }}</p>
                        @else
                            <ul class="list-unstyled mb-0 font-monospace small">
                                @foreach($providers as $provider)
                                    <li class="text-break"><i class="bi bi-box me-1 text-primary"></i> {{ $provider }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions & Routes -->
        <div class="col-lg-6">
            <div class="card card-outline card-secondary h-100 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold fs-6 mb-0">
                        <i class="bi bi-shield-check me-1"></i> {{ __('Permissions & Routes') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-uppercase text-muted fw-bold fs-7 mb-2">{{ __('Declared Permissions') }}</h6>
                        @php $permissions = $manifest->getPermissions(); @endphp
                        @if(empty($permissions))
                            <p class="text-muted small fst-italic mb-0">{{ __('No declared permissions') }}</p>
                        @else
                            <table class="table table-sm table-bordered align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Key') }}</th>
                                        <th>{{ __('Label') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($permissions as $perm)
                                        <tr>
                                            <td><code>{{ $perm['key'] }}</code></td>
                                            <td>{{ $perm['label'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                    <div>
                        <h6 class="text-uppercase text-muted fw-bold fs-7 mb-1">{{ __('Route Files') }}</h6>
                        @php $routes = $manifest->getRoutes(); @endphp
                        @if(empty(array_filter($routes)))
                            <p class="text-muted small fst-italic mb-0">{{ __('No custom routes declared') }}</p>
                        @else
                            <ul class="list-unstyled mb-0 small">
                                @foreach($routes as $channel => $file)
                                    @if($file)
                                        <li><strong>{{ strtoupper($channel) }}</strong> : <code>{{ $file }}</code></li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Direct Dependencies -->
    <div class="card card-outline card-secondary shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title fw-bold fs-6 mb-0">
                <i class="bi bi-diagram-3 me-1"></i> {{ __('Dependencies required by this extension') }}
            </h5>
            <small class="text-muted d-block mt-1">
                {{ __('Checking installation and activation status of prerequisites') }}
            </small>
        </div>
        <div class="card-body p-0">
            @if(empty($dependenciesHealth))
                <div class="text-center text-muted py-4 small">
                    {{ __('This extension does not require any other plugin to work.') }}
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light fs-7 text-uppercase text-muted">
                            <tr>
                                <th>{{ __('Plugin Identifier') }}</th>
                                <th>{{ __('Declared Constraint') }}</th>
                                <th>{{ __('Resolution State') }}</th>
                                <th class="text-end pe-3">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dependenciesHealth as $depId => $dep)
                                @php
                                    $badgeDep = match($dep['status']) {
                                        'satisfied' => 'badge-active',
                                        'disabled' => 'badge-disabled',
                                        'available' => 'badge-available',
                                        'missing' => 'badge-error',
                                        default => 'badge-available',
                                    };
                                    $bsBadgeDep = match($dep['status']) {
                                        'satisfied' => 'text-bg-success',
                                        'disabled' => 'text-bg-warning',
                                        'available' => 'text-bg-secondary',
                                        'missing' => 'text-bg-danger',
                                        default => 'text-bg-secondary',
                                    };
                                    $labelDep = match($dep['status']) {
                                        'satisfied' => __('Satisfied (Active)'),
                                        'disabled' => __('Installed but Inactive'),
                                        'available' => __('Available uninstalled'),
                                        'missing' => __('Missing / Non-existent'),
                                        default => __('Unknown'),
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $depId }}</strong>
                                        @if($dep['plugin'] !== null)
                                            <div class="small text-muted">
                                                {{ $dep['plugin']->getManifest()->getName() }}
                                            </div>
                                        @endif
                                    </td>
                                    <td><code>{{ $dep['constraint'] }}</code></td>
                                    <td>
                                        <span class="badge {{ $badgeDep }} {{ $bsBadgeDep }} px-2 py-1">{{ $labelDep }}</span>
                                    </td>
                                    <td class="text-end pe-3">
                                        @if($dep['plugin'] !== null)
                                            <a href="{{ route('admin.plugins.show', $depId) }}" class="btn btn-sm btn-outline-secondary">
                                                {{ __('Inspect') }}
                                            </a>
                                        @else
                                            <span class="text-danger small fw-semibold">{{ __('Action required') }}</span>
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

    <!-- Reverse Dependents -->
    <div class="card card-outline card-secondary shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title fw-bold fs-6 mb-0">
                <i class="bi bi-diagram-2 me-1"></i> {{ __('Dependent Extensions (Reverse Dependencies)') }}
            </h5>
            <small class="text-muted d-block mt-1">
                {{ __('Extensions installed or detected that declared a dependency on :id', ['id' => $pluginId]) }}
            </small>
        </div>
        <div class="card-body p-0">
            @if(empty($dependents))
                <div class="text-center text-muted py-4 small">
                    {{ __('No extension has declared a dependency on this extension.') }}
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light fs-7 text-uppercase text-muted">
                            <tr>
                                <th>{{ __('Dependent Extension') }}</th>
                                <th>{{ __('Current Status') }}</th>
                                <th>{{ __('Impact if Deactivated') }}</th>
                                <th class="text-end pe-3">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dependents as $depId => $depPlugin)
                                @php
                                    $depState = strtoupper($depPlugin->getState());
                                    $depActive = $depPlugin->isActive();
                                    $badgeDepClass = match($depState) {
                                        'ACTIVE' => 'badge-active',
                                        'DISABLED' => 'badge-disabled',
                                        'INSTALLED' => 'badge-installed',
                                        'ERROR' => 'badge-error',
                                        default => 'badge-available',
                                    };
                                    $bsBadgeDepClass = match($depState) {
                                        'ACTIVE' => 'text-bg-success',
                                        'DISABLED' => 'text-bg-warning',
                                        'INSTALLED' => 'text-bg-primary',
                                        'ERROR' => 'text-bg-danger',
                                        default => 'text-bg-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $depPlugin->getManifest()->getName() }}</strong>
                                        <div class="font-monospace small text-muted">{{ $depId }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeDepClass }} {{ $bsBadgeDepClass }} px-2 py-1">{{ $depState }}</span>
                                    </td>
                                    <td>
                                        @if($depActive)
                                            <span class="text-danger fw-semibold small">
                                                <i class="bi bi-exclamation-octagon me-1"></i> {!! __('Blocking: the extension is active') !!}
                                            </span>
                                        @else
                                            <span class="text-muted small">
                                                {{ __('Inactive (no immediate impact)') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('admin.plugins.show', $depId) }}" class="btn btn-sm btn-outline-secondary">
                                            {{ __('Inspect') }}
                                        </a>
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
