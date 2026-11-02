@php
    $badge = $data['badge'] ?? null;
    $title = $data['title'] ?? 'Titre du Hero';
    $subtitle = $data['subtitle'] ?? '';
    $btnPrimaryText = $data['primary_btn_text'] ?? null;
    $btnPrimaryUrl = $data['primary_btn_url'] ?? '#';
    $btnSecondaryText = $data['secondary_btn_text'] ?? null;
    $btnSecondaryUrl = $data['secondary_btn_url'] ?? '#';
    $bgImage = $data['background_image'] ?? null;
    $style = $data['style'] ?? 'dark';
    $isDark = ($style === 'dark');
@endphp

<section class="lcms-block-hero py-5 text-center position-relative overflow-hidden rounded-4 my-4 shadow-sm"
         style="{{ $bgImage ? "background: linear-gradient(rgba(15, 23, 42, 0.75), rgba(15, 23, 42, 0.75)), url('{$bgImage}') center/cover no-repeat;" : ($isDark ? 'background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);' : 'background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);') }} color: {{ $isDark || $bgImage ? '#ffffff' : '#0f172a' }};">
    <div class="container py-4">
        @if($badge)
            <span class="badge {{ $isDark || $bgImage ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-primary text-white' }} rounded-pill px-3 py-1 mb-3 text-uppercase fw-semibold" style="font-size: 0.8rem;">
                {{ $badge }}
            </span>
        @endif

        <h1 class="display-4 fw-bold mb-3" style="max-width: 800px; margin: 0 auto;">
            {{ $title }}
        </h1>

        @if($subtitle)
            <p class="lead mb-4 mx-auto opacity-75" style="max-width: 650px;">
                {{ $subtitle }}
            </p>
        @endif

        @if($btnPrimaryText || $btnSecondaryText)
            <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap mt-3">
                @if($btnPrimaryText)
                    <a href="{{ $btnPrimaryUrl }}" class="btn btn-primary btn-lg rounded-pill px-4 fw-semibold shadow-sm">
                        {{ $btnPrimaryText }}
                    </a>
                @endif
                @if($btnSecondaryText)
                    <a href="{{ $btnSecondaryUrl }}" class="btn {{ $isDark || $bgImage ? 'btn-outline-light' : 'btn-outline-secondary' }} btn-lg rounded-pill px-4 fw-semibold">
                        {{ $btnSecondaryText }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</section>
