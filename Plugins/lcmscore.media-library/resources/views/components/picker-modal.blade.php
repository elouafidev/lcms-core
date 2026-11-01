{{-- LCMS Universal Media Picker Modal Component --}}
<div class="modal fade" id="lcmsMediaPickerModal" tabindex="-1" aria-labelledby="lcmsMediaPickerModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header bg-light border-bottom px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-images text-primary fs-4"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="lcmsMediaPickerModalLabel">{{ __('Sélectionner un média') }}</h5>
                        <p class="text-muted small mb-0">{{ __('Parcourez la médiathèque ou téléversez un nouveau fichier') }}</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Fermer') }}"></button>
            </div>

            <!-- Modal Nav Tabs -->
            <div class="px-4 pt-3 pb-0 bg-light border-bottom">
                <ul class="nav nav-tabs border-0" id="lcmsPickerTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold" id="lcms-tab-browse" data-bs-toggle="tab" data-bs-target="#lcms-pane-browse" type="button" role="tab" aria-selected="true">
                            <i class="bi bi-grid-fill me-1"></i> {{ __('Médiathèque') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="lcms-tab-upload" data-bs-toggle="tab" data-bs-target="#lcms-pane-upload" type="button" role="tab" aria-selected="false">
                            <i class="bi bi-cloud-arrow-up-fill me-1"></i> {{ __('Téléverser un fichier') }}
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-0">
                <div class="tab-content h-100" id="lcmsPickerTabsContent">
                    <!-- Browse Tab -->
                    <div class="tab-pane fade show active p-4" id="lcms-pane-browse" role="tabpanel" aria-labelledby="lcms-tab-browse">
                        <!-- Search & Filter Bar -->
                        <div class="row g-2 mb-3 align-items-center">
                            <div class="col-md-6 col-lg-5">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="lcmsPickerSearch" class="form-control border-start-0" placeholder="{{ __('Rechercher par nom...') }}">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-7 text-md-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary active lcms-picker-filter-btn" data-type="all">{{ __('Tous') }}</button>
                                    <button type="button" class="btn btn-outline-secondary lcms-picker-filter-btn" data-type="image">{{ __('Images') }}</button>
                                    <button type="button" class="btn btn-outline-secondary lcms-picker-filter-btn" data-type="document">{{ __('Documents') }}</button>
                                </div>
                            </div>
                        </div>

                        <!-- Grid & Preview Sidebar -->
                        <div class="row g-3" style="min-height: 380px;">
                            <!-- Media Items Grid -->
                            <div class="col-lg-8 col-xl-9">
                                <div id="lcmsPickerLoading" class="text-center py-5 d-none">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">{{ __('Chargement...') }}</span>
                                    </div>
                                    <p class="small text-muted mt-2">{{ __('Chargement des médias...') }}</p>
                                </div>

                                <div id="lcmsPickerGrid" class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-2 overflow-auto" style="max-height: 440px;">
                                    <!-- Populated via JavaScript -->
                                </div>

                                <div id="lcmsPickerEmpty" class="text-center py-5 text-muted d-none">
                                    <i class="bi bi-images fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                    <p class="mb-0 fw-medium">{{ __('Aucun média trouvé.') }}</p>
                                </div>
                            </div>

                            <!-- Selected Item Details Sidebar -->
                            <div class="col-lg-4 col-xl-3">
                                <div class="card h-100 border shadow-none bg-light p-3" id="lcmsPickerSidebar">
                                    <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary small text-uppercase">
                                        {{ __('Détails du média') }}
                                    </h6>
                                    <div id="lcmsPickerNoSelection" class="text-center text-muted py-4 small">
                                        <i class="bi bi-cursor text-secondary fs-3 d-block mb-1 opacity-50"></i>
                                        {{ __('Cliquez sur un média pour afficher ses détails et le sélectionner.') }}
                                    </div>

                                    <div id="lcmsPickerSelectionDetails" class="d-none">
                                        <div class="text-center bg-white rounded p-2 border mb-3" style="height: 140px; display: flex; align-items: center; justify-content: center;">
                                            <img id="lcmsPickerPreviewImg" src="" alt="" class="img-fluid" style="max-height: 130px; object-fit: contain;">
                                            <div id="lcmsPickerPreviewIcon" class="d-none">
                                                <i class="bi bi-file-earmark-text text-secondary display-5"></i>
                                            </div>
                                        </div>

                                        <p class="fw-bold mb-1 small text-truncate" id="lcmsPickerDetailsName"></p>
                                        <div class="text-muted small mb-2">
                                            <span id="lcmsPickerDetailsSize"></span> &bull; <span id="lcmsPickerDetailsMime"></span>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold mb-1">{{ __('Texte alternatif (Alt)') }}</label>
                                            <input type="text" id="lcmsPickerDetailsAlt" class="form-control form-control-sm" placeholder="{{ __('Description pour le SEO') }}">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold mb-1">{{ __('URL directe') }}</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" id="lcmsPickerDetailsUrl" class="form-control" readonly>
                                                <button class="btn btn-outline-secondary" type="button" id="lcmsPickerBtnCopyUrl" title="{{ __('Copier') }}">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Tab -->
                    <div class="tab-pane fade p-4" id="lcms-pane-upload" role="tabpanel" aria-labelledby="lcms-tab-upload">
                        <div class="card border-2 border-dashed p-5 text-center bg-light rounded-4" id="lcmsPickerDropzone" style="border-color: #cbd5e1; cursor: pointer;">
                            <i class="bi bi-cloud-arrow-up display-3 text-primary opacity-75 mb-3 d-block"></i>
                            <h5 class="fw-bold mb-1">{{ __('Glissez et déposez vos fichiers ici') }}</h5>
                            <p class="text-muted small mb-3">{{ __('ou cliquez pour parcourir les dossiers de votre ordinateur') }}</p>
                            <div>
                                <input type="file" id="lcmsPickerFileInput" class="d-none" accept="image/*,.pdf,.svg">
                                <button type="button" class="btn btn-primary px-4 rounded-pill" onclick="document.getElementById('lcmsPickerFileInput').click()">
                                    <i class="bi bi-folder2-open me-1"></i> {{ __('Choisir un fichier') }}
                                </button>
                            </div>
                            <div class="small text-muted mt-3">
                                {{ __('Formats supportés : JPG, PNG, GIF, SVG, WebP, PDF. Taille maximale : 10 Mo.') }}
                            </div>

                            <div id="lcmsPickerUploadProgress" class="progress mt-4 d-none" style="height: 8px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-top px-4 py-2 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">
                    {{ __('Annuler') }}
                </button>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold" id="lcmsPickerBtnSelect" disabled>
                    <i class="bi bi-check-lg me-1"></i> {{ __('Insérer le média sélectionné') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    if (window.LcmsMediaPicker) return;

    window.LcmsMediaPicker = {
        modalInstance: null,
        currentCallback: null,
        selectedItem: null,
        itemsCache: [],
        filterType: 'all',
        searchTerm: '',

        init: function() {
            const modalEl = document.getElementById('lcmsMediaPickerModal');
            if (!modalEl) return;

            // Bootstrap modal instance
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                this.modalInstance = new bootstrap.Modal(modalEl);
            }

            // Events
            const searchInput = document.getElementById('lcmsPickerSearch');
            if (searchInput) {
                let debounceTimeout = null;
                searchInput.addEventListener('input', (e) => {
                    clearTimeout(debounceTimeout);
                    debounceTimeout = setTimeout(() => {
                        this.searchTerm = e.target.value.trim();
                        this.loadItems();
                    }, 300);
                });
            }

            // Filter buttons
            document.querySelectorAll('.lcms-picker-filter-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    document.querySelectorAll('.lcms-picker-filter-btn').forEach(b => b.classList.remove('active'));
                    e.currentTarget.classList.add('active');
                    this.filterType = e.currentTarget.getAttribute('data-type') || 'all';
                    this.loadItems();
                });
            });

            // Select button
            const selectBtn = document.getElementById('lcmsPickerBtnSelect');
            if (selectBtn) {
                selectBtn.addEventListener('click', () => {
                    if (this.selectedItem && typeof this.currentCallback === 'function') {
                        // Include any updated alt text
                        const altInput = document.getElementById('lcmsPickerDetailsAlt');
                        if (altInput) {
                            this.selectedItem.alt_text = altInput.value;
                        }
                        this.currentCallback(this.selectedItem);
                    }
                    if (this.modalInstance) {
                        this.modalInstance.hide();
                    }
                });
            }

            // Copy url button
            const copyBtn = document.getElementById('lcmsPickerBtnCopyUrl');
            if (copyBtn) {
                copyBtn.addEventListener('click', () => {
                    const urlInput = document.getElementById('lcmsPickerDetailsUrl');
                    if (urlInput && urlInput.value) {
                        navigator.clipboard.writeText(urlInput.value).then(() => {
                            copyBtn.innerHTML = '<i class="bi bi-check text-success"></i>';
                            setTimeout(() => copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>', 1500);
                        });
                    }
                });
            }

            // File upload handling
            const fileInput = document.getElementById('lcmsPickerFileInput');
            if (fileInput) {
                fileInput.addEventListener('change', (e) => {
                    if (e.target.files && e.target.files[0]) {
                        this.uploadFile(e.target.files[0]);
                    }
                });
            }

            // Drag and drop zone
            const dropzone = document.getElementById('lcmsPickerDropzone');
            if (dropzone) {
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.add('border-primary', 'bg-white');
                    });
                });
                ['dragleave', 'drop'].forEach(eventName => {
                    dropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.remove('border-primary', 'bg-white');
                    });
                });
                dropzone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    if (dt && dt.files && dt.files[0]) {
                        this.uploadFile(dt.files[0]);
                    }
                });
            }

            // Global delegation for data-lcms-media-picker buttons
            document.addEventListener('click', (e) => {
                const trigger = e.target.closest('[data-lcms-media-picker]');
                if (!trigger) return;

                e.preventDefault();
                const targetInput = document.querySelector(trigger.getAttribute('data-target-input'));
                const targetPreview = document.querySelector(trigger.getAttribute('data-target-preview'));
                const filterType = trigger.getAttribute('data-type') || 'image';

                this.open({ type: filterType }, (media) => {
                    if (targetInput) {
                        targetInput.value = media.url || media.file_path;
                        targetInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    if (targetPreview) {
                        if (targetPreview.tagName === 'IMG') {
                            targetPreview.src = media.url;
                            targetPreview.classList.remove('d-none');
                        } else {
                            targetPreview.style.backgroundImage = `url("${media.url}")`;
                        }
                    }
                });
            });
        },

        open: function(options, callback) {
            this.currentCallback = callback;
            this.selectedItem = null;
            this.filterType = (options && options.type) ? options.type : 'all';
            this.searchTerm = '';

            const searchInput = document.getElementById('lcmsPickerSearch');
            if (searchInput) searchInput.value = '';

            document.querySelectorAll('.lcms-picker-filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-type') === this.filterType);
            });

            this.updateSelectionSidebar(null);

            // Switch to browse tab
            const browseTab = document.getElementById('lcms-tab-browse');
            if (browseTab && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                new bootstrap.Tab(browseTab).show();
            }

            this.loadItems();

            if (this.modalInstance) {
                this.modalInstance.show();
            }
        },

        loadItems: function() {
            const grid = document.getElementById('lcmsPickerGrid');
            const loading = document.getElementById('lcmsPickerLoading');
            const empty = document.getElementById('lcmsPickerEmpty');

            if (loading) loading.classList.remove('d-none');
            if (grid) grid.innerHTML = '';
            if (empty) empty.classList.add('d-none');

            const url = new URL('/admin/media', window.location.origin);
            if (this.searchTerm) url.searchParams.set('search', this.searchTerm);
            if (this.filterType && this.filterType !== 'all') url.searchParams.set('type', this.filterType);
            url.searchParams.set('per_page', '36');

            fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(res => {
                if (loading) loading.classList.add('d-none');
                const items = (res && res.data) ? res.data : [];
                this.itemsCache = items;
                this.renderGrid(items);
            })
            .catch(err => {
                if (loading) loading.classList.add('d-none');
                if (empty) empty.classList.remove('d-none');
                console.error('Error loading media items:', err);
            });
        },

        renderGrid: function(items) {
            const grid = document.getElementById('lcmsPickerGrid');
            const empty = document.getElementById('lcmsPickerEmpty');
            if (!grid) return;

            grid.innerHTML = '';

            if (!items || items.length === 0) {
                if (empty) empty.classList.remove('d-none');
                return;
            }

            if (empty) empty.classList.add('d-none');

            items.forEach(item => {
                const col = document.createElement('div');
                col.className = 'col';

                const card = document.createElement('div');
                card.className = 'card h-100 border text-center p-1 rounded-3 position-relative lcms-media-card';
                card.style.cursor = 'pointer';
                card.style.transition = 'all 0.15s ease-in-out';
                card.setAttribute('data-id', item.id);

                let previewHtml = '';
                if (item.is_image || (item.mime_type && item.mime_type.startsWith('image/'))) {
                    previewHtml = `<div style="height: 90px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border-radius: 0.35rem; overflow: hidden;">
                        <img src="${item.url}" alt="${item.alt_text || item.name}" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    </div>`;
                } else {
                    previewHtml = `<div style="height: 90px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border-radius: 0.35rem;">
                        <i class="bi bi-file-earmark-text text-secondary fs-2"></i>
                    </div>`;
                }

                card.innerHTML = `
                    ${previewHtml}
                    <div class="p-1">
                        <p class="small text-truncate mb-0 fw-medium" style="font-size: 0.75rem;" title="${item.name}">${item.name}</p>
                    </div>
                `;

                card.addEventListener('click', () => {
                    document.querySelectorAll('.lcms-media-card').forEach(c => {
                        c.classList.remove('border-primary', 'shadow-sm', 'bg-light');
                        c.style.borderWidth = '1px';
                    });
                    card.classList.add('border-primary', 'shadow-sm', 'bg-light');
                    card.style.borderWidth = '2px';
                    this.updateSelectionSidebar(item);
                });

                // Double click selects directly
                card.addEventListener('dblclick', () => {
                    this.updateSelectionSidebar(item);
                    const selectBtn = document.getElementById('lcmsPickerBtnSelect');
                    if (selectBtn) selectBtn.click();
                });

                col.appendChild(card);
                grid.appendChild(col);
            });
        },

        updateSelectionSidebar: function(item) {
            this.selectedItem = item;
            const noSelection = document.getElementById('lcmsPickerNoSelection');
            const selectionDetails = document.getElementById('lcmsPickerSelectionDetails');
            const selectBtn = document.getElementById('lcmsPickerBtnSelect');

            if (!item) {
                if (noSelection) noSelection.classList.remove('d-none');
                if (selectionDetails) selectionDetails.classList.add('d-none');
                if (selectBtn) selectBtn.disabled = true;
                return;
            }

            if (noSelection) noSelection.classList.add('d-none');
            if (selectionDetails) selectionDetails.classList.remove('d-none');
            if (selectBtn) selectBtn.disabled = false;

            const previewImg = document.getElementById('lcmsPickerPreviewImg');
            const previewIcon = document.getElementById('lcmsPickerPreviewIcon');
            const isImg = item.is_image || (item.mime_type && item.mime_type.startsWith('image/'));

            if (isImg && previewImg) {
                previewImg.src = item.url;
                previewImg.classList.remove('d-none');
                if (previewIcon) previewIcon.classList.add('d-none');
            } else if (previewIcon) {
                if (previewImg) previewImg.classList.add('d-none');
                previewIcon.classList.remove('d-none');
            }

            const nameEl = document.getElementById('lcmsPickerDetailsName');
            if (nameEl) nameEl.textContent = item.name;

            const sizeEl = document.getElementById('lcmsPickerDetailsSize');
            if (sizeEl) sizeEl.textContent = item.formatted_size || `${Math.round(item.size / 1024)} KB`;

            const mimeEl = document.getElementById('lcmsPickerDetailsMime');
            if (mimeEl) mimeEl.textContent = item.mime_type;

            const altEl = document.getElementById('lcmsPickerDetailsAlt');
            if (altEl) altEl.value = item.alt_text || '';

            const urlEl = document.getElementById('lcmsPickerDetailsUrl');
            if (urlEl) urlEl.value = item.url;
        },

        uploadFile: function(file) {
            const progress = document.getElementById('lcmsPickerUploadProgress');
            const progressBar = progress ? progress.querySelector('.progress-bar') : null;

            if (progress) progress.classList.remove('d-none');
            if (progressBar) progressBar.style.width = '30%';

            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('/admin/media/upload', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (progressBar) progressBar.style.width = '100%';
                setTimeout(() => {
                    if (progress) progress.classList.add('d-none');
                    if (progressBar) progressBar.style.width = '0%';

                    // Switch back to browse tab and reload
                    const browseTab = document.getElementById('lcms-tab-browse');
                    if (browseTab && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                        new bootstrap.Tab(browseTab).show();
                    }

                    this.loadItems();

                    // Pre-select newly uploaded item if returned
                    if (res && res.data) {
                        setTimeout(() => this.updateSelectionSidebar(res.data), 300);
                    }
                }, 500);
            })
            .catch(err => {
                if (progress) progress.classList.add('d-none');
                alert('{{ __("Erreur lors du téléversement du fichier.") }}');
                console.error(err);
            });
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.LcmsMediaPicker.init();
    });
})();
</script>
