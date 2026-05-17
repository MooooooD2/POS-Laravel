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
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#returnsTab" data-fn="loadReturnsReport">
                <i class="fas fa-undo-alt me-1"></i>{{ __('pos.Returns Report') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#stockTab" data-fn="loadStockReport">
                <i class="fas fa-boxes me-1"></i>{{ __('pos.stock_report') }}
            </button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- Sales Report --}}
        <div class="tab-pane fade show active" id="salesTab">
            <!-- Your existing sales report HTML -->
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
                            <button class="btn btn-primary w-100 mb-2" data-fn="loadSalesReport">
                                <i class="fas fa-search me-1"></i>{{ __('pos.filter') }}
                            </button>
                            <div class="btn-group w-100">
                                <button class="btn btn-sm btn-outline-success flex-fill" data-export-type="sales" data-export-format="csv">
                                    <i class="fas fa-file-csv me-1"></i>{{ __('pos.export_csv') }}
                                </button>
                                <button class="btn btn-sm btn-outline-danger flex-fill" data-export-type="sales" data-export-format="pdf">
                                    <i class="fas fa-file-pdf me-1"></i>{{ __('pos.export_pdf') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="row g-3 mb-4" id="salesStats" style="display:none">
                <div class="col-md-3">
                    <div class="stat-card blue">
                        <p class="mb-1 opacity-75 small">{{ __('pos.total') }}</p>
                        <h4 class="mb-0" id="statTotal">-</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card green">
                        <p class="mb-1 opacity-75 small">{{ __('pos.invoice_number') }}</p>
                        <h4 class="mb-0" id="statCount">-</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card orange">
                        <p class="mb-1 opacity-75 small">{{ __('pos.cash') }}</p>
                        <h4 class="mb-0" id="statCash">-</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card purple">
                        <p class="mb-1 opacity-75 small">{{ __('pos.card') }}</p>
                        <h4 class="mb-0" id="statCard">-</h4>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Invoices table -->
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
                                            <th>{{ __('pos.final') }}</th>
                                            <th>{{ __('pos.payment_method') }}</th>
                                            <th>{{ __('pos.date') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="salesInvoicesBody">
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">{{ __('pos.filter') }}
                                                to load</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top products -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">{{ __('pos.top_products') }}</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 table-sm">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>{{ __('pos.product_name') }}</th>
                                            <th>{{ __('pos.qty') }}</th>
                                            <th>{{ __('pos.total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="salesTopBody">
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Returns Report Tab --}}
        <div class="tab-pane fade" id="returnsTab">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('pos.start_date') }}</label>
                            <input type="date" class="form-control" id="returnsStart" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('pos.end_date') }}</label>
                            <input type="date" class="form-control" id="returnsEnd" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('pos.status') }} </label>
                            <select class="form-select" id="returnsStatus">
                                <option value="">{{ __('pos.filter') }} - All</option>
                                <option value="completed">{{ __('pos.completed') }}</option>
                                <option value="cancelled">{{ __('pos.cancelled') }} </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100 mb-2" data-fn="loadReturnsReport">
                                <i class="fas fa-search me-1"></i>{{ __('pos.filter') }}
                            </button>
                            <div class="btn-group w-100">
                                <button class="btn btn-sm btn-outline-success flex-fill" data-export-type="returns" data-export-format="csv">
                                    <i class="fas fa-file-csv me-1"></i>{{ __('pos.export_csv') }}
                                </button>
                                <button class="btn btn-sm btn-outline-danger flex-fill" data-export-type="returns" data-export-format="pdf">
                                    <i class="fas fa-file-pdf me-1"></i>{{ __('pos.export_pdf') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Returns Summary Stats --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card blue">
                        <p class="mb-1 opacity-75 small">{{ __('pos.total_returned_value') }}</p>
                        <h4 class="mb-0" id="returnsTotal">-</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card orange">
                        <p class="mb-1 opacity-75 small">{{ __('pos.total_returns_count') }}</p>
                        <h4 class="mb-0" id="returnsCount">-</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card purple">
                        <p class="mb-1 opacity-75 small">{{ __('pos.avg_return_value') }}</p>
                        <h4 class="mb-0" id="returnsAvg">-</h4>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                {{-- Returns table --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">{{ __('pos.returns_list') }}</div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                                <table class="table table-hover mb-0 table-sm">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th>{{ __('pos.return_number') }}</th>
                                            <th>{{ __('pos.invoice_number') }}</th>
                                            <th>{{ __('pos.Customer') }}</th>
                                            <th>{{ __('pos.total_amount') }}</th>
                                            <th>{{ __('pos.reason') }}</th>
                                            <th>{{ __('pos.status') }}</th>
                                            <th>{{ __('pos.return_date') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="returnsBody">
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                {{ __('pos.select_date_range') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Top returned products --}}
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">{{ __('pos.top_returned_products') }}</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 table-sm">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>{{ __('pos.product_name') }}</th>
                                            <th>{{ __('pos.quantity') }}</th>
                                            <th>{{ __('pos.total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="returnsTopBody">
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">-</td>
                                        </tr>
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
                    <div class="stat-card blue">
                        <p class="mb-1 opacity-75 small">{{ __('pos.total_stock_value') }}</p>
                        <h4 class="mb-0" id="stockTotalVal">-</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card orange">
                        <p class="mb-1 opacity-75 small">{{ __('pos.low_stock') }}</p>
                        <h4 class="mb-0" id="stockLowCount">-</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card red">
                        <p class="mb-1 opacity-75 small">{{ __('pos.out_of_stock') }}</p>
                        <h4 class="mb-0" id="stockOutCount">-</h4>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('pos.stock_report') }}</span>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control form-control-sm" style="width:180px" id="stockSearch"
                            placeholder="{{ __('pos.search') }}..." data-on-input="filterStock">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-success" data-export-type="stock" data-export-format="csv">
                                <i class="fas fa-file-csv me-1"></i>{{ __('pos.export_csv') }}
                            </button>
                            <button class="btn btn-sm btn-outline-danger" data-export-type="stock" data-export-format="pdf">
                                <i class="fas fa-file-pdf me-1"></i>{{ __('pos.export_pdf') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>{{ __('pos.product_name') }}</th>
                                    <th>{{ __('pos.category') }}</th>
                                    <th>{{ __('pos.unit') }}</th>
                                    <th>{{ __('pos.current_stock') }}</th>
                                    <th>{{ __('pos.cost_price') }}</th>
                                    <th>{{ __('pos.selling_price') }}</th>
                                    <th>{{ __('pos.stock_value') }}</th>
                                    <th>{{ __('pos.status') }}</th>
                                </tr>
                            </thead>
                            <tbody id="stockBody">
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script @nonce>
        const EXPORT_URLS = {
            sales:   '{{ route('reports.export.sales') }}',
            returns: '{{ route('reports.export.returns') }}',
            stock:   '{{ route('reports.export.stock') }}',
        };

        let stockData = [];
        let salesInvoiceMap = {};
        let returnsMap = {};

        async function loadSalesReport() {
            const start = document.getElementById('salesStart').value;
            const end = document.getElementById('salesEnd').value;
            const payment = document.getElementById('salesPayment').value;

            const res = await apiCall('{{ route('reports.sales') }}', 'POST', {
                start_date: start,
                end_date: end,
                payment_method: payment || undefined
            });

            // Show stats
            document.getElementById('salesStats').style.removeProperty('display');
            document.getElementById('statTotal').textContent = formatCurrency(res.totals?.total_revenue);
            document.getElementById('statCount').textContent = res.totals?.total_count ?? 0;
            document.getElementById('statCash').textContent = formatCurrency(res.byPayment?.cash?.total || 0);
            document.getElementById('statCard').textContent = formatCurrency(res.byPayment?.card?.total || 0);

            // Invoices
            const invoiceList = res.invoices?.data || [];
            salesInvoiceMap = {};
            invoiceList.forEach(inv => salesInvoiceMap[inv.id] = inv);
            document.getElementById('salesInvoicesBody').innerHTML = invoiceList.length ?
                invoiceList.map(inv => `
            <tr>
                <td><span class="badge bg-primary">${inv.invoice_number}</span></td>
                <td>${formatCurrency(inv.total)}</td>
                <td class="text-danger">${inv.discount > 0 ? '-'+formatCurrency(inv.discount) : '-'}</td>
                <td class="fw-semibold">${formatCurrency(inv.final_total)}</td>
                <td><span class="badge bg-secondary">${inv.payment_method}</span></td>
                <td class="text-muted small">${formatDate(inv.created_at)}</td>
                <td><button class="btn btn-xs btn-outline-secondary py-0 px-1" data-print-type="invoice" data-print-id="${inv.id}" title="{{ __('pos.print') }}"><i class="fas fa-print"></i></button></td>
            </tr>`).join('') :
                '<tr><td colspan="7" class="text-center text-muted py-3">{{ __('pos.no_data') }}</td></tr>';

            // Top products
            document.getElementById('salesTopBody').innerHTML = (res.topProducts || []).length ?
                res.topProducts.map((p, i) => `
            <tr>
                <td><span class="badge bg-secondary me-1">${i+1}</span>${p.product_name}</td>
                <td>${p.total_qty}</td>
                <td>${formatCurrency(p.total_sales)}</td>
            </tr>`).join('') :
                '<tr><td colspan="3" class="text-center text-muted py-3">-</td></tr>';
        }

        async function loadReturnsReport() {
            const start = document.getElementById('returnsStart').value;
            const end = document.getElementById('returnsEnd').value;
            const status = document.getElementById('returnsStatus').value;

            const res = await apiCall('{{ route('reports.returns') }}', 'POST', {
                start_date: start,
                end_date: end,
                status: status || undefined
            });

            // Update stats
            const retTotal = res.totals?.total_returned ?? 0;
            const retCount = res.totals?.total_count ?? 0;
            document.getElementById('returnsTotal').textContent = formatCurrency(retTotal);
            document.getElementById('returnsCount').textContent = retCount;
            document.getElementById('returnsAvg').textContent = formatCurrency(retCount > 0 ? retTotal / retCount : 0);

            // Returns table
            const returnList = res.returns?.data || [];
            returnsMap = {};
            returnList.forEach(ret => returnsMap[ret.id] = ret);
            document.getElementById('returnsBody').innerHTML = returnList.length ?
                returnList.map(ret => `
            <tr>
                <td><span class="badge bg-danger">${ret.return_number}</span></td>
                <td>${ret.invoice_number || '-'}</td>
                <td>${ret.customer_name || 'Walk-in'}</td>
                <td>${formatCurrency(ret.total_amount)}</td>
                <td>${ret.reason || '-'}</td>
                <td><span class="badge ${ret.status === 'completed' ? 'bg-success' : 'bg-secondary'}">${ret.status}</span></td>
                <td class="text-muted small">${formatDate(ret.return_date)}</td>
                <td><button class="btn btn-xs btn-outline-secondary py-0 px-1" data-print-type="return" data-print-id="${ret.id}" title="{{ __('pos.print') }}"><i class="fas fa-print"></i></button></td>
            </tr>`).join('') :
                '<tr><td colspan="8" class="text-center text-muted py-3">{{ __('pos.no_data') }}</td></tr>';


            // Top returned products
            document.getElementById('returnsTopBody').innerHTML = (res.topReturnedProducts || []).length ?
                res.topReturnedProducts.map((p, i) => `
            <tr>
                <td><span class="badge bg-secondary me-1">${i+1}</span>${p.product_name}</td>
                <td class="text-danger">${p.total_qty}</td>
                <td class="text-danger">${formatCurrency(p.total_amount)}</td>
            </tr>`).join('') :
                '<tr><td colspan="3" class="text-center text-muted py-3">-</td></tr>';
        }

        async function loadStockReport() {
            const res = await apiCall('{{ route('reports.stock') }}');
            stockData = res.products || [];

            document.getElementById('stockTotalVal').textContent = formatCurrency(res.total_stock_value);
            document.getElementById('stockLowCount').textContent = res.low_stock_count;
            document.getElementById('stockOutCount').textContent = res.out_of_stock;

            renderStock(stockData);
        }

        function filterStock() {
            const q = document.getElementById('stockSearch').value.toLowerCase();
            renderStock(stockData.filter(p => p.name.toLowerCase().includes(q) || (p.category || '').toLowerCase().includes(
                q)));
        }

        function escapeHtml(str) {
            if (str == null) return '';
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
        }

        function renderStock(products) {
            document.getElementById('stockBody').innerHTML = products.length ?
                products.map(p => `
            <tr>
                <td class="fw-semibold">${p.name}</td>
                <td>${p.category || '-'}</td>
                <td><span class="badge bg-info text-dark">${p.unit_abbreviation || p.unit_name || '-'}</span></td>
                <td class="fw-bold ${p.quantity === 0 ? 'text-danger' : p.low_stock ? 'text-warning' : 'text-success'}">${p.quantity}</td>
                <td>${formatCurrency(p.cost_price)}</td>
                <td>${formatCurrency(p.price)}</td>
                <td>${formatCurrency(p.stock_value)}</td>
                <td>${p.quantity === 0
                    ? '<span class="badge bg-danger">{{ __('pos.out_of_stock') }}</span>'
                    : p.low_stock
                    ? '<span class="badge badge-low-stock">{{ __('pos.low_stock') }}</span>'
                    : '<span class="badge badge-in-stock">OK</span>'}</td>
            </tr>`).join('') :
                '<tr><td colspan="8" class="text-center text-muted py-4">{{ __('pos.no_data') }}</td></tr>';
        }
        document.addEventListener('click', function (e) {
            const exportBtn = e.target.closest('[data-export-type]');
            if (exportBtn) {
                const type   = exportBtn.dataset.exportType;
                const format = exportBtn.dataset.exportFormat;
                const params = new URLSearchParams({ format });
                if (type === 'sales') {
                    params.set('start_date', document.getElementById('salesStart').value);
                    params.set('end_date',   document.getElementById('salesEnd').value);
                    const pay = document.getElementById('salesPayment').value;
                    if (pay) params.set('payment_method', pay);
                } else if (type === 'returns') {
                    params.set('start_date', document.getElementById('returnsStart').value);
                    params.set('end_date',   document.getElementById('returnsEnd').value);
                    const st = document.getElementById('returnsStatus').value;
                    if (st) params.set('status', st);
                }
                const url = EXPORT_URLS[type] + '?' + params;
                format === 'pdf' ? window.open(url, '_blank') : (window.location.href = url);
                return;
            }

            const printBtn = e.target.closest('[data-print-type]');
            if (printBtn) {
                const type = printBtn.dataset.printType;
                const id   = printBtn.dataset.printId;
                if (type === 'invoice') {
                    const inv = salesInvoiceMap[id];
                    if (inv) openPrintWindow(generateInvoicePrintHtml(inv));
                } else if (type === 'return') {
                    const ret = returnsMap[id];
                    if (ret) openPrintWindow(generateReturnPrintHtml(ret));
                }
            }
        });

        function openPrintWindow(html) {
            const w = window.open('', '_blank', 'width=420,height=700');
            w.document.write(html);
            w.document.close();
            w.focus();
            w.print();
            w.onafterprint = () => w.close();
        }

        function receiptStyles(dir) {
            const align = dir === 'rtl' ? 'right' : 'left';
            return `
                body{font-family:'Cairo','Segoe UI',Tahoma,sans-serif;font-size:13px;line-height:1.4;margin:0;padding:15px;background:#fff;max-width:350px;margin:0 auto}
                .box{border:1px solid #ddd;padding:12px;border-radius:5px}
                .hdr{text-align:center;margin-bottom:12px;padding-bottom:8px;border-bottom:1px dashed #aaa}
                .store{font-size:17px;font-weight:bold}
                .title{font-size:13px;font-weight:bold;margin-top:4px}
                .row{display:flex;justify-content:space-between;margin:3px 0;font-size:12px}
                table{width:100%;border-collapse:collapse;margin:10px 0}
                th{background:#f2f2f2;padding:5px 4px;font-size:11px;border-bottom:1px solid #aaa;text-align:${align}}
                td{padding:5px 4px;border-bottom:1px solid #eee;font-size:12px}
                .totals{border-top:1px solid #ccc;margin-top:4px}
                .grand{font-weight:bold;border-top:2px solid #333}
                .ftr{text-align:center;margin-top:12px;font-size:11px;color:#555;border-top:1px dashed #aaa;padding-top:8px}
                @media print{body{padding:0}.box{border:none;padding:0}}`;
        }

        function generateInvoicePrintHtml(inv) {
            const isRTL  = document.documentElement.dir === 'rtl';
            const dir    = isRTL ? 'rtl' : 'ltr';
            const rAlign = 'right';
            const lAlign = isRTL ? 'right' : 'left';
            const date   = inv.created_at ? new Date(inv.created_at).toLocaleString(isRTL ? 'ar-EG' : 'en-EG') : '';
            const items  = Array.isArray(inv.items) ? inv.items : [];

            const itemRows = items.map(it => `
                <tr>
                    <td style="text-align:${lAlign}">${escapeHtml(it.product_name)}</td>
                    <td style="text-align:center">${it.quantity}</td>
                    <td style="text-align:${rAlign}">${formatCurrency(it.price)}</td>
                    <td style="text-align:${rAlign}">${formatCurrency(it.subtotal)}</td>
                </tr>`).join('');

            const discountRow = inv.discount > 0 ? `
                <tr><td colspan="3" style="text-align:${rAlign};color:#d9534f">${isRTL?'الخصم':'Discount'}</td>
                <td style="text-align:${rAlign};color:#d9534f">-${formatCurrency(inv.discount)}</td></tr>` : '';

            const taxRow = inv.tax_amount > 0 ? `
                <tr><td colspan="3" style="text-align:${rAlign}">${isRTL?'الضريبة':'Tax'}</td>
                <td style="text-align:${rAlign}">${formatCurrency(inv.tax_amount)}</td></tr>` : '';

            const cashRows = inv.payment_method === 'cash' && inv.cash_received != null ? `
                <tr><td colspan="3" style="text-align:${rAlign};font-weight:bold">${isRTL?'المدفوع':'Paid'}</td>
                <td style="text-align:${rAlign};color:#198754;font-weight:bold">${formatCurrency(inv.cash_received)}</td></tr>
                <tr style="background:#fff3cd"><td colspan="3" style="text-align:${rAlign};font-weight:bold">${isRTL?'الباقي':'Change'}</td>
                <td style="text-align:${rAlign};font-weight:bold;color:#856404">${formatCurrency(inv.change_amount ?? 0)}</td></tr>` : '';

            return `<!DOCTYPE html><html dir="${dir}"><head><meta charset="utf-8">
                <title>${inv.invoice_number}</title><style>${receiptStyles(dir)}</style></head><body>
                <div class="box">
                    <div class="hdr">
                        <div class="store">{{ __('pos.sales_report') }}</div>
                        <div class="title">${isRTL?'رقم الفاتورة':'Invoice No'}: ${escapeHtml(inv.invoice_number)}</div>
                    </div>
                    <div class="row"><span>${isRTL?'التاريخ':'Date'}:</span><span>${escapeHtml(date)}</span></div>
                    <div class="row"><span>${isRTL?'أمين الصندوق':'Cashier'}:</span><span>${escapeHtml(inv.cashier_name||'-')}</span></div>
                    <table>
                        <thead><tr>
                            <th>${isRTL?'المنتج':'Product'}</th>
                            <th style="text-align:center">${isRTL?'الكمية':'Qty'}</th>
                            <th style="text-align:${rAlign}">${isRTL?'السعر':'Price'}</th>
                            <th style="text-align:${rAlign}">${isRTL?'الإجمالي':'Total'}</th>
                        </tr></thead>
                        <tbody>${itemRows}</tbody>
                    </table>
                    <table class="totals">
                        <tr><td colspan="3" style="text-align:${rAlign}">${isRTL?'المجموع الفرعي':'Subtotal'}</td>
                            <td style="text-align:${rAlign}">${formatCurrency(inv.total)}</td></tr>
                        ${discountRow}${taxRow}
                        <tr class="grand"><td colspan="3" style="text-align:${rAlign}">${isRTL?'الإجمالي النهائي':'Grand Total'}</td>
                            <td style="text-align:${rAlign}">${formatCurrency(inv.final_total)}</td></tr>
                        ${cashRows}
                    </table>
                    <div class="row"><span>${isRTL?'طريقة الدفع':'Payment'}:</span><span>${escapeHtml(inv.payment_method||'-')}</span></div>
                    <div class="ftr"><div style="font-weight:bold">${isRTL?'شكراً لتسوقكم معنا':'Thank you for shopping with us'}</div></div>
                </div></body></html>`;
        }

        function generateReturnPrintHtml(ret) {
            const isRTL  = document.documentElement.dir === 'rtl';
            const dir    = isRTL ? 'rtl' : 'ltr';
            const rAlign = 'right';
            const lAlign = isRTL ? 'right' : 'left';
            const date   = ret.return_date ? new Date(ret.return_date).toLocaleDateString(isRTL ? 'ar-EG' : 'en-EG') : '';
            const items  = Array.isArray(ret.items) ? ret.items : [];

            const itemRows = items.map(it => `
                <tr>
                    <td style="text-align:${lAlign}">${escapeHtml(it.product_name)}</td>
                    <td style="text-align:center">${it.quantity}</td>
                    <td style="text-align:${rAlign}">${formatCurrency(it.price)}</td>
                    <td style="text-align:${rAlign}">${formatCurrency(it.subtotal)}</td>
                </tr>`).join('');

            return `<!DOCTYPE html><html dir="${dir}"><head><meta charset="utf-8">
                <title>${ret.return_number}</title><style>${receiptStyles(dir)}</style></head><body>
                <div class="box">
                    <div class="hdr">
                        <div class="store">{{ __('pos.Returns Report') }}</div>
                        <div class="title">${isRTL?'رقم المرتجع':'Return No'}: ${escapeHtml(ret.return_number)}</div>
                    </div>
                    <div class="row"><span>${isRTL?'الفاتورة الأصلية':'Original Invoice'}:</span><span>${escapeHtml(ret.invoice_number||'-')}</span></div>
                    <div class="row"><span>${isRTL?'العميل':'Customer'}:</span><span>${escapeHtml(ret.customer_name||'Walk-in')}</span></div>
                    <div class="row"><span>${isRTL?'التاريخ':'Date'}:</span><span>${escapeHtml(date)}</span></div>
                    ${ret.reason ? `<div class="row"><span>${isRTL?'السبب':'Reason'}:</span><span>${escapeHtml(ret.reason)}</span></div>` : ''}
                    <table>
                        <thead><tr>
                            <th>${isRTL?'المنتج':'Product'}</th>
                            <th style="text-align:center">${isRTL?'الكمية':'Qty'}</th>
                            <th style="text-align:${rAlign}">${isRTL?'السعر':'Price'}</th>
                            <th style="text-align:${rAlign}">${isRTL?'الإجمالي':'Total'}</th>
                        </tr></thead>
                        <tbody>${itemRows || `<tr><td colspan="4" style="text-align:center;color:#999">${isRTL?'لا توجد تفاصيل':'No items'}</td></tr>`}</tbody>
                    </table>
                    <table class="totals">
                        <tr class="grand"><td colspan="3" style="text-align:${rAlign}">${isRTL?'إجمالي المرتجع':'Return Total'}</td>
                            <td style="text-align:${rAlign}">${formatCurrency(ret.total_amount)}</td></tr>
                    </table>
                    ${ret.refund_method ? `<div class="row"><span>${isRTL?'طريقة الاسترداد':'Refund Method'}:</span><span>${escapeHtml(ret.refund_method)}</span></div>` : ''}
                    <div class="row"><span>${isRTL?'الحالة':'Status'}:</span><span>${escapeHtml(ret.status)}</span></div>
                    <div class="ftr"><div style="font-weight:bold">${isRTL?'تم استلام المرتجع بنجاح':'Return processed successfully'}</div></div>
                </div></body></html>`;
        }

        loadSalesReport();
    </script>
@endpush
