{{-- FILE: resources/views/reports/index.blade.php --}}
@extends('layouts.app')
@section('title', __('pos.reports'))
@section('page-title', __('pos.reports'))

@section('content')
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#salesTab">
            <i class="fas fa-chart-line me-1"></i>{{ __('pos.sales_report') }}
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#stockTab" onclick="loadStockReport()">
            <i class="fas fa-boxes me-1"></i>{{ __('pos.stock_report') }}
        </button>
    </li>
</ul>

<div class="tab-content">
    {{-- Sales Report --}}
    <div class="tab-pane fade show active" id="salesTab">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('pos.start_date') }}</label>
                        <input type="date" class="form-control" id="salesStart" value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('pos.end_date') }}</label>
                        <input type="date" class="form-control" id="salesEnd" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('pos.payment_method') }}</label>
                        <select class="form-select" id="salesPayment">
                            <option value="">{{ __('pos.filter') }} - All</option>
                            <option value="cash">{{ __('pos.cash') }}</option>
                            <option value="card">{{ __('pos.card') }}</option>
                            <option value="transfer">{{ __('pos.transfer') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" onclick="loadSalesReport()">
                            <i class="fas fa-search me-1"></i>{{ __('pos.filter') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="row g-3 mb-4" id="salesStats" style="display:none!important">
            <div class="col-md-3">
                <div class="stat-card blue"><p class="mb-1 opacity-75 small">{{ __('pos.total') }}</p>
                    <h4 class="mb-0" id="statTotal">-</h4></div>
            </div>
            <div class="col-md-3">
                <div class="stat-card green"><p class="mb-1 opacity-75 small">{{ __('pos.invoice_number') }}</p>
                    <h4 class="mb-0" id="statCount">-</h4></div>
            </div>
            <div class="col-md-3">
                <div class="stat-card orange"><p class="mb-1 opacity-75 small">{{ __('pos.cash') }}</p>
                    <h4 class="mb-0" id="statCash">-</h4></div>
            </div>
            <div class="col-md-3">
                <div class="stat-card purple"><p class="mb-1 opacity-75 small">{{ __('pos.card') }}</p>
                    <h4 class="mb-0" id="statCard">-</h4></div>
            </div>
        </div>

        <div class="row g-3">
            {{-- Invoices table --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">{{ __('pos.recent_invoices') }}</div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                            <table class="table table-hover mb-0 table-sm">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>{{ __('pos.invoice_number') }}</th>
                                        <th>{{ __('pos.total') }}</th>
                                        <th>{{ __('pos.discount') }}</th>
                                        <th>Final</th>
                                        <th>{{ __('pos.payment_method') }}</th>
                                        <th>{{ __('pos.date') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="salesInvoicesBody">
                                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('pos.filter') }} to load</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Top products --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">{{ __('pos.top_products') }}</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 table-sm">
                                <thead class="table-dark">
                                    <tr>
                                        <th>{{ __('pos.product_name') }}</th>
                                        <th>Qty</th>
                                        <th>{{ __('pos.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="salesTopBody">
                                    <tr><td colspan="3" class="text-center text-muted py-3">-</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stock Report --}}
    <div class="tab-pane fade" id="stockTab">
        <div class="row g-3 mb-3" id="stockStats">
            <div class="col-md-4">
                <div class="stat-card blue"><p class="mb-1 opacity-75 small">{{ __('pos.total_stock_value') }}</p>
                    <h4 class="mb-0" id="stockTotalVal">-</h4></div>
            </div>
            <div class="col-md-4">
                <div class="stat-card orange"><p class="mb-1 opacity-75 small">{{ __('pos.low_stock') }}</p>
                    <h4 class="mb-0" id="stockLowCount">-</h4></div>
            </div>
            <div class="col-md-4">
                <div class="stat-card red"><p class="mb-1 opacity-75 small">{{ __('pos.out_of_stock') }}</p>
                    <h4 class="mb-0" id="stockOutCount">-</h4></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span>{{ __('pos.stock_report') }}</span>
                <input type="text" class="form-control form-control-sm" style="width:200px"
                    id="stockSearch" placeholder="{{ __('pos.search') }}..." oninput="filterStock()">
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>{{ __('pos.product_name') }}</th>
                                <th>{{ __('pos.category') }}</th>
                                <th>{{ __('pos.current_stock') }}</th>
                                <th>{{ __('pos.cost_price') }}</th>
                                <th>{{ __('pos.selling_price') }}</th>
                                <th>Stock Value</th>
                                <th>{{ __('pos.status') }}</th>
                            </tr>
                        </thead>
                        <tbody id="stockBody">
                            <tr><td colspan="7" class="text-center text-muted py-4">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let stockData = [];

async function loadSalesReport() {
    const start   = document.getElementById('salesStart').value;
    const end     = document.getElementById('salesEnd').value;
    const payment = document.getElementById('salesPayment').value;

    const res = await apiCall('{{ route("reports.sales") }}', 'POST', { start_date: start, end_date: end, payment_method: payment || undefined });

    // Show stats
    document.getElementById('salesStats').style.removeProperty('display');
    document.getElementById('statTotal').textContent = formatCurrency(res.total_revenue);
    document.getElementById('statCount').textContent = res.total_count;
    document.getElementById('statCash').textContent  = formatCurrency(res.by_payment?.cash?.total || 0);
    document.getElementById('statCard').textContent  = formatCurrency(res.by_payment?.card?.total || 0);

    // Invoices
    document.getElementById('salesInvoicesBody').innerHTML = (res.invoices || []).length
        ? res.invoices.map(inv => `
            <tr>
                <td><span class="badge bg-primary">${inv.invoice_number}</span></td>
                <td>${formatCurrency(inv.total)}</td>
                <td class="text-danger">${inv.discount > 0 ? '-'+formatCurrency(inv.discount) : '-'}</td>
                <td class="fw-semibold">${formatCurrency(inv.final_total)}</td>
                <td><span class="badge bg-secondary">${inv.payment_method}</span></td>
                <td class="text-muted small">${formatDate(inv.created_at)}</td>
            </tr>`).join('')
        : '<tr><td colspan="6" class="text-center text-muted py-3">{{ __("pos.no_data") }}</td></tr>';

    // Top products
    document.getElementById('salesTopBody').innerHTML = (res.top_products || []).length
        ? res.top_products.map((p, i) => `
            <tr>
                <td><span class="badge bg-secondary me-1">${i+1}</span>${p.product_name}</td>
                <td>${p.total_qty}</td>
                <td>${formatCurrency(p.total_sales)}</td>
            </tr>`).join('')
        : '<tr><td colspan="3" class="text-center text-muted py-3">-</td></tr>';
}

async function loadStockReport() {
    const res = await apiCall('{{ route("reports.stock") }}');
    stockData = res.products || [];

    document.getElementById('stockTotalVal').textContent = formatCurrency(res.total_stock_value);
    document.getElementById('stockLowCount').textContent = res.low_stock_count;
    document.getElementById('stockOutCount').textContent = res.out_of_stock;

    renderStock(stockData);
}

function filterStock() {
    const q = document.getElementById('stockSearch').value.toLowerCase();
    renderStock(stockData.filter(p => p.name.toLowerCase().includes(q) || (p.category || '').toLowerCase().includes(q)));
}

function renderStock(products) {
    document.getElementById('stockBody').innerHTML = products.length
        ? products.map(p => `
            <tr>
                <td class="fw-semibold">${p.name}</td>
                <td>${p.category || '-'}</td>
                <td class="fw-bold ${p.quantity === 0 ? 'text-danger' : p.low_stock ? 'text-warning' : 'text-success'}">${p.quantity}</td>
                <td>${formatCurrency(p.cost_price)}</td>
                <td>${formatCurrency(p.price)}</td>
                <td>${formatCurrency(p.stock_value)}</td>
                <td>${p.quantity === 0
                    ? '<span class="badge bg-danger">{{ __("pos.out_of_stock") }}</span>'
                    : p.low_stock
                    ? '<span class="badge badge-low-stock">{{ __("pos.low_stock") }}</span>'
                    : '<span class="badge badge-in-stock">OK</span>'}</td>
            </tr>`).join('')
        : '<tr><td colspan="7" class="text-center text-muted py-4">{{ __("pos.no_data") }}</td></tr>';
}
</script>
@endpush
