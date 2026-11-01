@props([
    'name' => 'featured_image',
    'value' => null,
    'label' => __('Image à la une'),
    'id' => null,
    'type' => 'image',
])

@php
    $fieldId = $id ?? 'media_field_'.md5($name.uniqid());
    $previewId = $fieldId.'_preview';
    $inputId = $fieldId.'_input';
    $hasValue = !empty($value);
@endphp

<div class="lcms-media-picker-field mb-3" id="{{ $fieldId }}">
    @if($label)
        <label for="{{ $inputId }}" class="form-label fw-semibold small text-secondary">{{ $label }}</label>
    @endif

    <div class="d-flex align-items-center gap-3">
        <!-- Preview Box -->
        <div class="border rounded bg-light d-flex align-items-center justify-content-center overflow-hidden position-relative shadow-sm"
             style="width: 110px; height: 90px; flex-shrink: 0;">
            <img id="{{ $previewId }}"
                 src="{{ $value ?? '' }}"
                 alt="{{ __('Aperçu') }}"
                 class="img-fluid {{ $hasValue ? '' : 'd-none' }}"
                 style="max-height: 100%; max-width: 100%; object-fit: contain;">
            <i class="bi bi-image text-muted fs-2 {{ $hasValue ? 'd-none' : '' }}" id="{{ $previewId }}_placeholder"></i>
        </div>

        <!-- Hidden input storing URL/Path -->
        <input type="hidden"
               name="{{ $name }}"
               id="{{ $inputId }}"
               value="{{ $value ?? '' }}">

        <!-- Action Buttons -->
        <div>
            <div class="d-flex gap-2">
                <button type="button"
                        class="btn btn-outline-primary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1"
                        data-lcms-media-picker
                        data-target-input="#{{ $inputId }}"
                        data-target-preview="#{{ $previewId }}"
                        data-type="{{ $type }}">
                    <i class="bi bi-folder2-open"></i> {{ __('Choisir un média') }}
                </button>

                <button type="button"
                        class="btn btn-outline-danger btn-sm rounded-pill px-2 {{ $hasValue ? '' : 'd-none' }}"
                        id="{{ $fieldId }}_btn_clear"
                        onclick="
                            document.getElementById('{{ $inputId }}').value = '';
                            document.getElementById('{{ $previewId }}').src = '';
                            document.getElementById('{{ $previewId }}').classList.add('d-none');
                            document.getElementById('{{ $previewId }}_placeholder').classList.remove('d-none');
                            this.classList.add('d-none');
                        "
                        title="{{ __('Supprimer l\'image sélectionnée') }}">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="form-text small text-muted mt-1">
                {{ __('Sélectionnez ou téléversez une image via la médiathèque.') }}
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('{{ $inputId }}');
    const clearBtn = document.getElementById('{{ $fieldId }}_btn_clear');
    const placeholder = document.getElementById('{{ $previewId }}_placeholder');
    const preview = document.getElementById('{{ $previewId }}');

    if (input) {
        input.addEventListener('change', function() {
            if (this.value) {
                if (clearBtn) clearBtn.classList.remove('d-none');
                if (placeholder) placeholder.classList.add('d-none');
                if (preview) preview.classList.remove('d-none');
            } else {
                if (clearBtn) clearBtn.classList.add('d-none');
                if (placeholder) placeholder.classList.remove('d-none');
                if (preview) preview.classList.add('d-none');
            }
        });
    }
});
</script>
