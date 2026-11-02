@extends('core::admin.layouts.app')

@section('title', __('Modifier le Produit — Administration'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ __('Modifier le Produit') }}</h1>
            <p class="text-muted small mb-0">{{ __('Mettez à jour les informations, le tarif et les stocks du produit') }}</p>
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
                <form action="{{ url('/admin/commerce/products/' . $product->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('Nom du produit') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="form-control" placeholder="T-shirt Coton Bio LCMS">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small">{{ __('Référence unique (SKU)') }} <span class="text-danger">*</span></label>
                            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required class="form-control font-monospace text-uppercase">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small">{{ __('Slug URL (Optionnel)') }}</label>
                            <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" class="form-control font-monospace">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small">{{ __('Prix de vente (€ TTC)') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="price" value="{{ old('price', number_format($product->price / 100, 2, '.', '')) }}" step="0.01" min="0" required class="form-control font-monospace">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small">{{ __('Stock disponible') }} <span class="text-danger">*</span></label>
                            <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" min="0" required class="form-control font-monospace">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('URL Image du produit') }}</label>
                        <input type="text" name="image" value="{{ old('image', $product->image) }}" class="form-control" placeholder="https://example.com/photo.jpg">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('Description détaillée') }}</label>
                        <textarea name="description" rows="4" class="form-control">{{ old('description', $product->description) }}</textarea>
                    </div>

                    <div class="mb-4 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold small" for="isActiveSwitch">
                            {{ __('Rendre ce produit visible et disponible à la vente') }}
                        </label>
                    </div>

                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                        <span class="text-muted small">
                            {{ __('Créé le') }} {{ $product->created_at?->format('d/m/Y H:i') }}
                        </span>
                        <div class="d-flex gap-2">
                            <a href="{{ url('/admin/commerce/products') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-4">{{ __('Annuler') }}</a>
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold">{{ __('Mettre à jour') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
