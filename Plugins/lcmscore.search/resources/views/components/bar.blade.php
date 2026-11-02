<div class="lcms-search-bar-wrapper position-relative" style="max-width: 450px;">
    <form action="{{ url('/search') }}" method="GET" class="position-relative">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" name="q" id="lcmsSearchInput" value="{{ request('q', '') }}"
                   class="form-control border-start-0 ps-0" placeholder="{{ __('Rechercher sur le site...') }}"
                   autocomplete="off" oninput="lcmsLiveSearch(this.value)">
            <button type="submit" class="btn btn-primary px-3">{{ __('Chercher') }}</button>
        </div>
    </form>

    <!-- Instant Live Suggestions Dropdown -->
    <div id="lcmsLiveResults" class="position-absolute w-100 bg-white rounded-3 shadow-lg border mt-1 d-none" style="z-index: 1050; max-height: 380px; overflow-y: auto;">
        <div id="lcmsLiveContent" class="list-group list-group-flush small"></div>
    </div>
</div>

@push('scripts')
    <script>
        let lcmsSearchTimeout = null;

        function lcmsLiveSearch(val) {
            clearTimeout(lcmsSearchTimeout);
            const container = document.getElementById('lcmsLiveResults');
            const content = document.getElementById('lcmsLiveContent');

            if (!val || val.trim().length < 2) {
                container.classList.add('d-none');
                return;
            }

            lcmsSearchTimeout = setTimeout(() => {
                fetch('{{ url('/search/live') }}?q=' + encodeURIComponent(val.trim()))
                    .then(res => res.json())
                    .then(data => {
                        content.innerHTML = '';
                        if (!data.results || data.results.length === 0) {
                            content.innerHTML = '<div class="p-3 text-center text-muted">{{ __('Aucun résultat trouvé.') }}</div>';
                        } else {
                            data.results.forEach(item => {
                                const a = document.createElement('a');
                                a.href = item.url;
                                a.className = 'list-group-item list-group-item-action py-2 px-3 border-0 border-bottom';
                                a.innerHTML = `
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold text-dark text-truncate">${item.title}</span>
                                        <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;">${item.type_label}</span>
                                    </div>
                                    <div class="text-muted text-truncate" style="font-size: 0.8rem;">${item.snippet}</div>
                                `;
                                content.appendChild(a);
                            });

                            const seeAll = document.createElement('a');
                            seeAll.href = '{{ url('/search') }}?q=' + encodeURIComponent(val.trim());
                            seeAll.className = 'list-group-item list-group-item-action bg-light text-center fw-semibold text-primary py-2';
                            seeAll.textContent = '{{ __('Voir tous les résultats &rarr;') }}';
                            content.appendChild(seeAll);
                        }
                        container.classList.remove('d-none');
                    })
                    .catch(() => {
                        container.classList.add('d-none');
                    });
            }, 250);
        }

        // Close on outside click
        document.addEventListener('click', function(e) {
            const wrapper = document.querySelector('.lcms-search-bar-wrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                document.getElementById('lcmsLiveResults')?.classList.add('d-none');
            }
        });
    </script>
@endpush
