@extends('core::admin.layouts.app')

@section('title', __('Navigation Menus — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Navigation Menus') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Configuration of tree menus and navigation links') }}
            </p>
        </div>
    </div>

    <!-- Create Menu Card -->
    <div class="card card-outline card-primary shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title fw-bold fs-6 mb-0 text-primary">
                <i class="bi bi-plus-circle me-1"></i> {{ __('Create New Menu') }}
            </h5>
        </div>
        <div class="card-body">
            <form action="/admin/menus" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-semibold small">{{ __('Menu Name') }}</label>
                        <input type="text" id="name" name="name" required placeholder="{{ __('ex: Main Menu') }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="slug" class="form-label fw-semibold small">{{ __('Slug Identifier') }}</label>
                        <input type="text" id="slug" name="slug" required placeholder="ex: main-nav" class="form-control">
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold small">{{ __('Description') }}</label>
                        <textarea id="description" name="description" rows="2" placeholder="{{ __('Optional description') }}" class="form-control"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <i class="bi bi-check2"></i> {{ __('Create Menu') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Menus Table -->
    <div class="card card-outline card-secondary shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title fw-bold fs-6 mb-0 text-secondary">
                <i class="bi bi-list-ul me-1"></i> {{ __('Existing Menus') }}
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light fs-7 text-uppercase text-muted">
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 30%;">{{ __('Name') }}</th>
                            <th style="width: 25%;">{{ __('Slug Identifier') }}</th>
                            <th style="width: 15%;">{{ __('Items') }}</th>
                            <th style="width: 10%;">{{ __('Status') }}</th>
                            <th style="width: 15%;" class="text-end pe-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menus as $menu)
                            <tr>
                                <td>{{ $menu->id }}</td>
                                <td><strong>{{ $menu->name }}</strong></td>
                                <td><code>{{ $menu->slug }}</code></td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ __(':count item(s)', ['count' => $menu->items_count]) }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $menu->is_active ? 'badge-active text-bg-success' : 'badge-disabled text-bg-warning' }} px-2 py-1">
                                        {{ $menu->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="/admin/menus/{{ $menu->id }}" class="btn btn-sm btn-secondary">
                                        <i class="bi bi-pencil-square me-1"></i> {{ __('Manage') }}
                                    </a>
                                    <form action="/admin/menus/{{ $menu->id }}" method="POST" class="actions-form" onsubmit="return confirm('{{ __('Confirm deletion?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash me-1"></i> {{ __('Delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4 small">
                                    {{ __('No navigation menus configured yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
