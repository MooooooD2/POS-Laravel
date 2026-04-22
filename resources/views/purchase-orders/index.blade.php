{{-- FILE: resources/views/purchase-orders/index.blade.php --}}
@extends('layouts.app')
@section('title', __('pos.purchase_orders'))
@section('page-title', __('pos.purchase_orders'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-file-invoice me-2"></i>{{ __('pos.purchase_orders') }}</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createPOModal">
            <i class="fas fa-plus me-1"></i>{{ __('pos.create_po') }}
        </button>
    </div>
    <div class="card-body">
        <div class="row mb-3 g-2">
            <div class="col-md-3">
                <select class="form-select" id="poStatusFilter" onchange="loadPOs()">
                    <option value="">{{ __('pos.status') }} - {{ __('pos.filter') }}</option>
                    <option value="pending">{{ __('pos.po_status_pending') }}</option>
                    <option value="received">{{ __('pos.po_status_received') }}</option>
                    <option value="partial">{{ __('pos.po_status_partial') }}</option>
                    <option value="cancelled">{{ __('pos.po_status_cancelled') }}</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>{{ __('pos.po_number') }}</th>
                        <th>{{ __('pos.suppliers') }}</th>
                        <th>{{ __('pos.total') }}</th>
                        <th>{{ __('pos.order_date') }}</th>
                        <th>{{ __('pos.expected_date') }}</th>
                        <th>{{ __('pos.status') }}</th>
                        <th>{{ __('pos.actions') }}</th>
                    </tr>
                </thead>
                <tbody id="poBody">
                    <tr><td colspan="7" class="text-center py-4"><div class="spinner-border"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Create PO Modal --}}
<div class="modal fade" id="createPOModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('pos.create_po') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('pos.suppliers') }} *</label>
                        <select class="form-select" id="poSupplier" required>
                            <option value="">-- {{ __('pos.suppliers') }} --</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('pos.order_date') }} *</label>
                        <input type="date" class="form-control" id="poOrderDate" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('pos.expected_date') }}</label>
                        <input type="date" class="form-control" id="poExpectedDate">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('pos.discount') }}</label>
                        <input type="number" class="form-control" id="poDiscount" value="0" min="0" step="0.01" onchange="updatePOTotals()">
                    </div>
                </div>

                {{-- PO Items --}}
                <h6 class="fw-semibold mb-2">{{ __('pos.product_name') }}</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="width:30%">{{ __('pos.product_name') }}</th>
                                <th style="width:12%">{{ __('pos.quantity') }}</th>
                                <th style="width:15%">{{ __('pos.cost_price') }}</th>
                                <th style="width:15%">{{ __('pos.selling_price') }}</th>
                                <th style="width:15%">{{ __('pos.subtotal') }}</th>
                                <th style="width:8%"></th>
                            </tr>
                        </thead>
                        <tbody id="poItemsBody"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6">
                                    <button class="btn btn-sm btn-outline-primary w-100" onclick="addPOItemRow()">
                                        <i class="fas fa-plus me-1"></i> {{ __('pos.add_product') }}
                                    </button>
                                </td>
                            </tr>
                            <tr class="table-light fw-bold">
                                <td colspan="4" class="text-end">{{ __('pos.total') }}</td>
                                <td id="poGrandTotal">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('pos.notes') }}</label>
                    <textarea class="form-control" id="poNotes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">{{ __('pos.cancel') }}</button>
                <button class="btn btn-primary" onclick="savePO()">
                    <i class="fas fa-save me-1"></i>{{ __('pos.save') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Receive PO Modal --}}
