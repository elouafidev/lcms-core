@php
    $title = $data['title'] ?? 'Prêt à commencer ?';
    $description = $data['description'] ?? '';
    $buttonText = $data['button_text'] ?? 'Commencer maintenant';
    $buttonUrl = $data['button_url'] ?? '#';
    $bgColor = $data['background_color'] ?? '#2563eb';
@endphp

<section class="lcms-block-cta py-5 px-4 rounded-4 my-4 shadow-sm text-white text-center position-relative overflow-hidden"
         style="background-color: {{ $bgColor }};">
    <div class="container py-3">
        <h2 class="display-6 fw-bold mb-3">{{ $title }}</h2>
        @if($description)
            <p class="lead opacity-90 mb-4 mx-auto" style="max-width: 600px;">
                {{ $description }}
            </p>
        @endif
        @if($buttonText)
            <a href="{{ $buttonUrl }}" class="btn btn-light btn-lg rounded-pill px-5 fw-bold text-dark shadow-sm">
                {{ $buttonText }}
            </a>
        @endif
    </div>
</section>
