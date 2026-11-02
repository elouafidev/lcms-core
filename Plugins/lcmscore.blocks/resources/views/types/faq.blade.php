@php
    $title = $data['title'] ?? 'Foire Aux Questions';
    $description = $data['description'] ?? '';
    $items = $data['items'] ?? [];
    $accordionId = 'faq-accordion-' . $block->id;
@endphp

<section class="lcms-block-faq py-5 my-4">
    <div class="container" style="max-width: 800px;">
        @if($title)
            <div class="text-center mb-4">
                <h2 class="display-6 fw-bold text-dark mb-2">{{ $title }}</h2>
                @if($description)
                    <p class="text-muted">{{ $description }}</p>
                @endif
            </div>
        @endif

        <div class="accordion accordion-flush rounded-4 overflow-hidden shadow-sm border" id="{{ $accordionId }}">
            @forelse($items as $index => $item)
                @php
                    $headingId = "heading-{$block->id}-{$index}";
                    $collapseId = "collapse-{$block->id}-{$index}";
                @endphp
                <div class="accordion-item">
                    <h2 class="accordion-header" id="{{ $headingId }}">
                        <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }} fw-semibold text-dark" type="button"
                                data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                            {{ $item['question'] ?? 'Question ?' }}
                        </button>
                    </h2>
                    <div id="{{ $collapseId }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                         aria-labelledby="{{ $headingId }}" data-bs-parent="#{{ $accordionId }}">
                        <div class="accordion-body text-secondary small" style="line-height: 1.7;">
                            {{ $item['answer'] ?? '' }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-4 text-center text-muted">
                    {{ __('Aucune question/réponse configurée.') }}
                </div>
            @endforelse
        </div>
    </div>
</section>
