{{-- FILE: resources/views/supplier-accounts/index.blade.php --}}
@extends('layouts.app')
@section('title', __('pos.supplier_accounts'))
@section('page-title', __('pos.supplier_accounts'))

@section('content')
<div class="row g-3">
    {{-- Suppliers List --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>{{ __('pos.suppliers') }}
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="suppliersList">
                    <div class="text-center py-4"><div class="spinner-border spinner-border-sm"></div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Account Details --}}
    <div class="col-md-8">
        <div id="accountPanel" style="display:none">
            {{-- Summary Cards --}}
            <div class="row g-3 mb-3">
                <div class="col-4">
                    <div class="stat-card red text-center">
                        <p class="mb-1 small opacity-75">Total Owed</p>
                        <h5 class="mb-0 fw-bold" id="acctTotalDebt">-</h5>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-card green text-center">
                        <p class="mb-1 small opacity-75">Total Paid</p>
                        <h5 class="mb-0 fw-bold" id="acctTotalPaid">-</h5>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-card orange text-center">
                        <p class="mb-1 small opacity-75">Balance</p>
                        <h5 class="mb-0 fw-bold" id="acctBalance">-</h5>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="acctSupplierName">Account Ledger</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>{{ __('pos.date') }}</th>
                                    <th>Type</th>
                                    <th>Reference</th>
                                    <th>{{ __('pos.debit') }} (Owed)</th>
                                    <th>{{ __('pos.credit') }} (Paid)</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody id="acctBody">
                                <tr><td colspan="6" class="text-center text-muted py-3">Select a supplier</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="noSupplierPanel" class="text-center text-muted py-5">
            <i class="fas fa-balance-scale fa-4x mb-3 d-block opacity-25"></i>
            <p> @lang('pos.Select a supplier to view their account')</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
async function loadSuppliersList() {
    const res       = await apiCall('{{ route("suppliers.all") }}');
    const suppliers = res.suppliers || [];

    document.getElementById('suppliersList').innerHTML = suppliers.length
        ? suppliers.map(s => `
            <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                onclick="loadSupplierAccount(${s.id}, '${s.name.replace(/'/g,"\\'")}')">
                <div>
                    <div class="fw-semibold">${s.name}</div>
                    <small class="text-muted">${s.phone || ''}</small>
                </div>
                <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-muted"></i>
            </button>`).join('')
        : '<div class="p-3 text-muted text-center">{{ __("pos.no_data") }}</div>';
}

async function loadSupplierAccount(id, name) {
    // Highlight active
    document.querySelectorAll('#suppliersList button').forEach(b => b.classList.remove('active'));
    event.currentTarget.classList.add('active');

    document.getElementById('noSupplierPanel').style.display = 'none';
    document.getElementById('accountPanel').style.display    = 'block';
    document.getElementById('acctSupplierName').textContent  = name + ' - Account Ledger';
    document.getElementById('acctBody').innerHTML = '<tr><td colspan="6" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></td></tr>';

    const res = await apiCall(`{{ url('/api/supplier-accounts') }}/${id}`);

    document.getElementById('acctTotalDebt').textContent = formatCurrency(res.total_debt);
    document.getElementById('acctTotalPaid').textContent = formatCurrency(res.total_payment);
    document.getElementById('acctBalance').textContent   = formatCurrency(res.balance);

    const typeLabels = {
        'purchase_order': 'Purchase Order',
        'payment':        '{{ __("pos.add_payment") }}',
        'adjustment':     'Adjustment',
    };

    document.getElementById('acctBody').innerHTML = (res.entries || []).length
        ? res.entries.map(e => `
            <tr>
                <td class="small">${formatDate(e.created_at)}</td>
                <td><span class="badge bg-secondary">${typeLabels[e.transaction_type] || e.transaction_type}</span></td>
                <td><code>${e.reference_number || '-'}</code></td>
                <td class="text-danger fw-semibold">${e.debit > 0 ? formatCurrency(e.debit) : '-'}</td>
                <td class="text-success fw-semibold">${e.credit > 0 ? formatCurrency(e.credit) : '-'}</td>
                <td class="fw-bold ${e.balance > 0 ? 'text-warning' : 'text-success'}">${formatCurrency(e.balance)}</td>
            </tr>`).join('')
        : '<tr><td colspan="6" class="text-center text-muted py-3">{{ __("pos.no_data") }}</td></tr>';
}

loadSuppliersList();
</script>
@endpush
