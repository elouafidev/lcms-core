@php
    $title = $data['title'] ?? 'Nos Tarifs & Offres';
    $subtitle = $data['subtitle'] ?? '';
    $items = $data['items'] ?? [];
@endphp

<section class="lcms-block-pricing py-5 my-4">
    <div class="container text-center">
        @if($title)
            <h2 class="display-6 fw-bold mb-2 text-dark">{{ $title }}</h2>
        @endif
        @if($subtitle)
            <p class="text-muted mb-5 max-w-2xl mx-auto" style="max-width: 650px;">{{ $subtitle }}</p>
        @endif

        <div class="row g-4 justify-content-center text-start">
            @forelse($items as $item)
                @php $isFeatured = !empty($item['is_featured']); @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white position-relative {{ $isFeatured ? 'border-primary border-2 shadow' : '' }}">
                        @if($isFeatured)
                            <span class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-primary px-3 py-2 text-uppercase fw-semibold shadow-xs" style="font-size: 0.75rem;">
                                {{ $item['badge'] ?? 'Le plus populaire' }}
                            </span>
                        @endif

                        <h4 class="h5 fw-bold text-dark mb-1">{{ $item['name'] ?? 'Formule' }}</h4>
                        <div class="d-flex align-items-baseline gap-1 my-3">
                            <span class="display-6 fw-bold text-dark">{{ $item['price'] ?? '0€' }}</span>
                            <span class="text-muted small">{{ $item['period'] ?? '/mois' }}</span>
                        </div>

                        @if(!empty($item['description']))
                            <p class="text-muted small mb-4">{{ $item['description'] }}</p>
                        @endif

                        @php
                            $features = is_array($item['features'] ?? null) ? $item['features'] : (is_string($item['features'] ?? null) ? explode("\n", $item['features']) : []);
                        @endphp
                        <ul class="list-unstyled mb-4 flex-grow-1 small">
                            @foreach($features as $feat)
                                @if(trim($feat) !== '')
                                    <li class="mb-2 d-flex align-items-center gap-2">
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                        <span>{{ trim($feat) }}</span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>

                        <div class="d-grid mt-auto">
                            <a href="{{ $item['button_url'] ?? '#' }}" class="btn {{ $isFeatured ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill py-2 fw-semibold">
                                {{ $item['button_text'] ?? 'Choisir cette offre' }}
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted">
                    <p>{{ __('Aucune formule tarifaire configurée.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
