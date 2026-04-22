{{-- FILE: resources/views/pos/index.blade.php --}}
@extends('layouts.app')
@section('title', __('pos.pos'))
@section('page-title', __('pos.pos'))

@push('styles')
<style>
    .pos-layout { display: grid; grid-template-columns: 1fr 380px; gap: 1rem; height: calc(100vh - 130px); }
    .pos-left { display: flex; flex-direction: column; gap: 1rem; overflow: hidden; }
    .pos-right { display: flex; flex-direction: column; gap: 0.75rem; }
    .cart-table-wrapper { flex: 1; overflow-y: auto; }
    .product-search { position: relative; }
    .search-results {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 100;
        background: #fff; border: 1px solid #dee2e6; border-radius: 0.5rem;
        box-shadow: 0 8px 24px rgba(0,0,0,.12); max-height: 300px; overflow-y: auto;
        display: none;
    }
    .search-results.show { display: block; }
    .search-item { padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid #f8f9fa; }
    .search-item:hover { background: #f0f9ff; }
    .cart-row td { vertical-align: middle; }
    .qty-btn { width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
    .total-section { background: #1e293b; color: #fff; border-radius: 0.75rem; padding: 1rem; }
    .payment-btn { flex: 1; padding: 0.6rem; border-radius: 0.5rem; font-weight: 600; }
    @media (max-width: 900px) { .pos-layout { grid-template-columns: 1fr; } .pos-right { order: -1; } }
</style>
@endpush

@section('content')
<div class="pos-layout">
    {{-- Left: Product Search & Cart --}}
    <div class="pos-left">
        {{-- Search --}}
        <div class="card">
            <div class="card-body py-2">
                <div class="product-search">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                        <input type="text" class="form-control form-control-lg" id="searchInput"
                            placeholder="{{ __('pos.scan_barcode') }} / {{ __('pos.search_product') }}"
                            autocomplete="off">
                        <button class="btn btn-primary" onclick="searchProduct()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div class="search-results" id="searchResults"></div>
                </div>
            </div>
        </div>

        {{-- Cart --}}
        <div class="card flex-grow-1">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-shopping-cart me-2"></i>{{ __('pos.cart_empty') }}</span>
                <span class="badge bg-primary" id="cartCount">0</span>
            </div>
            <div class="cart-table-wrapper">
                <table class="table table-hover mb-0" id="cartTable">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>#</th>
                            <th>{{ __('pos.product_name') }}</th>
                            <th>{{ __('pos.unit_price') }}</th>
                            <th>{{ __('pos.quantity') }}</th>
                            <th>{{ __('pos.subtotal') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <tr id="emptyRow">
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-shopping-cart fa-3x mb-3 d-block opacity-25"></i>
                                {{ __('pos.cart_empty') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Right: Order Summary --}}
    <div class="pos-right">
        {{-- Totals --}}
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('pos.subtotal') }}</span>
                    <span id="displaySubtotal">{{ __('pos.currency') }} 0.00</span>
                </div>
                <div class="d-flex justify-content-between mb-2 align-items-center">
                    <span class="text-muted">{{ __('pos.discount') }}</span>
                    <div class="input-group input-group-sm" style="width: 130px;">
                        <input type="number" class="form-control text-end" id="discountInput"
                            value="0" min="0" step="0.01" onchange="updateTotals()">
                        <span class="input-group-text">{{ __('pos.currency') }}</span>
                    </div>
                </div>
                <hr>
                <div class="total-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fs-5">{{ __('pos.total') }}</span>
                        <span class="fs-3 fw-bold" id="displayTotal">{{ __('pos.currency') }} 0.00</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Method --}}
        <div class="card">
            <div class="card-body">
                <label class="form-label fw-semibold">{{ __('pos.payment_method') }}</label>
                <div class="d-flex gap-2">
                    <button class="payment-btn btn btn-success" onclick="setPayment('cash')" id="btnCash">
                        <i class="fas fa-money-bill-wave d-block mb-1"></i>{{ __('pos.cash') }}
                    </button>
                    <button class="payment-btn btn btn-outline-secondary" onclick="setPayment('card')" id="btnCard">
                        <i class="fas fa-credit-card d-block mb-1"></i>{{ __('pos.card') }}
                    </button>
                    <button class="payment-btn btn btn-outline-secondary" onclick="setPayment('transfer')" id="btnTransfer">
                        <i class="fas fa-exchange-alt d-block mb-1"></i>{{ __('pos.transfer') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Cash Received (for cash payment) --}}
        <div class="card" id="cashPanel">
            <div class="card-body">
                <label class="form-label fw-semibold">{{ __('pos.cash') }} {{ __('pos.amount') }}</label>
                <input type="number" class="form-control" id="cashReceived" placeholder="0.00" onchange="calcChange()">
                <div class="d-flex justify-content-between mt-2">
                    <span class="text-muted">Change / الباقي</span>
                    <span class="fw-bold text-success" id="changeAmount">{{ __('pos.currency') }} 0.00</span>
                </div>
            </div>
        </div>

        {{-- Complete Sale Button --}}
        <button class="btn btn-primary btn-lg w-100 py-3" id="completeSaleBtn" onclick="completeSale()" disabled>
            <i class="fas fa-check-circle me-2"></i>{{ __('pos.complete_sale') }}
        </button>
    </div>
</div>

{{-- Invoice Modal --}}
<div class="modal fade" id="invoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>{{ __('pos.print_invoice') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="invoiceBody"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">{{ __('pos.cancel') }}</button>
                <button class="btn btn-primary" onclick="printInvoice()">
                    <i class="fas fa-print me-2"></i>{{ __('pos.print') }}
                </button>
                <button class="btn btn-success" onclick="newSale()">
                    <i class="fas fa-plus me-2"></i>{{ 'New Sale / بيعة جديدة' }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let cart = [];
    let paymentMethod = 'cash';
    let searchTimeout;

    // Search product
    document.getElementById('searchInput').addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (q.length < 2) { closeSearch(); return; }
        searchTimeout = setTimeout(() => searchProduct(q), 300);
    });

    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') searchProduct(this.value.trim());
    });

    async function searchProduct(q = null) {
        q = q || document.getElementById('searchInput').value.trim();
        if (!q) return;

        const res = await apiCall(`{{ route('products.search') }}?query=${encodeURIComponent(q)}`);
        if (res.success) {
            addToCart(res.product);
            document.getElementById('searchInput').value = '';
            closeSearch();
        } else {
            showToast(res.message, 'danger');
        }
    }

    function closeSearch() {
        document.getElementById('searchResults').classList.remove('show');
    }

    function addToCart(product) {
        const existing = cart.find(i => i.product_id === product.id);
        if (existing) {
            existing.quantity++;
        } else {
            if (product.quantity <= 0) { showToast('{{ __("pos.insufficient_stock", ["name" => "this product"]) }}', 'danger'); return; }
            cart.push({
                product_id:   product.id,
                product_name: product.name,
                price:        product.price,
                quantity:     1,
                max_qty:      product.quantity,
            });
        }
        renderCart();
    }

    function renderCart() {
        const tbody = document.getElementById('cartBody');
        const emptyRow = document.getElementById('emptyRow');

        if (cart.length === 0) {
            tbody.innerHTML = `<tr id="emptyRow"><td colspan="6" class="text-center text-muted py-5">
                <i class="fas fa-shopping-cart fa-3x mb-3 d-block opacity-25"></i>{{ __('pos.cart_empty') }}</td></tr>`;
            document.getElementById('completeSaleBtn').disabled = true;
            document.getElementById('cartCount').textContent = 0;
            updateTotals();
            return;
        }

        tbody.innerHTML = cart.map((item, idx) => `
            <tr class="cart-row">
                <td>${idx + 1}</td>
                <td>${item.product_name}</td>
                <td>${formatCurrency(item.price)}</td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-sm btn-outline-secondary qty-btn" onclick="changeQty(${idx}, -1)">−</button>
                        <input type="number" class="form-control form-control-sm text-center" style="width:60px"
                            value="${item.quantity}" min="1" max="${item.max_qty}"
                            onchange="setQty(${idx}, this.value)">
                        <button class="btn btn-sm btn-outline-secondary qty-btn" onclick="changeQty(${idx}, 1)">+</button>
                    </div>
                </td>
                <td class="fw-semibold">${formatCurrency(item.price * item.quantity)}</td>
                <td><button class="btn btn-sm btn-outline-danger" onclick="removeItem(${idx})"><i class="fas fa-trash"></i></button></td>
            </tr>`).join('');

        document.getElementById('completeSaleBtn').disabled = false;
        document.getElementById('cartCount').textContent = cart.reduce((s, i) => s + i.quantity, 0);
        updateTotals();
    }

    function changeQty(idx, delta) {
        cart[idx].quantity = Math.max(1, Math.min(cart[idx].max_qty, cart[idx].quantity + delta));
        renderCart();
    }

    function setQty(idx, val) {
        cart[idx].quantity = Math.max(1, Math.min(cart[idx].max_qty, parseInt(val) || 1));
        renderCart();
    }

    function removeItem(idx) {
        cart.splice(idx, 1);
        renderCart();
    }

    function updateTotals() {
        const subtotal = cart.reduce((s, i) => s + i.price * i.quantity, 0);
        const discount = parseFloat(document.getElementById('discountInput').value) || 0;
        const total    = Math.max(0, subtotal - discount);
        document.getElementById('displaySubtotal').textContent = formatCurrency(subtotal);
        document.getElementById('displayTotal').textContent    = formatCurrency(total);
        calcChange();
    }

    function calcChange() {
        const total = parseFloat(document.getElementById('displayTotal').textContent.replace(/[^\d.]/g, '')) || 0;
        const cash  = parseFloat(document.getElementById('cashReceived').value) || 0;
        document.getElementById('changeAmount').textContent = formatCurrency(Math.max(0, cash - total));
    }

    function setPayment(method) {
        paymentMethod = method;
        ['cash','card','transfer'].forEach(m => {
            const btn = document.getElementById('btn' + m.charAt(0).toUpperCase() + m.slice(1));
            btn.className = `payment-btn btn ${m === method ? 'btn-success' : 'btn-outline-secondary'}`;
        });
        document.getElementById('cashPanel').style.display = method === 'cash' ? 'block' : 'none';
    }

    async function completeSale() {
        if (cart.length === 0) return;

        const discount = parseFloat(document.getElementById('discountInput').value) || 0;
        const btn      = document.getElementById('completeSaleBtn');
        btn.disabled   = true;
        btn.innerHTML  = '<span class="spinner-border spinner-border-sm me-2"></span>{{ __("pos.loading") }}';

        try {
            const res = await apiCall('{{ route("invoices.create") }}', 'POST', {
                items:          cart.map(i => ({ product_id: i.product_id, product_name: i.product_name, quantity: i.quantity, price: i.price })),
                discount,
                payment_method: paymentMethod,
            });

            if (res.success) {
                showInvoiceModal(res.invoice);
                showToast('{{ __("pos.sale_completed") }}');
            } else {
                showToast(res.message, 'danger');
            }
        } catch(e) {
            showToast('{{ __("pos.error") }}', 'danger');
        } finally {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>{{ __("pos.complete_sale") }}';
        }
    }

    function showInvoiceModal(invoice) {
        const items = invoice.items.map(i => `
            <tr>
                <td>${i.product_name}</td>
                <td class="text-center">${i.quantity}</td>
                <td class="text-end">${formatCurrency(i.price)}</td>
                <td class="text-end">${formatCurrency(i.subtotal)}</td>
            </tr>`).join('');

        document.getElementById('invoiceBody').innerHTML = `
            <div class="text-center mb-3">
                <h5>{{ __('pos.app_name') }}</h5>
                <p class="text-muted mb-0">{{ __('pos.invoice_number') }}: <strong>${invoice.invoice_number}</strong></p>
                <small class="text-muted">${new Date().toLocaleString()}</small>
            </div>
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('pos.product_name') }}</th>
                        <th class="text-center">{{ __('pos.quantity') }}</th>
                        <th class="text-end">{{ __('pos.unit_price') }}</th>
                        <th class="text-end">{{ __('pos.subtotal') }}</th>
                    </tr>
                </thead>
                <tbody>${items}</tbody>
                <tfoot>
                    <tr><td colspan="3" class="text-end">{{ __('pos.subtotal') }}</td><td class="text-end">${formatCurrency(invoice.total)}</td></tr>
                    ${invoice.discount > 0 ? `<tr><td colspan="3" class="text-end text-danger">{{ __('pos.discount') }}</td><td class="text-end text-danger">-${formatCurrency(invoice.discount)}</td></tr>` : ''}
                    <tr class="table-dark fw-bold"><td colspan="3" class="text-end">{{ __('pos.total') }}</td><td class="text-end">${formatCurrency(invoice.final_total)}</td></tr>
                </tfoot>
            </table>
            <div class="text-center text-muted small mt-2">{{ __('pos.payment_method') }}: ${invoice.payment_method}</div>`;

        new bootstrap.Modal(document.getElementById('invoiceModal')).show();
    }

    function printInvoice() { window.print(); }

    function newSale() {
        cart = [];
        document.getElementById('discountInput').value = 0;
        document.getElementById('cashReceived').value = '';
        renderCart();
        bootstrap.Modal.getInstance(document.getElementById('invoiceModal')).hide();
        document.getElementById('searchInput').focus();
    }

    // Init
    setPayment('cash');
    document.addEventListener('click', e => { if (!e.target.closest('.product-search')) closeSearch(); });
</script>
@endpush
