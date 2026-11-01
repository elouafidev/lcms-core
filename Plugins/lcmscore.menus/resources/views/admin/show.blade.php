@extends('core::admin.layouts.app')

@section('title', $menu->name . ' — ' . __('Menu Management'))

@section('content')
    <div class="mb-3">
        <a href="/admin/menus" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i> &larr; {{ __('Back to menus') }}
        </a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">
                {{ $menu->name }} <code class="fs-6 text-muted fw-normal">({{ $menu->slug }})</code>
            </h1>
            <p class="text-muted small mb-0">{{ __('Managing structure and hierarchical links of this menu') }}</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Add Link Card -->
        <div class="col-lg-5">
            <div class="card card-outline card-primary shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-primary">
                        <i class="bi bi-link-45deg me-1"></i> {{ __('Add Link') }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="/admin/menus/{{ $menu->id }}/items" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold small">{{ __('Link Title') }}</label>
                            <input type="text" id="title" name="title" required placeholder="{{ __('ex: Home, Services, Contact...') }}" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="url" class="form-label fw-semibold small">{{ __('Target URL') }}</label>
                            <input type="text" id="url" name="url" required placeholder="{{ __('ex: / or /page/about-us') }}" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="parent_id" class="form-label fw-semibold small">{{ __('Parent Element (optional)') }}</label>
                            <select id="parent_id" name="parent_id" class="form-select">
                                <option value="">{{ __('-- No parent (Root link) --') }}</option>
                                @foreach($menu->items as $parentOption)
                                    <option value="{{ $parentOption->id }}">{{ $parentOption->title }} ({{ $parentOption->url }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="order" class="form-label fw-semibold small">{{ __('Display Order') }}</label>
                            <input type="number" id="order" name="order" value="0" class="form-control" style="max-width: 120px;">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <i class="bi bi-plus-lg"></i> {{ __('Add Link') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Links Tree Table -->
        <div class="col-lg-7">
            <div class="card card-outline card-secondary shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-secondary">
                        <i class="bi bi-diagram-3 me-1"></i> {{ __('Links Hierarchy') }}
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light fs-7 text-uppercase text-muted">
                                <tr>
                                    <th style="width: 10%;">{{ __('Order') }}</th>
                                    <th style="width: 25%;">{{ __('Link Title') }}</th>
                                    <th style="width: 25%;">{{ __('Target URL') }}</th>
                                    <th style="width: 20%;">{{ __('Parent') }}</th>
                                    <th style="width: 10%;">{{ __('Target') }}</th>
                                    <th style="width: 10%;" class="text-end pe-3">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($menu->items as $item)
                                    <tr>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $item->order }}</span>
                                        </td>
                                        <td><strong>{{ $item->title }}</strong></td>
                                        <td><code>{{ $item->url }}</code></td>
                                        <td>
                                            <span class="text-muted small">{{ $item->parent ? $item->parent->title : __('Root') }}</span>
                                        </td>
                                        <td><span class="small">{{ $item->target }}</span></td>
                                        <td class="text-end pe-3">
                                            <form action="/admin/menus/items/{{ $item->id }}" method="POST" class="actions-form" onsubmit="return confirm('{{ __('Delete this link?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4 small">
                                            {{ __('No items in this menu.') }}
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
@endsection
