{{-- FILE: resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')
@section('title', __('pos.dashboard'))
@section('page-title', __('pos.dashboard'))

@section('content')
{{-- Stats Row --}}
<div class="row g-3 mb-4" id="statsRow">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card blue">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 opacity-75 small">{{ __('pos.today_sales') }}</p>
                    <h3 class="mb-0 fw-bold" id="todaySalesTotal">-</h3>
                    <small id="todaySalesCount" class="opacity-75"></small>
                </div>
                <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
            </div>
            <div class="mt-2">
                <small id="growthBadge"></small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card green">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 opacity-75 small">{{ __('pos.total_revenue') }}</p>
                    <h3 class="mb-0 fw-bold" id="totalRevenue">-</h3>
                </div>
                <i class="fas fa-coins fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card orange">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 opacity-75 small">{{ __('pos.low_stock') }}</p>
                    <h3 class="mb-0 fw-bold" id="lowStockCount">-</h3>
                    <small class="opacity-75">{{ __('pos.out_of_stock') }}: <span id="outOfStockCount">-</span></small>
                </div>
                <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card purple">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 opacity-75 small">{{ __('pos.total_products') }}</p>
                    <h3 class="mb-0 fw-bold" id="totalProducts">-</h3>
                    <small class="opacity-75">{{ __('pos.total_suppliers') }}: <span id="totalSuppliers">-</span></small>
                </div>
                <i class="fas fa-boxes fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

{{-- Tables Row --}}
<div class="row g-3">
    {{-- Recent Invoices --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-receipt me-2 text-primary"></i>{{ __('pos.recent_invoices') }}
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('pos.invoice_number') }}</th>
                                <th>{{ __('pos.total') }}</th>
                                <th>{{ __('pos.payment_method') }}</th>
                                <th>{{ __('pos.date') }}</th>
                            </tr>
                        </thead>
                        <tbody id="recentInvoicesBody">
                            <tr><td colspan="4" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Products --}}
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-star me-2 text-warning"></i>{{ __('pos.top_products') }}
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('pos.product_name') }}</th>
                                <th>{{ __('pos.quantity') }}</th>
                                <th>{{ __('pos.total') }}</th>
                            </tr>
                        </thead>
                        <tbody id="topProductsBody">
                            <tr><td colspan="3" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></td></tr>
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
    async function loadDashboard() {
        try {
            const data = await apiCall('{{ route("dashboard.data") }}');

            // Stats
            document.getElementById('todaySalesTotal').textContent  = formatCurrency(data.today_sales_total);
            document.getElementById('todaySalesCount').textContent  = data.today_sales_count + ' {{ __("pos.invoice_number") }}';
            document.getElementById('totalRevenue').textContent     = formatCurrency(data.total_revenue);
            document.getElementById('lowStockCount').textContent    = data.low_stock_count;
            document.getElementById('outOfStockCount').textContent  = data.out_of_stock_count;
            document.getElementById('totalProducts').textContent    = data.total_products;
            document.getElementById('totalSuppliers').textContent   = data.total_suppliers;

            // Growth badge
            const g = data.growth_percentage;
            document.getElementById('growthBadge').innerHTML = `
                <i class="fas fa-arrow-${g >= 0 ? 'up' : 'down'}"></i>
                ${Math.abs(g)}% {{ __('pos.growth_vs_yesterday') }}`;

            // Recent invoices
            document.getElementById('recentInvoicesBody').innerHTML = data.recent_invoices.length
                ? data.recent_invoices.map(inv => `
                    <tr>
                        <td><span class="badge bg-primary-subtle text-primary">${inv.invoice_number}</span></td>
                        <td class="fw-semibold">${formatCurrency(inv.final_total)}</td>
                        <td>${inv.payment_method}</td>
                        <td class="text-muted small">${formatDate(inv.created_at)}</td>
                    </tr>`).join('')
                : '<tr><td colspan="4" class="text-center text-muted py-3">{{ __("pos.no_data") }}</td></tr>';

            // Top products
            document.getElementById('topProductsBody').innerHTML = data.top_products.length
                ? data.top_products.map((p, i) => `
                    <tr>
                        <td><span class="badge bg-secondary me-1">${i+1}</span>${p.name}</td>
                        <td>${p.total_quantity}</td>
                        <td>${formatCurrency(p.total_sales)}</td>
                    </tr>`).join('')
                : '<tr><td colspan="3" class="text-center text-muted py-3">{{ __("pos.no_data") }}</td></tr>';

        } catch(e) {
            console.error(e);
        }
    }

    loadDashboard();
    setInterval(loadDashboard, 30000); // Refresh every 30s
</script>
@endpush
