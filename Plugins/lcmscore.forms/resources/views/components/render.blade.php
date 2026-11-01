@props([
    'form' => null,
    'slug' => null,
])

@php
    if (!$form && $slug) {
        $form = \Plugins\Forms\Models\Form::query()->where('slug', $slug)->where('is_active', true)->first();
    }
@endphp

@if($form && $form->is_active)
    <div class="lcms-form-wrapper" id="form-{{ $form->slug }}">
        @if(session('form_success') && session('submitted_form_slug') === $form->slug)
            <div class="alert alert-success alert-dismissible fade show p-3 rounded-3 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                    <div>{{ session('form_success') }}</div>
                </div>
            </div>
        @else
            <form action="/forms/{{ $form->slug }}/submit" method="POST" class="lcms-dynamic-form needs-validation" novalidate>
                @csrf

                {{-- Invisible Honeypot field for bot mitigation --}}
                @if($form->honeypot_enabled)
                    <div style="position: absolute !important; left: -9999px !important; top: -9999px !important; opacity: 0 !important; height: 0 !important; overflow: hidden !important;" aria-hidden="true" tabindex="-1">
                        <label for="_hp_website_{{ $form->slug }}">Ne pas remplir ce champ</label>
                        <input type="text" name="_hp_website" id="_hp_website_{{ $form->slug }}" autocomplete="off" tabindex="-1">
                    </div>
                @endif

                <div class="row g-3">
                    @foreach($form->fields as $field)
                        @php
                            $fieldName = $field['name'];
                            $fieldType = $field['type'] ?? 'text';
                            $fieldLabel = $field['label'] ?? ucfirst($fieldName);
                            $isRequired = !empty($field['required']);
                            $placeholder = $field['placeholder'] ?? '';
                            $colClass = in_array($fieldType, ['textarea', 'checkbox']) ? 'col-12' : 'col-md-6';
                            $hasError = isset($errors) && $errors->has($fieldName);
                            $errorMsg = $hasError ? $errors->first($fieldName) : null;
                        @endphp

                        <div class="{{ $colClass }}">
                            @if($fieldType !== 'checkbox')
                                <label for="field_{{ $form->slug }}_{{ $fieldName }}" class="form-label fw-semibold small text-dark mb-1">
                                    {{ $fieldLabel }}
                                    @if($isRequired)
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                            @endif

                            @if($fieldType === 'textarea')
                                <textarea name="{{ $fieldName }}"
                                          id="field_{{ $form->slug }}_{{ $fieldName }}"
                                          rows="4"
                                          class="form-control {{ $hasError ? 'is-invalid' : '' }}"
                                          placeholder="{{ $placeholder }}"
                                          {{ $isRequired ? 'required' : '' }}>{{ old($fieldName) }}</textarea>

                            @elseif($fieldType === 'select')
                                <select name="{{ $fieldName }}"
                                        id="field_{{ $form->slug }}_{{ $fieldName }}"
                                        class="form-select {{ $hasError ? 'is-invalid' : '' }}"
                                        {{ $isRequired ? 'required' : '' }}>
                                    <option value="">{{ $placeholder ?: __('Sélectionnez une option') }}</option>
                                    @if(!empty($field['options']) && is_array($field['options']))
                                        @foreach($field['options'] as $opt)
                                            <option value="{{ $opt }}" {{ old($fieldName) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    @endif
                                </select>

                            @elseif($fieldType === 'checkbox')
                                <div class="form-check mt-2">
                                    <input class="form-check-input {{ $hasError ? 'is-invalid' : '' }}"
                                           type="checkbox"
                                           name="{{ $fieldName }}"
                                           value="1"
                                           id="field_{{ $form->slug }}_{{ $fieldName }}"
                                           {{ old($fieldName) ? 'checked' : '' }}
                                           {{ $isRequired ? 'required' : '' }}>
                                    <label class="form-check-label small fw-semibold text-dark" for="field_{{ $form->slug }}_{{ $fieldName }}">
                                        {{ $fieldLabel }}
                                        @if($isRequired)
                                            <span class="text-danger">*</span>
                                        @endif
                                    </label>
                                </div>

                            @elseif($fieldType === 'radio')
                                @if(!empty($field['options']) && is_array($field['options']))
                                    <div class="d-flex flex-wrap gap-3 mt-1">
                                        @foreach($field['options'] as $rIdx => $opt)
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       type="radio"
                                                       name="{{ $fieldName }}"
                                                       id="field_{{ $form->slug }}_{{ $fieldName }}_{{ $rIdx }}"
                                                       value="{{ $opt }}"
                                                       {{ old($fieldName) === $opt ? 'checked' : '' }}
                                                       {{ $isRequired ? 'required' : '' }}>
                                                <label class="form-check-label small" for="field_{{ $form->slug }}_{{ $fieldName }}_{{ $rIdx }}">
                                                    {{ $opt }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                            @else
                                <input type="{{ $fieldType === 'number' ? 'number' : ($fieldType === 'tel' ? 'tel' : ($fieldType === 'email' ? 'email' : 'text')) }}"
                                       name="{{ $fieldName }}"
                                       id="field_{{ $form->slug }}_{{ $fieldName }}"
                                       value="{{ old($fieldName) }}"
                                       class="form-control {{ $hasError ? 'is-invalid' : '' }}"
                                       placeholder="{{ $placeholder }}"
                                       {{ $isRequired ? 'required' : '' }}>
                            @endif

                            @if($hasError && $errorMsg)
                                <div class="invalid-feedback d-block small mt-1">
                                    {{ $errorMsg }}
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm">
                            <i class="bi bi-send me-1"></i> {{ $form->submit_label ?: __('Envoyer') }}
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
@endif
