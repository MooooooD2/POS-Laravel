{{-- FILE: resources/views/returns/index.blade.php --}}
@extends('layouts.app')
@section('title', __('pos.returns'))
@section('page-title', __('pos.returns'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-undo me-2"></i>{{ __('pos.returns') }}</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#returnModal">
            <i class="fas fa-plus me-1"></i>{{ __('pos.process_return') }}
        </button>
    </div>
</div>

{{-- Return Modal --}}
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('pos.process_return') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Step 1: Find Invoice --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Step 1: {{ __('pos.invoice_number') }}</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="returnInvoiceNum"
                            placeholder="INV-20240101-0001">
                        <button class="btn btn-outline-primary" onclick="findInvoice()">
                            <i class="fas fa-search"></i> {{ __('pos.search') }}
                        </button>
                    </div>
                </div>

                {{-- Invoice Info --}}
                <div id="invoiceInfo" style="display:none" class="alert alert-info mb-3">
                    <strong id="invoiceInfoText"></strong>
                </div>

                {{-- Step 2: Select Items --}}
                <div id="returnItemsSection" style="display:none">
                    <h6 class="fw-semibold mb-2">Step 2: Select items to return</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('pos.product_name') }}</th>
                                    <th>Can Return</th>
                                    <th>Return Qty</th>
                                    <th>{{ __('pos.unit_price') }}</th>
                                    <th>{{ __('pos.subtotal') }}</th>
                                </tr>
                            </thead>
                            <tbody id="returnItemsBody"></tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="4" class="text-end">{{ __('pos.total') }} Return</td>
                                    <td id="returnTotal">0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="returnCustomer">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('pos.return_reason') }}</label>
                            <input type="text" class="form-control" id="returnReason">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">{{ __('pos.cancel') }}</button>
                <button class="btn btn-warning" id="submitReturnBtn" style="display:none" onclick="submitReturn()">
                    <i class="fas fa-undo me-1"></i>{{ __('pos.process_return') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentInvoice = null;
let returnableItems = [];

async function findInvoice() {
    const num = document.getElementById('returnInvoiceNum').value.trim();
    if (!num) return;

    const res = await apiCall(`{{ route('invoices.by-number') }}?number=${encodeURIComponent(num)}`);
    if (!res.success) { showToast(res.message, 'danger'); return; }

    currentInvoice = res.invoice;
    document.getElementById('invoiceInfo').style.display = 'block';
    document.getElementById('invoiceInfoText').textContent =
        `{{ __('pos.invoice_number') }}: ${res.invoice.invoice_number} | Total: ${formatCurrency(res.invoice.final_total)}`;

    // Load returnable items
    const itemsRes = await apiCall(`{{ url('/api/invoices') }}/${res.invoice.id}/returnable-items`);
    returnableItems = itemsRes.items || [];

    if (returnableItems.length === 0) {
        showToast('No items available for return', 'danger'); return;
    }

    document.getElementById('returnItemsSection').style.display = 'block';
    document.getElementById('submitReturnBtn').style.display = 'inline-block';

    document.getElementById('returnItemsBody').innerHTML = returnableItems.map((item, i) => `
        <tr>
            <td>${item.product_name}</td>
            <td class="text-center fw-semibold">${item.returnable_qty}</td>
            <td style="width:100px">
                <input type="number" class="form-control form-control-sm" id="retQty${i}"
                    value="0" min="0" max="${item.returnable_qty}" onchange="updateReturnTotal()">
            </td>
            <td>${formatCurrency(item.price)}</td>
            <td id="retSubtotal${i}">0.00</td>
        </tr>`).join('');
}

function updateReturnTotal() {
    let total = 0;
    returnableItems.forEach((item, i) => {
        const qty  = parseInt(document.getElementById(`retQty${i}`)?.value) || 0;
        const sub  = qty * item.price;
        total += sub;
        document.getElementById(`retSubtotal${i}`).textContent = formatCurrency(sub);
    });
    document.getElementById('returnTotal').textContent = formatCurrency(total);
}

async function submitReturn() {
    const items = returnableItems
        .map((item, i) => ({
            product_id:   item.product_id,
            product_name: item.product_name,
            quantity:     parseInt(document.getElementById(`retQty${i}`)?.value) || 0,
            price:        item.price,
        }))
        .filter(i => i.quantity > 0);

    if (!items.length) { showToast('Select at least one item to return', 'danger'); return; }

    const res = await apiCall('{{ route("returns.store") }}', 'POST', {
        invoice_id:    currentInvoice.id,
        customer_name: document.getElementById('returnCustomer').value,
        reason:        document.getElementById('returnReason').value,
        items,
    });

    if (res.success) {
        showToast('{{ __("pos.success") }}');
        bootstrap.Modal.getInstance(document.getElementById('returnModal')).hide();
        // Reset
        currentInvoice = null; returnableItems = [];
        document.getElementById('returnInvoiceNum').value = '';
        document.getElementById('invoiceInfo').style.display = 'none';
        document.getElementById('returnItemsSection').style.display = 'none';
        document.getElementById('submitReturnBtn').style.display = 'none';
    } else {
        showToast(res.message || '{{ __("pos.error") }}', 'danger');
    }
}
</script>
@endpush


{{-- ============================================================
FILE: resources/views/supplier-payments/index.blade.php
============================================================ --}}
{{-- @extends('layouts.app') ... (follows same pattern as suppliers) --}}

{{-- ============================================================
FILE: resources/views/supplier-accounts/index.blade.php
============================================================ --}}
{{-- Shows supplier list with button to view their account ledger --}}
