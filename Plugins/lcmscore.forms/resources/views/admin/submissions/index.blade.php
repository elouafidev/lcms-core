@extends('core::admin.layouts.app')

@section('title', __('Soumissions : ') . $form->name . ' — Administration')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="/admin/forms" class="btn btn-sm btn-outline-secondary rounded-circle" title="{{ __('Retour') }}">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0">
                    {{ __('Soumissions : ') }} <span class="text-primary">{{ $form->name }}</span>
                </h1>
            </div>
            <p class="text-muted small mb-0 mt-1">
                {{ __('Consultez les messages reçus et exportez vos données en tableur') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/admin/forms/{{ $form->id }}/submissions/export" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-file-earmark-spreadsheet"></i> {{ __('Exporter en CSV') }}
            </a>
            <a href="/admin/forms/{{ $form->id }}/edit" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-pencil me-1"></i> {{ __('Modifier le formulaire') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Pills -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="btn-group btn-group-sm" role="group">
                <a href="/admin/forms/{{ $form->id }}/submissions?filter=all"
                   class="btn btn-outline-secondary {{ ($filter ?? 'all') === 'all' ? 'active' : '' }}">
                    {{ __('Toutes les soumissions') }}
                </a>
                <a href="/admin/forms/{{ $form->id }}/submissions?filter=unread"
                   class="btn btn-outline-secondary {{ ($filter ?? '') === 'unread' ? 'active' : '' }}">
                    <i class="bi bi-envelope-fill text-warning me-1"></i> {{ __('Non lues') }}
                </a>
                <a href="/admin/forms/{{ $form->id }}/submissions?filter=read"
                   class="btn btn-outline-secondary {{ ($filter ?? '') === 'read' ? 'active' : '' }}">
                    <i class="bi bi-envelope-open text-muted me-1"></i> {{ __('Lues') }}
                </a>
            </div>

            <div class="small text-muted">
                {{ $submissions->total() }} {{ __('message(s) au total') }}
            </div>
        </div>
    </div>

    <!-- Submissions Table -->
    <div class="card card-outline card-secondary shadow-sm rounded-4 border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light fs-7 text-uppercase text-muted">
                        <tr>
                            <th style="width: 15%;">{{ __('Date') }}</th>
                            <th style="width: 45%;">{{ __('Extrait des données') }}</th>
                            <th style="width: 15%;">{{ __('Adresse IP') }}</th>
                            <th style="width: 10%;">{{ __('Statut') }}</th>
                            <th style="width: 15%;" class="text-end pe-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($submissions as $sub)
                            <tr class="{{ ! $sub->is_read ? 'table-warning bg-opacity-25 fw-semibold' : '' }}">
                                <td>
                                    <div class="small">
                                        {{ $sub->created_at ? $sub->created_at->format('d/m/Y H:i') : '-' }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ $sub->created_at ? $sub->created_at->diffForHumans() : '' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 450px;">
                                        @foreach($sub->data as $key => $val)
                                            <span class="badge bg-light text-dark border me-1">
                                                <strong>{{ $key }}:</strong> {{ is_array($val) ? implode(', ', $val) : Str::limit((string) $val, 30) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <code class="small text-secondary">{{ $sub->ip_address ?? 'N/A' }}</code>
                                </td>
                                <td>
                                    @if(! $sub->is_read)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                            <i class="bi bi-envelope-fill me-1"></i> {{ __('Non lu') }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                            <i class="bi bi-check me-1"></i> {{ __('Lu') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-view-submission"
                                                data-id="{{ $sub->id }}"
                                                title="{{ __('Consulter la soumission complète') }}">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <form action="/admin/forms/submissions/{{ $sub->id }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('{{ __('Supprimer définitivement ce message ?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="{{ __('Supprimer') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                    <h5>{{ __('Aucune soumission reçue') }}</h5>
                                    <p class="small mb-0">{{ __('Les messages soumis par les visiteurs apparaîtront ici.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $submissions->links() }}
    </div>

    <!-- View Submission Modal -->
    <div class="modal fade" id="submissionDetailModal" tabindex="-1" aria-labelledby="submissionDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold" id="submissionDetailModalLabel">
                        <i class="bi bi-envelope-paper text-primary me-2"></i> {{ __('Détail de la soumission') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-2 mb-3 pb-3 border-bottom text-muted small">
                        <div class="col-sm-6">
                            <strong>{{ __('Reçu le :') }}</strong> <span id="modalSubDate"></span>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <strong>{{ __('Adresse IP :') }}</strong> <span id="modalSubIp"></span>
                        </div>
                    </div>

                    <div id="modalSubFieldsContainer" class="list-group list-group-flush">
                        <!-- Populated via JavaScript -->
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">
                        {{ __('Fermer') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const detailModalEl = document.getElementById('submissionDetailModal');
    let detailModal = null;
    if (detailModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        detailModal = new bootstrap.Modal(detailModalEl);
    }

    document.querySelectorAll('.btn-view-submission').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const row = this.closest('tr');

            fetch(`/admin/forms/submissions/${id}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(res => {
                if (!res || !res.data) return;
                const sub = res.data;

                document.getElementById('modalSubDate').textContent = new Date(sub.created_at).toLocaleString();
                document.getElementById('modalSubIp').textContent = sub.ip_address || 'N/A';

                const container = document.getElementById('modalSubFieldsContainer');
                container.innerHTML = '';

                for (const [key, val] of Object.entries(sub.data)) {
                    const item = document.createElement('div');
                    item.className = 'list-group-item px-0 py-2';
                    const displayVal = Array.isArray(val) ? val.join(', ') : val;
                    item.innerHTML = `
                        <div class="small text-uppercase fw-bold text-secondary mb-1">${key}</div>
                        <div class="text-dark bg-light p-2 rounded border">${displayVal || '<span class="text-muted fst-italic">Vide</span>'}</div>
                    `;
                    container.appendChild(item);
                }

                // Remove table-warning highlight from row
                if (row) {
                    row.classList.remove('table-warning', 'bg-opacity-25', 'fw-semibold');
                    const badge = row.querySelector('.badge.bg-warning-subtle');
                    if (badge) {
                        badge.className = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1';
                        badge.innerHTML = '<i class="bi bi-check me-1"></i> Lu';
                    }
                }

                if (detailModal) detailModal.show();
            })
            .catch(err => console.error(err));
        });
    });
});
</script>
@endpush
