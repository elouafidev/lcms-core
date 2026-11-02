@php
    $cartService = app(\Plugins\CommerceLite\Services\CartService::class);
    $cartCount = $cartService->count();
@endphp

<a href="{{ url('/cart') }}" class="btn btn-outline-primary position-relative d-inline-flex align-items-center gap-2 rounded-pill px-3 py-2 shadow-sm">
    <i class="bi bi-cart3 fs-5"></i>
    <span class="d-none d-sm-inline fw-semibold">{{ __('Mon Panier') }}</span>
    @if($cartCount > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            {{ $cartCount }}
            <span class="visually-hidden">articles</span>
        </span>
    @endif
</a>
