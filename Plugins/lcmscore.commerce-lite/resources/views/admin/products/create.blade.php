@extends('core::admin.layouts.app')

@section('title', __('Nouveau Produit — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Nouveau Produit') }}</h1>
            <p class="text-muted small mb-0">{{ __('Ajoutez un produit au catalogue de votre boutique en ligne') }}</p>
        </div>
        <div>
            <a href="{{ url('/admin/commerce/products') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> {{ __('Retour au catalogue') }}
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger shadow-sm mb-4 alert-dismissible fade show">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <form action="{{ url('/admin/commerce/products') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('Nom du produit') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="form-control" placeholder="T-shirt Coton Bio LCMS">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small">{{ __('Référence unique (SKU)') }} <span class="text-danger">*</span></label>
                            <input type="text" name="sku" value="{{ old('sku') }}" required class="form-control font-monospace text-uppercase" placeholder="TSH-BIO-01">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small">{{ __('Slug URL (Optionnel)') }}</label>
                            <input type="text" name="slug" value="{{ old('slug') }}" class="form-control font-monospace" placeholder="t-shirt-coton-bio">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small">{{ __('Prix de vente (€ TTC)') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="price" value="{{ old('price', '29.90') }}" step="0.01" min="0" required class="form-control font-monospace">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small">{{ __('Stock disponible') }} <span class="text-danger">*</span></label>
                            <input type="number" name="stock" value="{{ old('stock', '50') }}" min="0" required class="form-control font-monospace">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('URL Image du produit') }}</label>
                        <input type="text" name="image" value="{{ old('image') }}" class="form-control" placeholder="https://example.com/photo.jpg ou /storage/media/...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('Description détaillée') }}</label>
                        <textarea name="description" rows="4" class="form-control" placeholder="Description du produit, matières, caractéristiques...">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-4 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold small" for="isActiveSwitch">
                            {{ __('Rendre ce produit visible et disponible à la vente') }}
                        </label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ url('/admin/commerce/products') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-4">{{ __('Annuler') }}</a>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold">{{ __('Enregistrer le produit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
