@extends('layouts.app')
@section('title', __('pos.pos'))
@section('page-title', __('pos.pos'))

@push('styles')
    <style>
        .pos-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 1rem;
            height: calc(100vh - 130px);
        }

        .pos-left {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            overflow: hidden;
        }

        .pos-right {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            overflow-y: auto;
        }

        .cart-table-wrapper {
            flex: 1;
            overflow-y: auto;
        }

        .product-search {
            position: relative;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 200;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .15);
            max-height: 320px;
            overflow-y: auto;
            display: none;
        }

        .search-results.show {
            display: block;
        }

        .search-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .search-item:hover {
            background: #f0f9ff;
        }

        .search-item .barcode-badge {
            font-family: monospace;
            font-size: 0.75rem;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            color: #64748b;
        }

        .cart-row td {
            vertical-align: middle;
        }

        .qty-btn {
            width: 28px;
            height: 28px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .total-section {
            background: #1e293b;
            color: #fff;
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .payment-btn {
            flex: 1;
            padding: 0.6rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .tax-row {
            background: #fef9ee;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
        }

        /* Scanner pulse animation */
        @keyframes scanPulse {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: 0.3
            }
        }

        .scanning {
            animation: scanPulse 0.5s ease-in-out;
        }

        @media (max-width: 960px) {
            .pos-layout {
                grid-template-columns: 1fr;
            }

            .pos-right {
                order: -1;
            }
        }
    </style>
@endpush

@section('content')
    <div class="pos-layout">
        {{-- Left: Search & Cart --}}
        <div class="pos-left">

            {{-- Barcode Search --}}
            <div class="card">
                <div class="card-body py-2">
                    <div class="product-search">
                        <div class="input-group">
                            <span class="input-group-text" id="barcodeIcon" title="Scan or type">
                                <i class="fas fa-barcode"></i>
                            </span>
                            <input type="text" class="form-control form-control-lg" id="searchInput"
                                placeholder="{{ __('pos.scan_barcode') }} / {{ __('pos.search_product') }}"
                                autocomplete="off" autofocus>
                            <button class="btn btn-outline-secondary" title="Camera scan" onclick="openCameraModal()">
                                <i class="fas fa-camera"></i>
                            </button>
                            <button class="btn btn-primary" onclick="triggerSearch()">
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
                    <span>
                        <i class="fas fa-shopping-cart me-2 text-primary"></i>
                        <span id="cartTitle">{{ __('pos.cart_empty') }}</span>
                    </span>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-primary rounded-pill" id="cartCount">0</span>
                        <button class="btn btn-sm btn-outline-danger" onclick="clearCart()" id="clearCartBtn"
                            style="display:none">
                            <i class="fas fa-trash me-1"></i>{{ __('pos.cancel') }}
                        </button>
                    </div>
                </div>
                <div class="cart-table-wrapper">
                    <table class="table table-hover mb-0" id="cartTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width:35px">#</th>
                                <th>{{ __('pos.product_name') }}</th>
                                <th style="width:100px">{{ __('pos.unit_price') }}</th>
                                <th style="width:120px">{{ __('pos.quantity') }}</th>
                                <th style="width:110px">{{ __('pos.subtotal') }}</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="cartBody">
                            <tr id="emptyRow">
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-barcode fa-3x mb-3 d-block opacity-20"></i>
                                    {{ __('pos.scan_barcode') }}<br>
                                    <small class="text-muted">{{ __('pos.cart_empty') }}</small>
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
                        <span id="displaySubtotal">{{ $settings['currency_symbol'] ?? 'ج.م' }} 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 align-items-center">
                        <span class="text-muted">{{ __('pos.discount') }}</span>
                        <div class="input-group input-group-sm" style="width:140px;">
                            <input type="number" class="form-control text-end" id="discountInput" value="0"
                                min="0" step="0.01" onchange="updateTotals()">
                            <span class="input-group-text">{{ $settings['currency_symbol'] ?? 'ج.م' }}</span>
                        </div>
                    </div>

                    @if ($settings['tax_enabled'])
                        <div class="d-flex justify-content-between mb-2 align-items-center tax-row" id="taxRow">
                            <span class="text-warning fw-semibold">
                                <i class="fas fa-percent me-1"></i>
                                {{ app()->getLocale() === 'ar' ? $settings['tax_name_ar'] : $settings['tax_name_en'] }}
                                ({{ $settings['tax_rate'] }}%)
                                @if ($settings['tax_inclusive'])
                                    <small class="text-muted"> - {{ __('pos.tax_inclusive') }}</small>
                                @endif
                            </span>
                            <span id="displayTax" class="text-warning fw-semibold">0.00</span>
                        </div>
                    @endif

                    <hr class="my-2">
                    <div class="total-section">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fs-5">{{ __('pos.total') }}</span>
                            <span class="fs-2 fw-bold" id="displayTotal">0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment Method --}}
            <div class="card">
                <div class="card-body pb-2">
                    <label class="form-label fw-semibold mb-2">{{ __('pos.payment_method') }}</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="payment-btn btn" id="btnCash" onclick="setPayment('cash')">
                            <i class="fas fa-money-bill-wave d-block mb-1"></i>{{ __('pos.cash') }}
                        </button>
                        <button class="payment-btn btn btn-outline-secondary" id="btnCard" onclick="setPayment('card')">
                            <i class="fas fa-credit-card d-block mb-1"></i>{{ __('pos.card') }}
                        </button>
                        <button class="payment-btn btn btn-outline-secondary" id="btnTransfer"
                            onclick="setPayment('transfer')">
                            <i class="fas fa-exchange-alt d-block mb-1"></i>{{ __('pos.transfer') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Cash Received --}}
            <div class="card" id="cashPanel">
                <div class="card-body py-2">
                    <label class="form-label fw-semibold small">{{ __('pos.cash') }} {{ __('pos.amount') }}</label>
                    <input type="number" class="form-control" id="cashReceived" placeholder="0.00"
                        oninput="calcChange()">
                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted small">{{ app()->getLocale() === 'ar' ? 'الباقي' : 'Change' }}</span>
                        <span class="fw-bold text-success" id="changeAmount">0.00</span>
                    </div>
                </div>
            </div>

            {{-- Complete Sale --}}
            <button class="btn btn-success btn-lg py-3 fw-bold" id="completeSaleBtn" onclick="completeSale()" disabled>
                <i class="fas fa-check-circle me-2"></i>{{ __('pos.complete_sale') }}
            </button>
        </div>
    </div>

    {{-- Invoice Modal --}}
    <div class="modal fade" id="invoiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-receipt me-2 text-success"></i>{{ __('pos.print_invoice') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="invoiceBody"></div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">{{ __('pos.cancel') }}</button>
                    <button class="btn btn-outline-primary" onclick="printInvoice()">
                        <i class="fas fa-print me-2"></i>{{ __('pos.print') }}
                    </button>
                    <button class="btn btn-success" onclick="newSale()">
                        <i class="fas fa-plus me-2"></i>{{ app()->getLocale() === 'ar' ? 'بيعة جديدة' : 'New Sale' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Settings passed from controller
        const POS_SETTINGS = {
            taxEnabled: {{ $settings['tax_enabled'] ? 'true' : 'false' }},
            taxRate: {{ (float) ($settings['tax_rate'] ?? 0) }},
            taxInclusive: {{ $settings['tax_inclusive'] ? 'true' : 'false' }},
            taxNameAr: '{{ addslashes($settings['tax_name_ar'] ?? 'ضريبة') }}',
            taxNameEn: '{{ addslashes($settings['tax_name_en'] ?? 'VAT') }}',
            posSound: {{ $settings['pos_sound'] ? 'true' : 'false' }},
            invoiceFooter: '{{ addslashes($settings['invoice_footer'] ?? '') }}',
            storeName: '{{ addslashes($settings['store_name'] ?? '') }}',
            storeAddress: '{{ addslashes($settings['store_address'] ?? '') }}',
            storePhone: '{{ addslashes($settings['store_phone'] ?? '') }}',
            defaultPayment: '{{ $settings['default_payment'] ?? 'cash' }}',
            autoPrint: {{ $settings['auto_print'] ? 'true' : 'false' }},
            currencySymbol: '{{ $settings['currency_symbol'] ?? 'ج.م' }}',
        };

        let cart = [];
        let paymentMethod = POS_SETTINGS.defaultPayment;
        let searchTimeout = null;
        let lastKeyTime = Date.now();
        let currentInvoice = null;

        // ─── BARCODE SCANNER SUPPORT ──────────────────────────────────────────────────
        document.getElementById('searchInput').addEventListener('keydown', function(e) {
            const now = Date.now();
            const timeDiff = now - lastKeyTime;
            lastKeyTime = now;

            if (e.key === 'Enter') {
                e.preventDefault();
                const val = this.value.trim();
                if (val) {
                    const isScanner = timeDiff < 80;
                    handleSearch(val, isScanner);
                }
                return;
            }
        });

        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const q = this.value.trim();
            if (q.length < 2) {
                closeSearch();
                return;
            }
            searchTimeout = setTimeout(() => showSearchResults(q), 350);
        });

        function triggerSearch() {
            const val = document.getElementById('searchInput').value.trim();
            if (val) handleSearch(val, false);
        }

        async function handleSearch(query, isScanner) {
            closeSearch();
            const url = `{{ route('products.search') }}?query=${encodeURIComponent(query)}&exact=${isScanner ? 1 : 0}`;
            const res = await apiCall(url);

            if (!res.success) {
                showToast(res.message || '{{ __('pos.product_not_found') }}', 'danger');
                document.getElementById('searchInput').value = '';
                return;
            }

            if (res.single) {
                addToCart(res.product);
                document.getElementById('searchInput').value = '';
                if (POS_SETTINGS.posSound) beep();
            } else {
                renderSearchDropdown(res.products);
            }
        }

        async function showSearchResults(query) {
            const res = await apiCall(`{{ route('products.search') }}?query=${encodeURIComponent(query)}&exact=0`);
            if (!res.success) {
                closeSearch();
                return;
            }
            if (res.single) {
                addToCart(res.product);
                document.getElementById('searchInput').value = '';
                closeSearch();
                if (POS_SETTINGS.posSound) beep();
            } else if (res.products?.length) {
                renderSearchDropdown(res.products);
            }
        }

        function renderSearchDropdown(products) {
            const container = document.getElementById('searchResults');
            container.innerHTML = products.map(p => `
        <div class="search-item" onclick='selectProduct(${JSON.stringify(p)})'>
            <div>
                <div class="fw-semibold">${escapeHtml(p.name)}</div>
                <small class="text-muted">${p.category || ''}</small>
            </div>
            <div class="text-end">
                <div class="fw-bold text-success">${formatCurrency(p.price)}</div>
                ${p.barcode ? `<span class="barcode-badge">${escapeHtml(p.barcode)}</span>` : ''}
                <small class="text-${p.quantity > 0 ? 'success' : 'danger'} d-block">${p.quantity} {{ app()->getLocale() === 'ar' ? 'قطعة' : 'pcs' }}</small>
            </div>
        </div>`).join('');
            container.classList.add('show');
        }

        function selectProduct(product) {
            addToCart(product);
            document.getElementById('searchInput').value = '';
            closeSearch();
            if (POS_SETTINGS.posSound) beep();
        }

        function closeSearch() {
            document.getElementById('searchResults').classList.remove('show');
        }

        // ─── CART MANAGEMENT ─────────────────────────────────────────────────────────
        function addToCart(product) {
            const existing = cart.find(i => i.product_id === product.id);
            if (existing) {
                if (existing.quantity + 1 > existing.max_qty) {
                    showToast('{{ __('pos.insufficient_stock') }}', 'danger');
                    return;
                }
                existing.quantity++;
            } else {
                if (product.quantity <= 0) {
                    showToast('{{ __('pos.insufficient_stock') }}', 'danger');
                    return;
                }
                cart.push({
                    product_id: product.id,
                    product_name: product.name,
                    price: product.price,
                    quantity: 1,
                    max_qty: product.quantity,
                });
            }
            renderCart();
        }

        function renderCart() {
            const tbody = document.getElementById('cartBody');

            if (cart.length === 0) {
                tbody.innerHTML = `<tr id="emptyRow"><td colspan="6" class="text-center text-muted py-5">
            <i class="fas fa-barcode fa-3x mb-3 d-block opacity-20"></i>
            {{ __('pos.scan_barcode') }}<br><small>{{ __('pos.cart_empty') }}</small>
         </td></tr>`;
                document.getElementById('completeSaleBtn').disabled = true;
                document.getElementById('clearCartBtn').style.display = 'none';
                document.getElementById('cartCount').textContent = 0;
                updateTotals();
                return;
            }

            tbody.innerHTML = cart.map((item, idx) => `
        <tr class="cart-row">
            <td class="text-muted small">${idx + 1}</td>
            <td>
                <div class="fw-semibold">${escapeHtml(item.product_name)}</div>
            </td>
            <td class="text-end">
                <input type="number" class="form-control form-control-sm text-center p-1"
                    style="width:80px" value="${item.price}" step="0.01" min="0"
                    onchange="setPrice(${idx}, this.value)">
            </td>
            <td class="text-center">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-sm btn-outline-secondary qty-btn" onclick="changeQty(${idx},-1)">−</button>
                    <input type="number" class="form-control form-control-sm text-center p-1"
                        style="width:55px" value="${item.quantity}" min="1" max="${item.max_qty}"
                        onchange="setQty(${idx}, this.value)">
                    <button class="btn btn-sm btn-outline-secondary qty-btn" onclick="changeQty(${idx},1)">+</button>
                </div>
            </td>
            <td class="fw-semibold text-success text-end">${formatCurrency(item.price * item.quantity)}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-danger qty-btn" onclick="removeItem(${idx})">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>`).join('');

            document.getElementById('completeSaleBtn').disabled = false;
            document.getElementById('clearCartBtn').style.display = 'inline-block';
            document.getElementById('cartCount').textContent = cart.reduce((s, i) => s + i.quantity, 0);
            updateTotals();
        }

        function changeQty(idx, delta) {
            const newQty = cart[idx].quantity + delta;
            if (newQty >= 1 && newQty <= cart[idx].max_qty) {
                cart[idx].quantity = newQty;
                renderCart();
            }
        }

        function setQty(idx, val) {
            let newQty = parseInt(val) || 1;
            newQty = Math.max(1, Math.min(cart[idx].max_qty, newQty));
            cart[idx].quantity = newQty;
            renderCart();
        }

        function setPrice(idx, val) {
            cart[idx].price = Math.max(0, parseFloat(val) || 0);
            updateTotals();
        }

        function removeItem(idx) {
            cart.splice(idx, 1);
            renderCart();
        }

        function clearCart() {
            cart = [];
            renderCart();
        }

        // ─── TOTALS WITH TAX ─────────────────────────────────────────────────────────
        function updateTotals() {
            const subtotal = cart.reduce((s, i) => s + i.price * i.quantity, 0);
            const discount = parseFloat(document.getElementById('discountInput').value) || 0;
            const afterDiscount = subtotal - discount;

            let taxAmount = 0;
            let finalTotal = afterDiscount;

            if (POS_SETTINGS.taxEnabled && POS_SETTINGS.taxRate > 0) {
                if (POS_SETTINGS.taxInclusive) {
                    taxAmount = afterDiscount - (afterDiscount / (1 + POS_SETTINGS.taxRate / 100));
                } else {
                    taxAmount = afterDiscount * (POS_SETTINGS.taxRate / 100);
                    finalTotal = afterDiscount + taxAmount;
                }
            }

            document.getElementById('displaySubtotal').textContent = formatCurrency(subtotal);
            const taxEl = document.getElementById('displayTax');
            if (taxEl) taxEl.textContent = formatCurrency(taxAmount);
            document.getElementById('displayTotal').textContent = formatCurrency(finalTotal);
            calcChange();
        }

        function calcChange() {
            const totalText = document.getElementById('displayTotal').textContent.replace(/[^\d.-]/g, '');
            const total = parseFloat(totalText) || 0;
            const cash = parseFloat(document.getElementById('cashReceived').value) || 0;
            document.getElementById('changeAmount').textContent = formatCurrency(Math.max(0, cash - total));
        }

        // ─── PAYMENT ──────────────────────────────────────────────────────────────────
        function setPayment(method) {
            paymentMethod = method;
            ['btnCash', 'btnCard', 'btnTransfer'].forEach(btnId => {
                const btn = document.getElementById(btnId);
                const btnMethod = btnId.replace('btn', '').toLowerCase();
                if (btnMethod === method) {
                    btn.className = 'payment-btn btn btn-success';
                } else {
                    btn.className = 'payment-btn btn btn-outline-secondary';
                }
            });
            document.getElementById('cashPanel').style.display = method === 'cash' ? 'block' : 'none';
        }

        // ─── COMPLETE SALE ────────────────────────────────────────────────────────────
        async function completeSale() {
            if (!cart.length) return;
            const btn = document.getElementById('completeSaleBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>{{ __('pos.loading') }}';

            const discount = parseFloat(document.getElementById('discountInput').value) || 0;

            try {
                const res = await apiCall('{{ route('invoices.create') }}', 'POST', {
                    items: cart.map(i => ({
                        product_id: i.product_id,
                        product_name: i.product_name,
                        quantity: i.quantity,
                        price: i.price,
                    })),
                    discount,
                    payment_method: paymentMethod,
                });

                if (res.success) {
                    currentInvoice = res.invoice;
                    showInvoiceModal(res.invoice);
                    showToast('{{ __('pos.sale_completed') }}');
                    if (POS_SETTINGS.autoPrint) setTimeout(() => printInvoice(), 800);
                } else {
                    showToast(res.message, 'danger');
                }
            } catch (e) {
                console.error(e);
                showToast('{{ __('pos.error') }}', 'danger');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>{{ __('pos.complete_sale') }}';
            }
        }

        // ─── INVOICE MODAL ────────────────────────────────────────────────────────────
        function showInvoiceModal(invoice) {
            const isRTL = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
            const alignment = isRTL ? 'right' : 'left';

            const itemsHtml = invoice.items.map(i => `
        <tr>
            <td style="padding: 8px; text-align: ${alignment};">${escapeHtml(i.product_name)}</td>
            <td style="padding: 8px; text-align: center;">${i.quantity}</td>
            <td style="padding: 8px; text-align: right;">${formatCurrency(i.price)}</td>
            <td style="padding: 8px; text-align: right;">${formatCurrency(i.subtotal)}</td>
        </tr>`).join('');

            const taxAmount = invoice.tax_amount || 0;
            const taxRate = invoice.tax_rate || POS_SETTINGS.taxRate;

            const taxRow = (POS_SETTINGS.taxEnabled && taxAmount > 0) ?
                `<tr style="background-color: #fef9ee;">
            <td colspan="3" style="padding: 8px; text-align: right; color: #856404;">
                ${getTaxName()} (${taxRate}%)
            </td>
            <td style="padding: 8px; text-align: right; color: #856404;">+${formatCurrency(taxAmount)}</td>
        </tr>` :
                '';

            const discRow = (invoice.discount && invoice.discount > 0) ?
                `<tr>
            <td colspan="3" style="padding: 8px; text-align: right; color: #dc3545;">{{ __('pos.discount') }}</td>
            <td style="padding: 8px; text-align: right; color: #dc3545;">-${formatCurrency(invoice.discount)}</td>
        </tr>` :
                '';

            const invoiceHtml = `
        <div style="text-align: center; margin-bottom: 15px;">
            <h4 style="margin: 0; font-weight: bold;">${escapeHtml(POS_SETTINGS.storeName || '{{ __('pos.app_name') }}')}</h4>
            ${POS_SETTINGS.storeAddress ? `<p style="margin: 5px 0; color: #6c757d; font-size: 12px;">${escapeHtml(POS_SETTINGS.storeAddress)}</p>` : ''}
            ${POS_SETTINGS.storePhone ? `<p style="margin: 5px 0; color: #6c757d; font-size: 12px;"><i class="fas fa-phone"></i> ${escapeHtml(POS_SETTINGS.storePhone)}</p>` : ''}
            <hr style="margin: 10px 0;">
            <div style="display: flex; justify-content: space-between; font-size: 13px;">
                <span style="color: #6c757d;">{{ __('pos.invoice_number') }}:</span>
                <span style="font-weight: bold;">${invoice.invoice_number}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px;">
                <span style="color: #6c757d;">{{ __('pos.date') }}:</span>
                <span>${new Date().toLocaleString()}</span>
            </div>
        </div>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
            <thead style="background-color: #f8f9fa;">
                <tr>
                    <th style="padding: 8px; text-align: ${alignment}; border-bottom: 2px solid #dee2e6;">{{ __('pos.product_name') }}</th>
                    <th style="padding: 8px; text-align: center; border-bottom: 2px solid #dee2e6;">{{ __('pos.quantity') }}</th>
                    <th style="padding: 8px; text-align: right; border-bottom: 2px solid #dee2e6;">{{ __('pos.unit_price') }}</th>
                    <th style="padding: 8px; text-align: right; border-bottom: 2px solid #dee2e6;">{{ __('pos.subtotal') }}</th>
                </tr>
            </thead>
            <tbody>${itemsHtml}</tbody>
            <tfoot>
                <tr style="border-top: 1px solid #dee2e6;">
                    <td colspan="3" style="padding: 8px; text-align: right;">{{ __('pos.subtotal') }}</td>
                    <td style="padding: 8px; text-align: right;">${formatCurrency(invoice.subtotal || invoice.total)}</td>
                </tr>
                ${discRow}
                ${taxRow}
                <tr style="background-color: #1e293b; color: white; font-weight: bold;">
                    <td colspan="3" style="padding: 10px; text-align: right;">{{ __('pos.total') }}</td>
                    <td style="padding: 10px; text-align: right;">${formatCurrency(invoice.final_total)}</td>
                </tr>
            </tfoot>
        </table>
        <div style="text-align: center; margin-top: 15px;">
            <small style="color: #6c757d;">{{ __('pos.payment_method') }}: ${getPaymentMethodText(invoice.payment_method)}</small>
        </div>
        ${POS_SETTINGS.invoiceFooter ? `<div style="text-align: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid #dee2e6; color: #6c757d; font-size: 11px;">${escapeHtml(POS_SETTINGS.invoiceFooter)}</div>` : ''}
        <div style="text-align: center; margin-top: 10px; font-size: 11px;">
            <small>{{ __('pos.thank_you') }}</small>
        </div>
    `;

            document.getElementById('invoiceBody').innerHTML = invoiceHtml;
            new bootstrap.Modal(document.getElementById('invoiceModal')).show();
        }

        function printInvoice() {
    if (!currentInvoice) {
        showToast('لا توجد فاتورة للطباعة', 'danger');
        return;
    }
    const printableHtml = generatePrintableInvoice(currentInvoice);
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printableHtml);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.onafterprint = function() { printWindow.close(); };
}

function generatePrintableInvoice(invoice) {
    const isRTL = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
    const direction = isRTL ? 'rtl' : 'ltr';
    const textAlignHead = isRTL ? 'right' : 'left';
    const textAlignPrice = 'right'; // الأسعار دائماً باليمين

    // ✅ إعداد التواريخ
    const now = new Date();
    const dateOptions = { year: 'numeric', month: '2-digit', day: '2-digit' };
    const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
    const formattedDate = now.toLocaleDateString(isRTL ? 'ar-EG' : 'en-EG', dateOptions);
    const formattedTime = now.toLocaleTimeString(isRTL ? 'ar-EG' : 'en-EG', timeOptions);

    // ✅ قيم الفاتورة
    const subtotal = invoice.subtotal || invoice.total || 0;
    const discount = invoice.discount || 0;
    const tax = invoice.tax_amount || 0;
    const finalTotal = invoice.final_total || (subtotal - discount + tax);
    const paid = invoice.paid_amount || (invoice.payment_method === 'cash' ? finalTotal : finalTotal);
    const change = invoice.change_amount || Math.max(0, paid - finalTotal);
    const cashierName = invoice.cashier_name || 'مسؤول المخزون';

    // ✅ ترجمة المحتوى
    const labels = {
        header: isRTL ? 'نظام نقطة البيع' : 'POS System',
        invoiceNo: isRTL ? 'رقم الفاتورة' : 'Invoice No',
        date: isRTL ? 'التاريخ' : 'Date',
        time: isRTL ? 'الوقت' : 'Time',
        product: isRTL ? 'اسم المنتج' : 'Product',
        qty: isRTL ? 'الكمية' : 'Qty',
        price: isRTL ? 'سعر الوحدة' : 'Price',
        total: isRTL ? 'الإجمالي' : 'Total',
        subtotalLabel: isRTL ? 'المجموع الفرعي' : 'Subtotal',
        discountLabel: isRTL ? 'الخصم' : 'Discount',
        taxLabel: isRTL ? 'الضريبة' : 'Tax',
        finalLabel: isRTL ? 'الإجمالي النهائي' : 'Grand Total',
        paidLabel: isRTL ? 'المدفوع' : 'Paid',
        changeLabel: isRTL ? 'الباقي' : 'Change',
        paymentMethod: isRTL ? 'طريقة الدفع' : 'Payment Method',
        cashier: isRTL ? 'أمين الصندوق' : 'Cashier',
        thankYou: isRTL ? 'شكراً لتسوقكم معنا' : 'Thank you for shopping with us'
    };

    // ✅ بناء جدول المنتجات
    const itemsRows = invoice.items.map(item => `
        <tr>
            <td style="padding:6px 4px; border-bottom:1px solid #ccc; text-align:${textAlignHead};">${escapeHtml(item.product_name)}</td>
            <td style="padding:6px 4px; border-bottom:1px solid #ccc; text-align:center;">${item.quantity}</td>
            <td style="padding:6px 4px; border-bottom:1px solid #ccc; text-align:${textAlignPrice};">${formatCurrency(item.price)}</td>
            <td style="padding:6px 4px; border-bottom:1px solid #ccc; text-align:${textAlignPrice};">${formatCurrency(item.subtotal)}</td>
        </tr>
    `).join('');

    // ✅ إظهار الضريبة فقط إذا كانت موجودة وفعالة
    const taxRowHtml = (POS_SETTINGS.taxEnabled && tax > 0) ? `
        <tr>
            <td colspan="3" style="padding:6px 4px; text-align:${textAlignPrice}; font-weight:bold;">${labels.taxLabel} (${POS_SETTINGS.taxRate}%)</td>
            <td style="padding:6px 4px; text-align:${textAlignPrice};">${formatCurrency(tax)}</td>
        </tr>
    ` : '';

    // ✅ إظهار الخصم فقط إذا كان موجوداً
    const discountRowHtml = (discount > 0) ? `
        <tr>
            <td colspan="3" style="padding:6px 4px; text-align:${textAlignPrice}; color:#d9534f;">${labels.discountLabel}</td>
            <td style="padding:6px 4px; text-align:${textAlignPrice}; color:#d9534f;">-${formatCurrency(discount)}</td>
        </tr>
    ` : '';

    // ✅ إظهار المدفوع والباقي فقط إذا كانت طريقة الدفع نقدي
    const cashPaymentRows = (invoice.payment_method === 'cash') ? `
        <tr>
            <td colspan="3" style="padding:6px 4px; text-align:${textAlignPrice};">${labels.paidLabel}</td>
            <td style="padding:6px 4px; text-align:${textAlignPrice};">${formatCurrency(paid)}</td>
        </tr>
        <tr>
            <td colspan="3" style="padding:6px 4px; text-align:${textAlignPrice};">${labels.changeLabel}</td>
            <td style="padding:6px 4px; text-align:${textAlignPrice};">${formatCurrency(change)}</td>
        </tr>
    ` : '';

    // ✅ HTML كامل للطباعة
    return `<!DOCTYPE html>
    <html dir="${direction}" lang="${isRTL ? 'ar' : 'en'}">
    <head>
        <title>${labels.invoiceNo} ${invoice.invoice_number}</title>
        <meta charset="utf-8">
        <style>
            body {
                font-family: ${isRTL ? "'Cairo', 'Segoe UI', Tahoma, sans-serif" : "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"};
                font-size: 13px;
                line-height: 1.4;
                margin: 0;
                padding: 15px;
                background: #fff;
                width: 100%;
                max-width: 350px;
                margin: 0 auto;
            }
            .invoice-box {
                border: 1px solid #ddd;
                padding: 12px;
                border-radius: 5px;
            }
            .header {
                text-align: center;
                margin-bottom: 15px;
                padding-bottom: 8px;
                border-bottom: 1px dashed #aaa;
            }
            .store-name {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .invoice-title {
                font-size: 14px;
                font-weight: bold;
                margin-top: 5px;
            }
            .info-line {
                display: flex;
                justify-content: space-between;
                margin: 4px 0;
                font-size: 12px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 12px 0;
            }
            th {
                background-color: #f2f2f2;
                padding: 6px 4px;
                font-size: 12px;
                border-bottom: 1px solid #aaa;
                text-align: ${textAlignHead};
            }
            td {
                padding: 4px;
            }
            .totals-table {
                margin-top: 8px;
                border-top: 1px solid #ccc;
            }
            .footer {
                text-align: center;
                margin-top: 15px;
                font-size: 11px;
                color: #555;
                border-top: 1px dashed #aaa;
                padding-top: 8px;
            }
            .thankyou {
                margin-top: 8px;
                font-weight: bold;
            }
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                }
                .invoice-box {
                    border: none;
                    padding: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="invoice-box">
            <div class="header">
                <div class="store-name">${escapeHtml(POS_SETTINGS.storeName || labels.header)}</div>
                <div class="invoice-title">${labels.invoiceNo}: ${invoice.invoice_number}</div>
            </div>

            <div class="info-line">
                <span>${labels.date}: ${formattedDate}</span>
                <span>${labels.time}: ${formattedTime}</span>
            </div>

            <!-- Table header -->
            <table>
                <thead>
                    <tr>
                        <th style="text-align:${textAlignHead};">${labels.product}</th>
                        <th style="text-align:center;">${labels.qty}</th>
                        <th style="text-align:${textAlignPrice};">${labels.price}</th>
                        <th style="text-align:${textAlignPrice};">${labels.total}</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows}
                </tbody>
            </table>

            <!-- Totals section -->
            <table class="totals-table">
                <tr>
                    <td colspan="3" style="text-align:${textAlignPrice}; font-weight:bold;">${labels.subtotalLabel}</td>
                    <td style="text-align:${textAlignPrice};">${formatCurrency(subtotal)}</td>
                </tr>
                ${discountRowHtml}
                ${taxRowHtml}
                <tr style="border-top:1px solid #aaa; font-weight:bold;">
                    <td colspan="3" style="text-align:${textAlignPrice};">${labels.finalLabel}</td>
                    <td style="text-align:${textAlignPrice};">${formatCurrency(finalTotal)}</td>
                </tr>
                ${cashPaymentRows}
            </table>

            <!-- Payment & Cashier -->
            <div style="margin-top:12px;">
                <div class="info-line">
                    <span>${labels.paymentMethod}:</span>
                    <span>${getPaymentMethodText(invoice.payment_method)}</span>
                </div>
                <div class="info-line">
                    <span>${labels.cashier}:</span>
                    <span>${escapeHtml(cashierName)}</span>
                </div>
            </div>

            <div class="footer">
                ${POS_SETTINGS.invoiceFooter ? `<div>${escapeHtml(POS_SETTINGS.invoiceFooter)}</div>` : ''}
                <div class="thankyou">${labels.thankYou}</div>
            </div>
        </div>
    </body>
    </html>`;
}
        function getTaxName() {
            const isArabic = document.documentElement.lang === 'ar' || document.documentElement.dir === 'rtl';
            return isArabic ? POS_SETTINGS.taxNameAr : POS_SETTINGS.taxNameEn;
        }

        function getPaymentMethodText(method) {
            const isArabic = document.documentElement.lang === 'ar' || document.documentElement.dir === 'rtl';
            const methods = {
                'cash': isArabic ? 'نقدي' : '{{ __('pos.cash') }}',
                'card': isArabic ? 'بطاقة' : '{{ __('pos.card') }}',
                'transfer': isArabic ? 'تحويل بنكي' : '{{ __('pos.transfer') }}'
            };
            return methods[method] || method;
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function newSale() {
            cart = [];
            currentInvoice = null;
            document.getElementById('discountInput').value = 0;
            document.getElementById('cashReceived').value = '';
            renderCart();
            const modal = bootstrap.Modal.getInstance(document.getElementById('invoiceModal'));
            if (modal) modal.hide();
            setTimeout(() => document.getElementById('searchInput').focus(), 300);
        }

        // ─── BEEP SOUND (scanner feedback) ───────────────────────────────────────────
        function beep() {
            try {
                const ctx = new(window.AudioContext || window.webkitAudioContext)();
                const o = ctx.createOscillator();
                const g = ctx.createGain();
                o.connect(g);
                g.connect(ctx.destination);
                o.frequency.value = 880;
                o.type = 'sine';
                g.gain.setValueAtTime(0.3, ctx.currentTime);
                g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
                o.start(ctx.currentTime);
                o.stop(ctx.currentTime + 0.15);
            } catch (e) {
                // Audio context might be blocked by browser
            }
        }

        // ─── HELPER FUNCTIONS ─────────────────────────────────────────────────────────
        function formatCurrency(amount) {
            const symbol = POS_SETTINGS.currencySymbol;
            const isArabic = document.documentElement.lang === 'ar' || document.documentElement.dir === 'rtl';
            if (isArabic) {
                // Arabic format: symbol after number
                return `${parseFloat(amount).toFixed(2)} ${symbol}`;
            }
            return `${symbol} ${parseFloat(amount).toFixed(2)}`;
        }

        async function apiCall(url, method = 'GET', data = null) {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            };
            if (data) options.body = JSON.stringify(data);

            const response = await fetch(url, options);
            return await response.json();
        }

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? '#28a745' : (type === 'danger' ? '#dc3545' : '#ffc107');
            toast.className = `position-fixed bottom-0 end-0 m-3`;
            toast.style.zIndex = '9999';
            toast.style.minWidth = '200px';
            toast.style.backgroundColor = bgColor;
            toast.style.color = 'white';
            toast.style.padding = '12px 20px';
            toast.style.borderRadius = '8px';
            toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            toast.innerHTML =
                `<i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'danger' ? 'exclamation-triangle' : 'info-circle')} me-2"></i>${message}`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        function openCameraModal() {
            showToast('{{ app()->getLocale() === 'ar' ? 'قريباً: مسح الكاميرا' : 'Coming soon: Camera scan' }}', 'info');
        }

        // Init
        setPayment(POS_SETTINGS.defaultPayment);
        document.addEventListener('click', e => {
            if (!e.target.closest('.product-search')) closeSearch();
        });
        document.getElementById('searchInput').focus();
    </script>
@endpush