<div class="modal fade" id="receivePOModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('pos.receive_po') }}: <span id="receivePONumber"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="receivePOId">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('pos.product_name') }}</th>
                                <th>{{ __('pos.quantity') }} ({{ __('pos.po_number') }})</th>
                                <th>Received So Far</th>
                                <th>{{ __('pos.quantity') }} to Receive</th>
                                <th>{{ __('pos.cost_price') }}</th>
                                <th>{{ __('pos.selling_price') }}</th>
                            </tr>
                        </thead>
                        <tbody id="receiveItemsBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">{{ __('pos.cancel') }}</button>
                <button class="btn btn-success" onclick="submitReceivePO()">
                    <i class="fas fa-check me-1"></i>{{ __('pos.receive_po') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let poProducts = [];
let poItemCount = 0;

async function loadSuppliers() {
    const res = await apiCall('{{ route("suppliers.all") }}');
    const sel = document.getElementById('poSupplier');
    sel.innerHTML = '<option value="">-- {{ __("pos.suppliers") }} --</option>' +
        (res.suppliers || []).map(s => `<option value="${s.id}">${s.name}</option>`).join('');
}

async function loadProductsList() {
    const res  = await apiCall('{{ route("products.all") }}');
    poProducts = res.products || [];
}

async function loadPOs() {
    const status = document.getElementById('poStatusFilter').value;
    const url    = '{{ route("purchase-orders.all") }}' + (status ? `?status=${status}` : '');
    const res    = await apiCall(url);
    const orders = res.purchase_orders?.data || [];

    const statusMap = {
        pending:   { label: '{{ __("pos.po_status_pending") }}',  cls: 'warning' },
        received:  { label: '{{ __("pos.po_status_received") }}', cls: 'success' },
        partial:   { label: '{{ __("pos.po_status_partial") }}',  cls: 'info' },
        cancelled: { label: '{{ __("pos.po_status_cancelled") }}',cls: 'danger' },
    };

    document.getElementById('poBody').innerHTML = orders.length
        ? orders.map(po => {
            const st = statusMap[po.status] || { label: po.status, cls: 'secondary' };
            return `<tr>
                <td><span class="badge bg-primary">${po.po_number}</span></td>
                <td>${po.supplier_name}</td>
                <td class="fw-semibold">${formatCurrency(po.final_amount)}</td>
                <td>${formatDate(po.order_date)}</td>
                <td>${po.expected_date ? formatDate(po.expected_date) : '-'}</td>
                <td><span class="badge bg-${st.cls}">${st.label}</span></td>
                <td>
                    ${po.status !== 'received' && po.status !== 'cancelled'
                        ? `<button class="btn btn-sm btn-success" onclick="showReceivePO(${JSON.stringify(po).replace(/"/g,'&quot;')})">
                            <i class="fas fa-box-open"></i> {{ __('pos.receive_po') }}
                           </button>`
                        : ''}
                </td>
            </tr>`;
        }).join('')
        : '<tr><td colspan="7" class="text-center text-muted py-4">{{ __("pos.no_data") }}</td></tr>';
}

function addPOItemRow() {
    const idx  = poItemCount++;
    const opts = poProducts.map(p => `<option value="${p.id}" data-cost="${p.cost_price}" data-price="${p.price}">${p.name}</option>`).join('');
    const row  = document.createElement('tr');
    row.id     = `poRow${idx}`;
    row.innerHTML = `
        <td>
            <select class="form-select form-select-sm" onchange="fillPORowPrices(${idx}, this)">
                <option value="">-- {{ __('pos.product_name') }} --</option>
                ${opts}
            </select>
            <input type="hidden" id="poItemProductId${idx}">
            <input type="text" class="form-control form-control-sm mt-1 d-none" id="poItemCustomName${idx}" placeholder="{{ __('pos.product_name') }}">
        </td>
        <td><input type="number" class="form-control form-control-sm" id="poItemQty${idx}" value="1" min="1" onchange="updatePORowSubtotal(${idx})"></td>
        <td><input type="number" class="form-control form-control-sm" id="poItemCost${idx}" value="0" step="0.01" onchange="updatePORowSubtotal(${idx})"></td>
        <td><input type="number" class="form-control form-control-sm" id="poItemSelling${idx}" value="0" step="0.01"></td>
        <td id="poRowSubtotal${idx}">0.00</td>
        <td><button class="btn btn-sm btn-outline-danger" onclick="removePORow(${idx})"><i class="fas fa-trash"></i></button></td>`;
    document.getElementById('poItemsBody').appendChild(row);
}

function fillPORowPrices(idx, sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('poItemProductId'+idx).value  = sel.value;
    document.getElementById('poItemCost'+idx).value       = opt.dataset.cost || 0;
    document.getElementById('poItemSelling'+idx).value    = opt.dataset.price || 0;
    updatePORowSubtotal(idx);
}

function updatePORowSubtotal(idx) {
    const qty  = parseFloat(document.getElementById('poItemQty'+idx)?.value)  || 0;
    const cost = parseFloat(document.getElementById('poItemCost'+idx)?.value) || 0;
    document.getElementById('poRowSubtotal'+idx).textContent = formatCurrency(qty * cost);
    updatePOTotals();
}

