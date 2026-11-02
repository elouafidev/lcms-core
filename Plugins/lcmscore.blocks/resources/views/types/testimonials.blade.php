@php
    $title = $data['title'] ?? 'Ce que disent nos clients';
    $subtitle = $data['subtitle'] ?? '';
    $items = $data['items'] ?? [];
@endphp

<section class="lcms-block-testimonials py-5 my-4">
    <div class="container text-center">
        @if($title)
            <h2 class="display-6 fw-bold text-dark mb-2">{{ $title }}</h2>
        @endif
        @if($subtitle)
            <p class="text-muted mb-5 max-w-2xl mx-auto" style="max-width: 650px;">{{ $subtitle }}</p>
        @endif

        <div class="row g-4 text-start">
            @forelse($items as $item)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white d-flex flex-column">
                        <div class="mb-3 text-warning">
                            @php $rating = (int)($item['rating'] ?? 5); @endphp
                            @for($i = 0; $i < $rating; $i++)
                                <i class="bi bi-star-fill"></i>
                            @endfor
                        </div>
                        <p class="text-secondary small fst-italic mb-4 flex-grow-1" style="line-height: 1.6;">
                            « {{ $item['quote'] ?? '' }} »
                        </p>
                        <div class="d-flex align-items-center gap-3 pt-3 border-top mt-auto">
                            @if(!empty($item['avatar']))
                                <img src="{{ $item['avatar'] }}" alt="{{ $item['author'] ?? '' }}" class="rounded-circle shadow-xs" style="width: 44px; height: 44px; object-fit: cover;">
                            @else
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px;">
                                    {{ substr($item['author'] ?? 'U', 0, 1) }}
                                </div>
                            @endif
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">{{ $item['author'] ?? 'Client' }}</h6>
                                <span class="text-muted" style="font-size: 0.75rem;">{{ $item['role'] ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted">
                    <p>{{ __('Aucun témoignage enregistré.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
