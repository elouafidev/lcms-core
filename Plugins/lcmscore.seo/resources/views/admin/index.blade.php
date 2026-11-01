@extends('core::admin.layouts.app')

@section('title', __('SEO Configuration — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('SEO Configuration') }}</h1>
            <p class="text-muted small mb-0">
                {{ __('Default settings for metadata, OpenGraph tags and robots') }}
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-xl-7">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold fs-6 mb-0 text-primary">
                        <i class="bi bi-search me-1"></i> {{ __('Default Settings') }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="/admin/seo" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="site_name" class="form-label fw-semibold small">{{ __('Site Name') }}</label>
                            <input type="text" id="site_name" name="site_name" value="{{ $settings['site_name'] ?? '' }}" placeholder="{{ __('ex: My Super CMS') }}" class="form-control">
                            <div class="form-text small text-muted">{{ __('Used as title suffix and in og:site_name tags.') }}</div>
                        </div>
                        <div class="mb-3">
                            <label for="default_title" class="form-label fw-semibold small">{{ __('Default Title (meta title / og:title)') }}</label>
                            <input type="text" id="default_title" name="default_title" value="{{ $settings['default_title'] ?? '' }}" placeholder="{{ __('ex: Home - My Super CMS') }}" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="default_description" class="form-label fw-semibold small">{{ __('Default Meta Description') }}</label>
                            <textarea id="default_description" name="default_description" rows="3" placeholder="{{ __('General description of the website') }}" class="form-control">{{ $settings['default_description'] ?? '' }}</textarea>
                        </div>
                        <div class="mb-4">
                            <label for="canonical_base" class="form-label fw-semibold small">{{ __('Canonical Base URL (optional)') }}</label>
                            <input type="url" id="canonical_base" name="canonical_base" value="{{ $settings['canonical_base'] ?? '' }}" placeholder="https://example.com" class="form-control">
                            <div class="form-text small text-muted">{{ __('Prefix used to generate absolute canonical tags.') }}</div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <i class="bi bi-save"></i> {{ __('Save SEO Configuration') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