function removePORow(idx) {
    document.getElementById('poRow'+idx)?.remove();
    updatePOTotals();
}

function updatePOTotals() {
    let total = 0;
    document.querySelectorAll('[id^="poItemQty"]').forEach(el => {
        const idx  = el.id.replace('poItemQty','');
        const qty  = parseFloat(el.value) || 0;
        const cost = parseFloat(document.getElementById('poItemCost'+idx)?.value) || 0;
        total += qty * cost;
    });
    const disc = parseFloat(document.getElementById('poDiscount').value) || 0;
    document.getElementById('poGrandTotal').textContent = formatCurrency(Math.max(0, total - disc));
}

async function savePO() {
    const items = [];
    document.querySelectorAll('[id^="poItemQty"]').forEach(el => {
        const idx = el.id.replace('poItemQty','');
        const productId = document.getElementById('poItemProductId'+idx)?.value;
        const name = productId
            ? document.querySelector(`#poRow${idx} select`)?.options[document.querySelector(`#poRow${idx} select`)?.selectedIndex]?.text
            : document.getElementById('poItemCustomName'+idx)?.value;
        items.push({
            product_id:    productId || null,
            product_name:  name || '',
            quantity:      parseInt(el.value),
            cost_price:    parseFloat(document.getElementById('poItemCost'+idx)?.value),
            selling_price: parseFloat(document.getElementById('poItemSelling'+idx)?.value) || null,
        });
    });

    if (!document.getElementById('poSupplier').value || items.length === 0) {
        showToast('{{ __("pos.error") }}: Please fill all required fields', 'danger'); return;
    }

    const res = await apiCall('{{ route("purchase-orders.store") }}', 'POST', {
        supplier_id:   document.getElementById('poSupplier').value,
        order_date:    document.getElementById('poOrderDate').value,
        expected_date: document.getElementById('poExpectedDate').value || null,
        discount:      document.getElementById('poDiscount').value || 0,
        notes:         document.getElementById('poNotes').value,
        items,
    });

    if (res.success) {
        showToast('{{ __("pos.success") }}');
        bootstrap.Modal.getInstance(document.getElementById('createPOModal')).hide();
        document.getElementById('poItemsBody').innerHTML = '';
        poItemCount = 0;
        loadPOs();
    } else {
        showToast(res.message || '{{ __("pos.error") }}', 'danger');
    }
}

function showReceivePO(po) {
    document.getElementById('receivePOId').value      = po.id;
    document.getElementById('receivePONumber').textContent = po.po_number;

    document.getElementById('receiveItemsBody').innerHTML = po.items.map(item => `
        <tr>
            <td>${item.product_name}</td>
            <td class="text-center">${item.quantity}</td>
            <td class="text-center">${item.received_quantity || 0}</td>
            <td><input type="number" class="form-control form-control-sm" id="recv_qty_${item.id}"
                value="${item.quantity - (item.received_quantity || 0)}" min="0"
                max="${item.quantity - (item.received_quantity || 0)}"></td>
            <td><input type="number" class="form-control form-control-sm" id="recv_cost_${item.id}"
                value="${item.cost_price}" step="0.01"></td>
            <td><input type="number" class="form-control form-control-sm" id="recv_selling_${item.id}"
                value="${item.selling_price || ''}" step="0.01"></td>
        </tr>`).join('');

    new bootstrap.Modal(document.getElementById('receivePOModal')).show();
    window._currentPOItems = po.items;
}

async function submitReceivePO() {
    const poId  = document.getElementById('receivePOId').value;
    const items = (window._currentPOItems || []).map(item => ({
        item_id:           item.id,
        received_quantity: parseInt(document.getElementById(`recv_qty_${item.id}`)?.value) || 0,
        cost_price:        parseFloat(document.getElementById(`recv_cost_${item.id}`)?.value) || null,
        selling_price:     parseFloat(document.getElementById(`recv_selling_${item.id}`)?.value) || null,
    }));

    const res = await apiCall(`/api/purchase-orders/${poId}/receive`, 'POST', { items });
    if (res.success) {
        showToast('{{ __("pos.success") }}');
        bootstrap.Modal.getInstance(document.getElementById('receivePOModal')).hide();
        loadPOs();
    } else {
        showToast(res.message || '{{ __("pos.error") }}', 'danger');
    }
}

loadSuppliers();
loadProductsList();
loadPOs();
</script>
@endpush
