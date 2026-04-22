{{-- FILE: resources/views/supplier-payments/index.blade.php --}}
@extends('layouts.app')
@section('title', __('pos.supplier_payments'))
@section('page-title', __('pos.supplier_payments'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-money-bill-wave me-2"></i>{{ __('pos.supplier_payments') }}</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">
            <i class="fas fa-plus me-1"></i>{{ __('pos.add_payment') }}
        </button>
    </div>
    <div class="card-body">
        <div class="row mb-3 g-2">
            <div class="col-md-4">
                <select class="form-select" id="paySupplierFilter" onchange="loadPayments()">
                    <option value="">{{ __('pos.suppliers') }} - All</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>{{ __('pos.payment_number') }}</th>
                        <th>{{ __('pos.suppliers') }}</th>
                        <th>{{ __('pos.amount') }}</th>
                        <th>{{ __('pos.payment_method') }}</th>
                        <th>{{ __('pos.payment_date') }}</th>
                        <th>{{ __('pos.notes') }}</th>
                    </tr>
                </thead>
                <tbody id="paymentsBody">
                    <tr><td colspan="6" class="text-center py-4"><div class="spinner-border"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add Payment Modal --}}
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('pos.add_payment') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('pos.suppliers') }} *</label>
                    <select class="form-select" id="paySupplier" required>
                        <option value="">-- {{ __('pos.suppliers') }} --</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('pos.amount') }} *</label>
                    <input type="number" class="form-control" id="payAmount" step="0.01" min="0.01">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('pos.payment_method') }} *</label>
                    <select class="form-select" id="payMethod">
                        <option value="cash">{{ __('pos.cash') }}</option>
                        <option value="card">{{ __('pos.card') }}</option>
                        <option value="transfer">{{ __('pos.transfer') }}</option>
                        <option value="check">Check / شيك</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('pos.payment_date') }} *</label>
                    <input type="date" class="form-control" id="payDate" value="{{ date('Y-m-d') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('pos.notes') }}</label>
                    <textarea class="form-control" id="payNotes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">{{ __('pos.cancel') }}</button>
                <button class="btn btn-primary" onclick="savePayment()">{{ __('pos.save') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let suppliers = [];

async function init() {
    const res  = await apiCall('{{ route("suppliers.all") }}');
    suppliers  = res.suppliers || [];
    const opts = suppliers.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
    document.getElementById('paySupplier').innerHTML      = '<option value="">--</option>' + opts;
    document.getElementById('paySupplierFilter').innerHTML = '<option value="">All Suppliers</option>' + opts;
    loadPayments();
}

async function loadPayments() {
    const suppId = document.getElementById('paySupplierFilter').value;
    const url    = '{{ route("supplier-payments.all") }}' + (suppId ? `?supplier_id=${suppId}` : '');
    const res    = await apiCall(url);
    const pays   = res.payments?.data || [];

    document.getElementById('paymentsBody').innerHTML = pays.length
        ? pays.map(p => `
            <tr>
                <td><span class="badge bg-success">${p.payment_number}</span></td>
                <td>${p.supplier_name}</td>
                <td class="fw-bold text-success">${formatCurrency(p.amount)}</td>
                <td><span class="badge bg-secondary">${p.payment_method}</span></td>
                <td>${formatDate(p.payment_date)}</td>
                <td class="text-muted small">${p.notes || '-'}</td>
            </tr>`).join('')
        : '<tr><td colspan="6" class="text-center text-muted py-4">{{ __("pos.no_data") }}</td></tr>';
}

async function savePayment() {
    const res = await apiCall('{{ route("supplier-payments.store") }}', 'POST', {
        supplier_id:    document.getElementById('paySupplier').value,
        amount:         document.getElementById('payAmount').value,
        payment_method: document.getElementById('payMethod').value,
        payment_date:   document.getElementById('payDate').value,
        notes:          document.getElementById('payNotes').value,
    });
    if (res.success) {
        showToast('{{ __("pos.success") }}');
        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
        loadPayments();
    } else {
        showToast(res.message || '{{ __("pos.error") }}', 'danger');
    }
}

init();
</script>
@endpush
