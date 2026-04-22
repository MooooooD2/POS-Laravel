{{-- FILE: resources/views/suppliers/index.blade.php --}}
@extends('layouts.app')
@section('title', __('pos.suppliers'))
@section('page-title', __('pos.suppliers'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-truck me-2"></i>{{ __('pos.suppliers') }}</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#supplierModal">
            <i class="fas fa-plus me-1"></i>{{ __('pos.add_supplier') }}
        </button>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" id="supplierSearch"
                    placeholder="{{ __('pos.search') }}..." oninput="filterSuppliers()">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>{{ __('pos.name') }}</th>
                        <th>{{ __('pos.phone') }}</th>
                        <th>{{ __('pos.address') }}</th>
                        <th>{{ __('pos.email') }}</th>
                        <th>{{ __('pos.actions') }}</th>
                    </tr>
                </thead>
                <tbody id="suppliersBody">
                    <tr><td colspan="6" class="text-center py-4"><div class="spinner-border"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add/Edit Supplier Modal --}}
<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supplierModalTitle">{{ __('pos.add_supplier') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="supplierId">
                <div class="mb-3">
                    <label class="form-label">{{ __('pos.name') }} *</label>
                    <input type="text" class="form-control" id="supplierName" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('pos.phone') }}</label>
                    <input type="text" class="form-control" id="supplierPhone">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('pos.address') }}</label>
                    <input type="text" class="form-control" id="supplierAddress">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('pos.email') }}</label>
                    <input type="email" class="form-control" id="supplierEmail">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">{{ __('pos.cancel') }}</button>
                <button class="btn btn-primary" onclick="saveSupplier()">{{ __('pos.save') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let allSuppliers = [];

async function loadSuppliers() {
    const res = await apiCall('{{ route("suppliers.all") }}');
    allSuppliers = res.suppliers || [];
    renderSuppliers(allSuppliers);
}

function filterSuppliers() {
    const q = document.getElementById('supplierSearch').value.toLowerCase();
    renderSuppliers(allSuppliers.filter(s =>
        s.name.toLowerCase().includes(q) ||
        (s.phone || '').includes(q) ||
        (s.email || '').toLowerCase().includes(q)
    ));
}

function renderSuppliers(suppliers) {
    document.getElementById('suppliersBody').innerHTML = suppliers.length
        ? suppliers.map((s, i) => `
            <tr>
                <td>${i+1}</td>
                <td class="fw-semibold">${s.name}</td>
                <td>${s.phone || '-'}</td>
                <td>${s.address || '-'}</td>
                <td>${s.email || '-'}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('supplier-accounts') }}?supplier=${s.id}" class="btn btn-info btn-sm">
                            <i class="fas fa-balance-scale"></i>
                        </a>
                        <button class="btn btn-primary" onclick="editSupplier(${JSON.stringify(s).replace(/"/g,'&quot;')})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger" onclick="deleteSupplier(${s.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`).join('')
        : '<tr><td colspan="6" class="text-center text-muted py-4">{{ __("pos.no_data") }}</td></tr>';
}

function editSupplier(s) {
    document.getElementById('supplierId').value      = s.id;
    document.getElementById('supplierName').value    = s.name;
    document.getElementById('supplierPhone').value   = s.phone || '';
    document.getElementById('supplierAddress').value = s.address || '';
    document.getElementById('supplierEmail').value   = s.email || '';
    document.getElementById('supplierModalTitle').textContent = '{{ __("pos.edit_supplier") }}';
    new bootstrap.Modal(document.getElementById('supplierModal')).show();
}

async function saveSupplier() {
    const id   = document.getElementById('supplierId').value;
    const data = {
        name:    document.getElementById('supplierName').value,
        phone:   document.getElementById('supplierPhone').value,
        address: document.getElementById('supplierAddress').value,
        email:   document.getElementById('supplierEmail').value,
    };
    const url    = id ? `/api/suppliers/${id}` : '{{ route("suppliers.store") }}';
    const method = id ? 'PUT' : 'POST';
    const res    = await apiCall(url, method, data);
    if (res.success) {
        showToast('{{ __("pos.success") }}');
        bootstrap.Modal.getInstance(document.getElementById('supplierModal')).hide();
        document.getElementById('supplierId').value = '';
        loadSuppliers();
    } else {
        showToast(res.message || '{{ __("pos.error") }}', 'danger');
    }
}

async function deleteSupplier(id) {
    if (!confirm('{{ __("pos.confirm_delete") }}')) return;
    const res = await apiCall(`/api/suppliers/${id}`, 'DELETE');
    if (res.success) { showToast('{{ __("pos.success") }}'); loadSuppliers(); }
    else showToast(res.message, 'danger');
}

document.getElementById('supplierModal').addEventListener('show.bs.modal', function(e) {
    if (!e.relatedTarget) return;
    document.getElementById('supplierId').value = '';
    ['Name','Phone','Address','Email'].forEach(f => document.getElementById('supplier'+f).value = '');
    document.getElementById('supplierModalTitle').textContent = '{{ __("pos.add_supplier") }}';
});

loadSuppliers();
</script>
@endpush
