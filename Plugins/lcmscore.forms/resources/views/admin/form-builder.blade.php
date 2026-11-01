@extends('core::admin.layouts.app')

@section('title', ($isEdit ? __('Modifier le formulaire : ') . $form->name : __('Créer un formulaire')) . ' — Administration')

@push('styles')
    <style>
        .field-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1rem; margin-bottom: 0.75rem; transition: box-shadow 0.15s ease; }
        .field-card:hover { box-shadow: 0 4px 6px -1px rgba(0,0,0,0.06); }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="/admin/forms" class="btn btn-sm btn-outline-secondary rounded-circle" title="{{ __('Retour') }}">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0">
                    {{ $isEdit ? __('Modifier le formulaire') : __('Créer un formulaire') }}
                </h1>
            </div>
            <p class="text-muted small mb-0 mt-1">
                {{ __('Définissez les champs, les règles de validation et les paramètres de notification') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/admin/forms" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                {{ __('Annuler') }}
            </a>
            <button type="submit" form="formBuilderForm" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                <i class="bi bi-save me-1"></i> {{ $isEdit ? __('Enregistrer les modifications') : __('Créer le formulaire') }}
            </button>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger shadow-sm mb-4">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ __('Erreurs de validation :') }}</h6>
            <ul class="mb-0 ps-3 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="formBuilderForm" action="{{ $isEdit ? '/admin/forms/'.$form->id : '/admin/forms' }}" method="POST">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="row g-4">
            <!-- Left Column: Form Settings & Fields Builder -->
            <div class="col-lg-8">
                <!-- General Info Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-info-circle text-primary me-1"></i> {{ __('Informations générales') }}
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="formName" class="form-label small fw-bold text-dark text-uppercase mb-1">
                                    {{ __('Nom du formulaire') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="formName" name="name" value="{{ old('name', $form->name) }}" required
                                       class="form-control fw-semibold" placeholder="{{ __('Ex: Formulaire de Contact Commercial') }}">
                            </div>

                            <div class="col-md-5">
                                <label for="formSlug" class="form-label small fw-semibold text-secondary mb-1">
                                    {{ __('Identifiant URL (Slug)') }}
                                </label>
                                <input type="text" id="formSlug" name="slug" value="{{ old('slug', $form->slug) }}"
                                       class="form-control font-monospace" placeholder="{{ __('ex: contact-commercial') }}">
                                <div class="form-text small text-muted">
                                    {{ __('Identifiant unique utilisé pour intégrer le formulaire') }}
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="formDescription" class="form-label small fw-semibold text-secondary mb-1">
                                    {{ __('Description interne (optionnelle)') }}
                                </label>
                                <input type="text" id="formDescription" name="description" value="{{ old('description', $form->description) }}"
                                       class="form-control form-control-sm" placeholder="{{ __('Usage interne de ce formulaire') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Fields Repeater Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-list-check text-primary me-1"></i> {{ __('Champs du formulaire') }}
                        </h6>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" id="btnAddField">
                            <i class="bi bi-plus-lg me-1"></i> {{ __('Ajouter un champ') }}
                        </button>
                    </div>

                    <div class="card-body p-3 bg-light rounded-bottom-4">
                        <div id="fieldsContainer">
                            @php
                                $currentFields = old('fields', $form->fields ?? []);
                            @endphp

                            @foreach($currentFields as $index => $field)
                                <div class="field-card" data-index="{{ $index }}">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-light text-secondary border font-monospace field-badge">
                                            #{{ $index + 1 }} &bull; {{ $field['type'] ?? 'text' }}
                                        </span>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-field" title="{{ __('Supprimer ce champ') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>

                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-4">
                                            <label class="form-label small text-secondary fw-semibold mb-0">{{ __('Libellé affiché') }}</label>
                                            <input type="text" name="fields[{{ $index }}][label]" value="{{ $field['label'] ?? '' }}" required
                                                   class="form-control form-control-sm field-label-input" placeholder="{{ __('Ex: Votre Nom') }}">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label small text-secondary fw-semibold mb-0">{{ __('Nom technique') }}</label>
                                            <input type="text" name="fields[{{ $index }}][name]" value="{{ $field['name'] ?? '' }}" required
                                                   class="form-control form-control-sm font-monospace field-name-input" placeholder="{{ __('ex: nom') }}">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label small text-secondary fw-semibold mb-0">{{ __('Type de champ') }}</label>
                                            <select name="fields[{{ $index }}][type]" class="form-select form-select-sm field-type-select">
                                                <option value="text" {{ ($field['type'] ?? '') === 'text' ? 'selected' : '' }}>{{ __('Texte court') }}</option>
                                                <option value="email" {{ ($field['type'] ?? '') === 'email' ? 'selected' : '' }}>{{ __('Adresse Email') }}</option>
                                                <option value="tel" {{ ($field['type'] ?? '') === 'tel' ? 'selected' : '' }}>{{ __('Téléphone') }}</option>
                                                <option value="textarea" {{ ($field['type'] ?? '') === 'textarea' ? 'selected' : '' }}>{{ __('Zone de texte (long)') }}</option>
                                                <option value="number" {{ ($field['type'] ?? '') === 'number' ? 'selected' : '' }}>{{ __('Nombre') }}</option>
                                                <option value="select" {{ ($field['type'] ?? '') === 'select' ? 'selected' : '' }}>{{ __('Liste déroulante') }}</option>
                                                <option value="checkbox" {{ ($field['type'] ?? '') === 'checkbox' ? 'selected' : '' }}>{{ __('Case à cocher unique') }}</option>
                                                <option value="radio" {{ ($field['type'] ?? '') === 'radio' ? 'selected' : '' }}>{{ __('Boutons radio') }}</option>
                                            </select>
                                        </div>

                                        <div class="col-md-2 text-md-center pt-md-3">
                                            <div class="form-check form-check-inline m-0">
                                                <input class="form-check-input" type="checkbox" name="fields[{{ $index }}][required]" value="1" id="req_{{ $index }}"
                                                       {{ !empty($field['required']) ? 'checked' : '' }}>
                                                <label class="form-check-label small fw-semibold" for="req_{{ $index }}">{{ __('Requis') }}</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mt-2">
                                            <input type="text" name="fields[{{ $index }}][placeholder]" value="{{ $field['placeholder'] ?? '' }}"
                                                   class="form-control form-control-sm" placeholder="{{ __('Texte d\'indication (placeholder)') }}">
                                        </div>

                                        <div class="col-md-6 mt-2 options-row {{ in_array($field['type'] ?? '', ['select', 'radio']) ? '' : 'd-none' }}">
                                            @php
                                                $opts = $field['options'] ?? '';
                                                if (is_array($opts)) { $opts = implode(', ', $opts); }
                                            @endphp
                                            <input type="text" name="fields[{{ $index }}][options]" value="{{ $opts }}"
                                                   class="form-control form-control-sm" placeholder="{{ __('Options séparées par des virgules (ex: Devis, Support, Partenariat)') }}">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Submission & Notification Settings -->
            <div class="col-lg-4">
                <!-- Notifications Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-bell text-primary me-1"></i> {{ __('Notifications par e-mail') }}
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="send_notification" value="1" id="switchNotification"
                                       {{ old('send_notification', $form->send_notification) ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold" for="switchNotification">
                                    {{ __('Alerter par e-mail à chaque envoi') }}
                                </label>
                            </div>

                            <label for="recipientEmail" class="form-label small fw-semibold text-secondary mb-1">
                                {{ __('Email destinataire') }}
                            </label>
                            <input type="email" id="recipientEmail" name="recipient_email" value="{{ old('recipient_email', $form->recipient_email) }}"
                                   class="form-control form-control-sm" placeholder="{{ __('contact@votredomaine.com') }}">
                            <div class="form-text small text-muted">
                                {{ __('Reçoit une copie des informations soumises dès validation.') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Anti-Spam & Security Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-shield-check text-primary me-1"></i> {{ __('Sécurité & Anti-Spam') }}
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="honeypot_enabled" value="1" id="switchHoneypot"
                                   {{ old('honeypot_enabled', $form->honeypot_enabled) ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="switchHoneypot">
                                {{ __('Activer le pot de miel (Honeypot)') }}
                            </label>
                        </div>
                        <p class="small text-muted mb-0">
                            {{ __('Injecte un champ invisible aux humains qui piège les robots et bloque les soumissions frauduleuses sans cookie ni captcha contraignant.') }}
                        </p>
                    </div>
                </div>

                <!-- Messages & Behavior Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-chat-square-text text-primary me-1"></i> {{ __('Textes & Boutons') }}
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <label for="submitLabel" class="form-label small fw-semibold text-secondary mb-1">
                                {{ __('Libellé du bouton d\'envoi') }}
                            </label>
                            <input type="text" id="submitLabel" name="submit_label" value="{{ old('submit_label', $form->submit_label) }}"
                                   class="form-control form-control-sm" placeholder="{{ __('Envoyer le message') }}">
                        </div>

                        <div class="mb-3">
                            <label for="successMessage" class="form-label small fw-semibold text-secondary mb-1">
                                {{ __('Message de confirmation') }}
                            </label>
                            <textarea id="successMessage" name="success_message" rows="3" class="form-control form-control-sm"
                                      placeholder="{{ __('Merci ! Votre message a bien été transmis.') }}">{{ old('success_message', $form->success_message) }}</textarea>
                        </div>

                        <div class="form-check form-switch pt-2 border-top">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="switchActive"
                                   {{ old('is_active', $form->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="switchActive">
                                {{ __('Formulaire actif (accessible)') }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('formName');
    const slugInput = document.getElementById('formSlug');
    let userEditedSlug = {{ $isEdit ? 'true' : 'false' }};

    if (slugInput) {
        slugInput.addEventListener('input', () => { userEditedSlug = true; });
    }

    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            if (!userEditedSlug) {
                slugInput.value = this.value
                    .toLowerCase()
                    .trim()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });
    }

    // Dynamic fields repeater
    const container = document.getElementById('fieldsContainer');
    const btnAdd = document.getElementById('btnAddField');

    function updateFieldEvents(card) {
        // Auto tech name from label if empty
        const labelInput = card.querySelector('.field-label-input');
        const nameInput = card.querySelector('.field-name-input');
        if (labelInput && nameInput) {
            labelInput.addEventListener('input', function() {
                if (!nameInput.value || nameInput.getAttribute('data-touched') !== 'true') {
                    nameInput.value = this.value
                        .toLowerCase()
                        .trim()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .replace(/[^a-z0-9_]/g, '_')
                        .replace(/_+/g, '_')
                        .replace(/^_+|_+$/g, '');
                }
            });
            nameInput.addEventListener('input', () => nameInput.setAttribute('data-touched', 'true'));
        }

        // Show/hide options row for select/radio
        const typeSelect = card.querySelector('.field-type-select');
        const optionsRow = card.querySelector('.options-row');
        if (typeSelect && optionsRow) {
            typeSelect.addEventListener('change', function() {
                if (this.value === 'select' || this.value === 'radio') {
                    optionsRow.classList.remove('d-none');
                } else {
                    optionsRow.classList.add('d-none');
                }
            });
        }

        // Remove button
        const btnRemove = card.querySelector('.btn-remove-field');
        if (btnRemove) {
            btnRemove.addEventListener('click', function() {
                if (container.querySelectorAll('.field-card').length <= 1) {
                    alert('{{ __("Un formulaire doit comporter au moins un champ.") }}');
                    return;
                }
                card.remove();
                renumberFields();
            });
        }
    }

    function renumberFields() {
        container.querySelectorAll('.field-card').forEach((card, idx) => {
            card.setAttribute('data-index', idx);
            const badge = card.querySelector('.field-badge');
            const type = card.querySelector('.field-type-select').value;
            if (badge) badge.innerHTML = `#${idx + 1} &bull; ${type}`;

            card.querySelectorAll('input, select').forEach(el => {
                const name = el.getAttribute('name');
                if (name) {
                    el.setAttribute('name', name.replace(/fields\[\d+\]/, `fields[${idx}]`));
                }
            });
        });
    }

    // Bind existing fields
    container.querySelectorAll('.field-card').forEach(updateFieldEvents);

    // Add field
    if (btnAdd) {
        btnAdd.addEventListener('click', function() {
            const nextIdx = container.querySelectorAll('.field-card').length;
            const newCard = document.createElement('div');
            newCard.className = 'field-card';
            newCard.setAttribute('data-index', nextIdx);
            newCard.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-light text-secondary border font-monospace field-badge">
                        #${nextIdx + 1} &bull; text
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-field" title="{{ __('Supprimer ce champ') }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label small text-secondary fw-semibold mb-0">{{ __('Libellé affiché') }}</label>
                        <input type="text" name="fields[${nextIdx}][label]" required
                               class="form-control form-control-sm field-label-input" placeholder="{{ __('Nouveau champ') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-secondary fw-semibold mb-0">{{ __('Nom technique') }}</label>
                        <input type="text" name="fields[${nextIdx}][name]" required
                               class="form-control form-control-sm font-monospace field-name-input" placeholder="{{ __('nouveau_champ') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-secondary fw-semibold mb-0">{{ __('Type de champ') }}</label>
                        <select name="fields[${nextIdx}][type]" class="form-select form-select-sm field-type-select">
                            <option value="text">{{ __('Texte court') }}</option>
                            <option value="email">{{ __('Adresse Email') }}</option>
                            <option value="tel">{{ __('Téléphone') }}</option>
                            <option value="textarea">{{ __('Zone de texte (long)') }}</option>
                            <option value="number">{{ __('Nombre') }}</option>
                            <option value="select">{{ __('Liste déroulante') }}</option>
                            <option value="checkbox">{{ __('Case à cocher unique') }}</option>
                            <option value="radio">{{ __('Boutons radio') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-md-center pt-md-3">
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="checkbox" name="fields[${nextIdx}][required]" value="1" id="req_${nextIdx}">
                            <label class="form-check-label small fw-semibold" for="req_${nextIdx}">{{ __('Requis') }}</label>
                        </div>
                    </div>
                    <div class="col-md-6 mt-2">
                        <input type="text" name="fields[${nextIdx}][placeholder]" class="form-control form-control-sm" placeholder="{{ __('Placeholder') }}">
                    </div>
                    <div class="col-md-6 mt-2 options-row d-none">
                        <input type="text" name="fields[${nextIdx}][options]" class="form-control form-control-sm" placeholder="{{ __('Options séparées par des virgules') }}">
                    </div>
                </div>
            `;

            container.appendChild(newCard);
            updateFieldEvents(newCard);
            newCard.querySelector('.field-label-input').focus();
        });
    }
});
</script>
@endpush
