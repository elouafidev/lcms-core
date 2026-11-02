@php
    $title = $data['title'] ?? 'Nos Fonctionnalités';
    $subtitle = $data['subtitle'] ?? '';
    $items = $data['items'] ?? [];
@endphp

<section class="lcms-block-features py-5 my-4">
    <div class="container text-center">
        @if($title)
            <h2 class="display-6 fw-bold mb-2 text-dark">{{ $title }}</h2>
        @endif
        @if($subtitle)
            <p class="text-muted mb-5 max-w-2xl mx-auto" style="max-width: 650px;">{{ $subtitle }}</p>
        @endif

        <div class="row g-4 text-start">
            @forelse($items as $item)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                        <div class="bg-primary-subtle text-primary rounded-3 d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                            <i class="{{ $item['icon'] ?? 'bi bi-check2-circle' }} fs-4"></i>
                        </div>
                        <h4 class="h5 fw-bold text-dark mb-2">{{ $item['title'] ?? 'Titre' }}</h4>
                        <p class="text-muted small mb-0">{{ $item['description'] ?? '' }}</p>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted">
                    <p>{{ __('Aucune fonctionnalité configurée.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
