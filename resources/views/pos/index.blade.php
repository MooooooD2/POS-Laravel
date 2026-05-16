@extends('layouts.app')
@section('title', __('pos.pos'))
@section('page-title', __('pos.pos'))

@push('styles')
    <style @nonce>
        .pos-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 1rem;
            min-height: calc(100vh - 130px);
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

        [data-theme="dark"] .tax-row           { background: #1e2d1a !important; }
        [data-theme="dark"] .search-results    { color: var(--body-color); }
        [data-theme="dark"] .search-item .barcode-badge { background: #334155; color: #94a3b8; }
        [data-theme="dark"] .pos-right .card   { border: 1px solid var(--card-border); }
        [data-theme="dark"] #selectedCustomerDisplay { background: #1e293b !important; color: var(--body-color); }

        /* ── Cart table dark mode ────────────────────────────────────────── */
        /* table-light thead — Bootstrap hardcodes #f8f9fa bg */
        [data-theme="dark"] thead.table-light th,
        [data-theme="dark"] .table-light {
            --bs-table-bg:           #0f172a !important;
            --bs-table-color:        #e2e8f0 !important;
            --bs-table-border-color: #334155 !important;
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
            border-color: #334155 !important;
        }

        /* Cart tbody rows */
        [data-theme="dark"] #cartTable tbody tr {
            background-color: var(--card-bg) !important;
            color: var(--body-color) !important;
        }

        [data-theme="dark"] #cartTable tbody tr:hover {
            background-color: #273548 !important;
        }

        /* Cart qty/price inputs */
        [data-theme="dark"] #cartTable input.form-control {
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
            border-color: #475569 !important;
        }

        /* Payment buttons inactive state */
        [data-theme="dark"] .payment-btn.btn-outline-secondary {
            color: #94a3b8 !important;
            border-color: #475569 !important;
            background: transparent !important;
        }

        [data-theme="dark"] .payment-btn.btn-outline-secondary:hover {
            background: #334155 !important;
            color: #e2e8f0 !important;
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
                            <button class="btn btn-outline-secondary" id="cameraScanBtn" title="Camera scan">
                                <i class="fas fa-camera"></i>
                            </button>
                            <button class="btn btn-primary" id="searchTriggerBtn">
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
                        <span id="cartTitle">{{ __('pos.cart') }}</span>
                    </span>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-primary rounded-pill" id="cartCount">0</span>
                        <button class="btn btn-sm btn-outline-danger" id="clearCartBtn"
                            style="display:none">
                            <i class="fas fa-trash me-1"></i>{{ __('pos.cancel') }}
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
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

            {{-- Customer Search --}}
            <div class="card">
                <div class="card-body py-2">
                    <label class="form-label fw-semibold small mb-1">
                        <i class="fas fa-user me-1 text-primary"></i>
                        {{ app()->getLocale() === 'ar' ? 'العميل (اختياري)' : 'Customer (optional)' }}
                    </label>
                    {{-- Selected customer display --}}
                    <div id="selectedCustomerDisplay" class="align-items-center justify-content-between mb-1 p-2 rounded bg-light d-none">
                        <div>
                            <div class="fw-semibold small" id="selectedCustomerName"></div>
                            <div class="text-muted" style="font-size:0.78rem" id="selectedCustomerPhone"></div>
                        </div>
                        <button class="btn btn-sm btn-outline-danger py-0 px-1" id="clearCustomerBtn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    {{-- Search input --}}
                    <div id="customerSearchBox" class="position-relative">
                        <input type="text" class="form-control form-control-sm" id="customerSearchInput"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث باسم أو هاتف...' : 'Search by name or phone...' }}"
                            autocomplete="off">
                        <div id="customerSearchResults" class="search-results" style="max-height:200px"></div>
                    </div>
                    {{-- Quick-add form (hidden by default) --}}
                    <div id="customerQuickAdd" class="mt-2 p-2 border rounded" style="display:none">
                        <div class="small fw-semibold mb-2 text-success">
                            <i class="fas fa-user-plus me-1"></i>
                            {{ app()->getLocale() === 'ar' ? 'إضافة عميل جديد' : 'Add new customer' }}
                        </div>
                        <input type="text" class="form-control form-control-sm mb-1" id="newCustomerName"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'الاسم' : 'Name' }}">
                        <input type="tel" class="form-control form-control-sm mb-1" id="newCustomerPhone"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'رقم الهاتف / واتساب' : 'Phone / WhatsApp' }}">
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-success flex-grow-1" id="saveNewCustomerBtn">
                                <i class="fas fa-plus me-1"></i>{{ app()->getLocale() === 'ar' ? 'حفظ' : 'Save' }}
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" id="cancelQuickAddBtn">
                                {{ app()->getLocale() === 'ar' ? 'إلغاء' : 'Cancel' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

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
                                min="0" step="0.01">
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
                        <button class="payment-btn btn" id="btnCash">
                            <i class="fas fa-money-bill-wave d-block mb-1"></i>{{ __('pos.cash') }}
                        </button>
                        <button class="payment-btn btn btn-outline-secondary" id="btnCard">
                            <i class="fas fa-credit-card d-block mb-1"></i>{{ __('pos.card') }}
                        </button>
                        <button class="payment-btn btn btn-outline-secondary" id="btnTransfer">
                            <i class="fas fa-exchange-alt d-block mb-1"></i>{{ __('pos.transfer') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Cash Received --}}
            <div class="card" id="cashPanel">
                <div class="card-body py-2">
                    <label class="form-label fw-semibold small">{{ __('pos.cash') }} {{ __('pos.amount') }}</label>
                    <input type="number" class="form-control" id="cashReceived" placeholder="0.00">
                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted small">{{ app()->getLocale() === 'ar' ? 'الباقي' : 'Change' }}</span>
                        <span class="fw-bold text-success" id="changeAmount">0.00</span>
                    </div>
                </div>
            </div>

            {{-- Complete Sale --}}
            <button class="btn btn-success btn-lg py-3 fw-bold" id="completeSaleBtn" disabled>
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
                    <button class="btn btn-outline-primary" id="printInvoiceBtn">
                        <i class="fas fa-print me-2"></i>{{ __('pos.print') }}
                    </button>
                    <button class="btn btn-outline-success d-none" id="waInvoiceBtn">
                        <i class="fab fa-whatsapp me-2"></i>{{ app()->getLocale() === 'ar' ? 'إرسال واتساب' : 'Send WhatsApp' }}
                    </button>
                    <button class="btn btn-success" id="newSaleBtn">
                        <i class="fas fa-plus me-2"></i>{{ app()->getLocale() === 'ar' ? 'بيعة جديدة' : 'New Sale' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── CAMERA BARCODE SCANNER MODAL ─────────────────────────────────────── --}}
    <div class="modal fade" id="cameraScanModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-camera me-2"></i>
                        {{ app()->getLocale() === 'ar' ? 'مسح الباركود بالكاميرا' : 'Camera Barcode Scan' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0 bg-black" style="position:relative;min-height:320px">
                    <video id="cameraVideo" style="width:100%;display:block;max-height:400px;object-fit:cover" autoplay
                        muted playsinline></video>
                    {{-- Scan frame overlay --}}
                    <div id="scanOverlay"
                        style="
                    position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
                    width:220px;height:140px;border:3px solid rgba(255,255,255,0.8);
                    border-radius:12px;pointer-events:none;
                    box-shadow:0 0 0 9999px rgba(0,0,0,0.45);
                    transition:border-color 0.3s,box-shadow 0.3s;
                ">
                        <div
                            style="position:absolute;top:-2px;left:-2px;width:22px;height:22px;border-top:4px solid #3b82f6;border-left:4px solid #3b82f6;border-radius:3px 0 0 0">
                        </div>
                        <div
                            style="position:absolute;top:-2px;right:-2px;width:22px;height:22px;border-top:4px solid #3b82f6;border-right:4px solid #3b82f6;border-radius:0 3px 0 0">
                        </div>
                        <div
                            style="position:absolute;bottom:-2px;left:-2px;width:22px;height:22px;border-bottom:4px solid #3b82f6;border-left:4px solid #3b82f6;border-radius:0 0 0 3px">
                        </div>
                        <div
                            style="position:absolute;bottom:-2px;right:-2px;width:22px;height:22px;border-bottom:4px solid #3b82f6;border-right:4px solid #3b82f6;border-radius:0 0 3px 0">
                        </div>
                        {{-- Scan line animation --}}
                        <div
                            style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#3b82f6,transparent);animation:scanLine 2s linear infinite">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-dark text-white d-flex justify-content-between align-items-center">
                    <span id="cameraStatus" class="small text-light">
                        <i class="fas fa-spinner fa-spin me-1"></i>
                        {{ app()->getLocale() === 'ar' ? 'جاري التحميل...' : 'Loading...' }}
                    </span>
                    <button id="switchCameraBtn" class="btn btn-sm btn-outline-light" style="display:none">
                        <i class="fas fa-sync-alt me-1"></i>
                        {{ app()->getLocale() === 'ar' ? 'تبديل الكاميرا' : 'Switch Camera' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style @nonce>
        @keyframes scanLine {
            0% {
                top: 0;
                opacity: 1;
            }

            90% {
                top: calc(100% - 2px);
                opacity: 1;
            }

            100% {
                top: 0;
                opacity: 0;
            }
        }
    </style>
@endsection

@push('scripts')
    <script @nonce>
        // Settings passed from controller
        const POS_SETTINGS = {
            waEnabled: {{ $waEnabled ? 'true' : 'false' }},
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
        let searchAbort = null;   // AbortController for in-flight search requests
        let lastKeyTime = Date.now();
        let currentInvoice = null;
        let lastSearchResults = [];

        // ── Customer widget state ──────────────────────────────────────────────────
        let selectedCustomerId = null;
        let customerSearchTimeout = null;
        let _customerResults = [];

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
            searchTimeout = setTimeout(() => showSearchResults(q), 150);
        });

        function triggerSearch() {
            const val = document.getElementById('searchInput').value.trim();
            if (val) handleSearch(val, false);
        }

        async function handleSearch(query, isScanner) {
            closeSearch();
            if (searchAbort) searchAbort.abort();
            searchAbort = new AbortController();
            try {
                const url = `{{ route('products.search') }}?query=${encodeURIComponent(query)}&exact=${isScanner ? 1 : 0}`;
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    signal: searchAbort.signal,
                });
                const res = await response.json();
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
            } catch (e) {
                if (e.name !== 'AbortError') showToast('{{ __('pos.product_not_found') }}', 'danger');
            }
        }

        async function showSearchResults(query) {
            if (searchAbort) searchAbort.abort();
            searchAbort = new AbortController();
            try {
                const response = await fetch(`{{ route('products.search') }}?query=${encodeURIComponent(query)}&exact=0`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    signal: searchAbort.signal,
                });
                const res = await response.json();
                if (!res.success) { closeSearch(); return; }
                if (res.single) {
                    addToCart(res.product);
                    document.getElementById('searchInput').value = '';
                    closeSearch();
                    if (POS_SETTINGS.posSound) beep();
                } else if (res.products?.length) {
                    renderSearchDropdown(res.products);
                }
            } catch (e) {
                if (e.name !== 'AbortError') closeSearch();
            }
        }

        function renderSearchDropdown(products) {
            lastSearchResults = products;
            const container = document.getElementById('searchResults');
            container.innerHTML = products.map((p, i) => `
        <div class="search-item" data-product-idx="${i}">
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
                // تنبيه لو الكمية المتبقية بعد البيع أقل من min_stock
                const remaining = existing.max_qty - existing.quantity;
                if (remaining <= (product.min_stock || 5) && remaining > 0) {
                    showToast(`⚠️ تنبيه: ${product.name} — متبقي ${remaining} قطعة فقط`, 'warning');
                }
            } else {
                if (product.quantity <= 0) {
                    showToast('{{ __('pos.insufficient_stock') }}', 'danger');
                    return;
                }
                cart.push({
                    product_id:  product.id,
                    product_name: product.name,
                    price:       product.price,
                    quantity:    1,
                    max_qty:     product.quantity,
                    min_stock:   product.min_stock || 5,
                });
                // تنبيه لو المنتج أصلاً على وشك النفاذ
                if (product.quantity <= (product.min_stock || 5)) {
                    showToast(`⚠️ ${product.name} — مخزون منخفض (${product.quantity} قطعة)`, 'warning');
                }
            }
            renderCart();
        }

        function buildCartRowHTML(item, idx) {
            return `<tr class="cart-row" data-cart-idx="${idx}" data-product-id="${item.product_id}">
            <td class="text-muted small">${idx + 1}</td>
            <td><div class="fw-semibold">${escapeHtml(item.product_name)}</div></td>
            <td class="text-end">
                <input type="number" class="form-control form-control-sm text-center p-1"
                    style="width:80px" value="${item.price}" step="0.01" min="0"
                    data-action="set-price" data-idx="${idx}">
            </td>
            <td class="text-center">
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-sm btn-outline-secondary qty-btn" data-action="dec-qty" data-idx="${idx}">−</button>
                    <input type="number" class="form-control form-control-sm text-center p-1"
                        style="width:55px" value="${item.quantity}" min="1" max="${item.max_qty}"
                        data-action="set-qty" data-idx="${idx}">
                    <button class="btn btn-sm btn-outline-secondary qty-btn" data-action="inc-qty" data-idx="${idx}">+</button>
                </div>
            </td>
            <td class="fw-semibold text-success text-end" data-cell="total">${formatCurrency(item.price * item.quantity)}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-danger qty-btn" data-action="remove" data-idx="${idx}">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>`;
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

            // Remove empty-state row if present (it has no .cart-row class so querySelector misses it)
            const emptyRow = document.getElementById('emptyRow');
            if (emptyRow) emptyRow.remove();

            const existingRows = tbody.querySelectorAll('tr.cart-row');

            // Patch existing rows / add new ones / remove extras
            cart.forEach((item, idx) => {
                const existing = existingRows[idx];
                const newHTML = buildCartRowHTML(item, idx);
                if (!existing) {
                    tbody.insertAdjacentHTML('beforeend', newHTML);
                } else {
                    // Only replace the row if data-cart-idx changed (item reordered/removed)
                    // or update just the dynamic cells to avoid losing input focus
                    const idxAttr = parseInt(existing.dataset.cartIdx);
                    if (idxAttr !== idx || existing.dataset.productId !== String(item.product_id)) {
                        existing.outerHTML = newHTML;
                    } else {
                        // Update only quantity input, price input, and total cell
                        const qtyInput   = existing.querySelector('[data-action="set-qty"]');
                        const priceInput = existing.querySelector('[data-action="set-price"]');
                        const totalCell  = existing.querySelector('[data-cell="total"]');
                        if (document.activeElement !== qtyInput)   qtyInput.value   = item.quantity;
                        if (document.activeElement !== priceInput) priceInput.value = item.price;
                        totalCell.textContent = formatCurrency(item.price * item.quantity);
                    }
                }
            });

            // Remove surplus rows (items were deleted)
            for (let i = cart.length; i < existingRows.length; i++) {
                existingRows[i].remove();
            }

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

        // ─── CUSTOMER WIDGET ─────────────────────────────────────────────────────────
        document.getElementById('customerSearchInput').addEventListener('input', function() {
            clearTimeout(customerSearchTimeout);
            const q = this.value.trim();
            if (q.length < 1) { closeCustomerSearch(); return; }
            customerSearchTimeout = setTimeout(() => searchCustomers(q), 250);
        });

        async function searchCustomers(q) {
            try {
                const res = await fetch(`{{ route('customers.search') }}?q=${encodeURIComponent(q)}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                });
                const data = await res.json();
                if (!data.success) return;
                renderCustomerDropdown(data.customers, q);
            } catch (e) {}
        }

        function renderCustomerDropdown(customers, q) {
            _customerResults = customers;
            const isAr = LOCALE === 'ar';
            const container = document.getElementById('customerSearchResults');
            let html = customers.map((c, i) => `
                <div class="search-item" data-cust-idx="${i}">
                    <div>
                        <div class="fw-semibold">${escapeHtml(c.name)}</div>
                        <small class="text-muted">${c.phone ? escapeHtml(c.phone) : ''}</small>
                    </div>
                    <span class="badge bg-primary">${escapeHtml(c.code)}</span>
                </div>`).join('');
            html += `<div class="search-item text-success border-top" id="addNewCustomerOption">
                <i class="fas fa-user-plus me-1"></i>
                ${isAr ? 'إضافة عميل جديد' : 'Add new customer'}
            </div>`;
            container.innerHTML = html;
            container.classList.add('show');
        }

        function closeCustomerSearch() {
            document.getElementById('customerSearchResults').classList.remove('show');
        }

        function selectCustomer(customer) {
            selectedCustomerId = customer.id;
            document.getElementById('selectedCustomerName').textContent = customer.name;
            document.getElementById('selectedCustomerPhone').textContent = customer.phone || '';
            document.getElementById('selectedCustomerDisplay').classList.remove('d-none');
            document.getElementById('selectedCustomerDisplay').classList.add('d-flex');
            document.getElementById('customerSearchBox').classList.add('d-none');
            closeCustomerSearch();
            document.getElementById('customerSearchInput').value = '';
        }

        function clearCustomer() {
            selectedCustomerId = null;
            document.getElementById('selectedCustomerDisplay').classList.add('d-none');
            document.getElementById('selectedCustomerDisplay').classList.remove('d-flex');
            document.getElementById('customerSearchBox').classList.remove('d-none');
            document.getElementById('customerQuickAdd').style.display = 'none';
            document.getElementById('newCustomerName').value = '';
            document.getElementById('newCustomerPhone').value = '';
        }

        document.getElementById('customerSearchResults').addEventListener('click', function(e) {
            const item = e.target.closest('[data-cust-idx]');
            if (item) { selectCustomer(_customerResults[parseInt(item.dataset.custIdx)]); return; }
            if (e.target.closest('#addNewCustomerOption')) {
                closeCustomerSearch();
                document.getElementById('customerQuickAdd').style.display = 'block';
                document.getElementById('newCustomerName').focus();
            }
        });

        document.getElementById('clearCustomerBtn').addEventListener('click', clearCustomer);
        document.getElementById('cancelQuickAddBtn').addEventListener('click', function() {
            document.getElementById('customerQuickAdd').style.display = 'none';
        });

        document.getElementById('saveNewCustomerBtn').addEventListener('click', async function() {
            const name = document.getElementById('newCustomerName').value.trim();
            const phone = document.getElementById('newCustomerPhone').value.trim();
            if (!name) { showToast(LOCALE === 'ar' ? 'الاسم مطلوب' : 'Name is required', 'danger'); return; }
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            try {
                const res = await apiCall('{{ route('customers.store') }}', 'POST', { name, phone });
                if (res.success) {
                    selectCustomer(res.customer);
                    document.getElementById('customerQuickAdd').style.display = 'none';
                    document.getElementById('newCustomerName').value = '';
                    document.getElementById('newCustomerPhone').value = '';
                    showToast(LOCALE === 'ar' ? 'تم إضافة العميل' : 'Customer added');
                } else {
                    showToast(res.message || (LOCALE === 'ar' ? 'خطأ' : 'Error'), 'danger');
                }
            } catch (e) {
                showToast(LOCALE === 'ar' ? 'خطأ في الاتصال' : 'Connection error', 'danger');
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<i class="fas fa-plus me-1"></i>${LOCALE === 'ar' ? 'حفظ' : 'Save'}`;
            }
        });

        // Close customer dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#customerSearchBox') && !e.target.closest('#customerSearchResults')) {
                closeCustomerSearch();
            }
        });

        // ─── COMPLETE SALE ────────────────────────────────────────────────────────────
        async function completeSale() {
            if (!cart.length) return;
            const btn = document.getElementById('completeSaleBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>{{ __('pos.loading') }}';

            const discount = parseFloat(document.getElementById('discountInput').value) || 0;

            // إرسال المبلغ المستلم للكاش فقط
            const cashReceived = paymentMethod === 'cash'
                ? (parseFloat(document.getElementById('cashReceived').value) || 0)
                : null;

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
                    cash_received: cashReceived,
                    customer_id: selectedCustomerId || null,
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
            const isRTL  = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
            const alignment = isRTL ? 'right' : 'left';
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const C = {
                theadBg:      isDark ? '#0f172a'  : '#f8f9fa',
                theadColor:   isDark ? '#e2e8f0'  : '#212529',
                border:       isDark ? '#334155'  : '#dee2e6',
                mutedColor:   isDark ? '#94a3b8'  : '#6c757d',
                sectionBg:    isDark ? '#1e293b'  : '#f8f9fa',
                sectionColor: isDark ? '#e2e8f0'  : '#212529',
                taxBg:        isDark ? '#1e2d1a'  : '#fef9ee',
                taxColor:     isDark ? '#6ee7b7'  : '#856404',
                changeBg:     isDark ? '#451a03'  : '#fff3cd',
                changeColor:  isDark ? '#fcd34d'  : '#856404',
            };

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
                `<tr style="background-color:${C.taxBg};">
            <td colspan="3" style="padding:8px;text-align:right;color:${C.taxColor};">
                ${getTaxName()} (${taxRate}%)
            </td>
            <td style="padding:8px;text-align:right;color:${C.taxColor};">+${formatCurrency(taxAmount)}</td>
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
        <table style="width:100%;border-collapse:collapse;margin-bottom:15px;color:${C.sectionColor};">
            <thead style="background-color:${C.theadBg};color:${C.theadColor};">
                <tr>
                    <th style="padding:8px;text-align:${alignment};border-bottom:2px solid ${C.border};color:${C.theadColor};">{{ __('pos.product_name') }}</th>
                    <th style="padding:8px;text-align:center;border-bottom:2px solid ${C.border};color:${C.theadColor};">{{ __('pos.quantity') }}</th>
                    <th style="padding:8px;text-align:right;border-bottom:2px solid ${C.border};color:${C.theadColor};">{{ __('pos.unit_price') }}</th>
                    <th style="padding:8px;text-align:right;border-bottom:2px solid ${C.border};color:${C.theadColor};">{{ __('pos.subtotal') }}</th>
                </tr>
            </thead>
            <tbody>${itemsHtml}</tbody>
            <tfoot>
                <tr style="border-top:1px solid ${C.border};color:${C.sectionColor};">
                    <td colspan="3" style="padding:8px;text-align:right;">{{ __('pos.subtotal') }}</td>
                    <td style="padding:8px;text-align:right;">${formatCurrency(invoice.subtotal || invoice.total)}</td>
                </tr>
                ${discRow}
                ${taxRow}
                <tr style="background-color:#1e293b;color:#fff;font-weight:bold;">
                    <td colspan="3" style="padding:10px;text-align:right;">{{ __('pos.total') }}</td>
                    <td style="padding:10px;text-align:right;">${formatCurrency(invoice.final_total)}</td>
                </tr>
            </tfoot>
        </table>
        <div style="margin-top:15px;padding:12px;background:${C.sectionBg};border-radius:8px;font-size:13px;color:${C.sectionColor};">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="color:${C.mutedColor};">{{ app()->getLocale() === 'ar' ? 'طريقة الدفع' : 'Payment Method' }}</span>
                <span style="font-weight:600;color:${C.sectionColor};">${getPaymentMethodText(invoice.payment_method)}</span>
            </div>
            ${invoice.payment_method === 'cash' && invoice.cash_received != null ? `
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;color:#198754;">
                <span style="font-weight:600;">{{ app()->getLocale() === 'ar' ? '💵 المبلغ المدفوع' : '💵 Cash Received' }}</span>
                <span style="font-weight:700;font-size:15px;">${formatCurrency(invoice.cash_received)}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px;background:${C.changeBg};border-radius:6px;font-weight:700;">
                <span style="color:${C.changeColor};">{{ app()->getLocale() === 'ar' ? '🔄 الباقي للزبون' : '🔄 Change Due' }}</span>
                <span style="color:${C.changeColor};font-size:16px;">${formatCurrency(invoice.change_amount ?? 0)}</span>
            </div>
            ` : ''}
        </div>
        ${POS_SETTINGS.invoiceFooter ? `<div style="text-align: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid #dee2e6; color: #6c757d; font-size: 11px;">${escapeHtml(POS_SETTINGS.invoiceFooter)}</div>` : ''}
        <div style="text-align: center; margin-top: 10px; font-size: 11px;">
            <small>{{ __('pos.thank_you') }}</small>
        </div>
    `;

            document.getElementById('invoiceBody').innerHTML = invoiceHtml;

            const waBtn = document.getElementById('waInvoiceBtn');
            if (waBtn) {
                waBtn.classList.toggle('d-none', !(POS_SETTINGS.waEnabled && invoice.customer_phone));
            }

            new bootstrap.Modal(document.getElementById('invoiceModal')).show();
        }

        async function sendInvoiceWhatsApp() {
            if (!currentInvoice) return;
            const btn = document.getElementById('waInvoiceBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>{{ app()->getLocale() === 'ar' ? 'جاري الإرسال...' : 'Sending...' }}';
            try {
                const res = await apiCall(`/api/whatsapp/invoices/${currentInvoice.id}/send`, 'POST', {});
                if (res.success) {
                    showToast('{{ app()->getLocale() === 'ar' ? 'تم إرسال الفاتورة عبر واتساب' : 'Invoice sent via WhatsApp' }}');
                    btn.classList.add('d-none');
                } else {
                    showToast(res.message || '{{ app()->getLocale() === 'ar' ? 'فشل الإرسال' : 'Send failed' }}', 'danger');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fab fa-whatsapp me-2"></i>{{ app()->getLocale() === 'ar' ? 'إرسال واتساب' : 'Send WhatsApp' }}';
                }
            } catch {
                showToast('{{ app()->getLocale() === 'ar' ? 'خطأ في الإرسال' : 'Send error' }}', 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="fab fa-whatsapp me-2"></i>{{ app()->getLocale() === 'ar' ? 'إرسال واتساب' : 'Send WhatsApp' }}';
            }
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
            printWindow.onafterprint = function() {
                printWindow.close();
            };
        }

        function generatePrintableInvoice(invoice) {
            const isRTL = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
            const direction = isRTL ? 'rtl' : 'ltr';
            const textAlignHead = isRTL ? 'right' : 'left';
            const textAlignPrice = 'right'; // الأسعار دائماً باليمين

            // ✅ إعداد التواريخ
            const now = new Date();
            const dateOptions = {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            };
            const timeOptions = {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            const formattedDate = now.toLocaleDateString(isRTL ? 'ar-EG' : 'en-EG', dateOptions);
            const formattedTime = now.toLocaleTimeString(isRTL ? 'ar-EG' : 'en-EG', timeOptions);

            // ✅ قيم الفاتورة
            const subtotal   = invoice.subtotal || invoice.total || 0;
            const discount   = invoice.discount || 0;
            const tax        = invoice.tax_amount || 0;
            const finalTotal = invoice.final_total || (subtotal - discount + tax);
            // cash_received وchange_amount جايين من الـ API مباشرة
            const paid   = invoice.cash_received ?? finalTotal;
            const change = invoice.change_amount ?? 0;
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

            // ✅ إظهار المدفوع والباقي — من قيم الـ API المحفوظة في DB
            const cashPaymentRows = (invoice.payment_method === 'cash' && invoice.cash_received != null) ? `
        <tr style="border-top:2px solid #333;">
            <td colspan="3" style="padding:6px 4px; text-align:${textAlignPrice}; font-weight:bold;">${labels.paidLabel}</td>
            <td style="padding:6px 4px; text-align:${textAlignPrice}; font-weight:bold; color:#198754;">${formatCurrency(invoice.cash_received)}</td>
        </tr>
        <tr style="background:#fff3cd;">
            <td colspan="3" style="padding:8px 4px; text-align:${textAlignPrice}; font-weight:bold;">${labels.changeLabel}</td>
            <td style="padding:8px 4px; text-align:${textAlignPrice}; font-weight:bold; font-size:15px; color:#856404;">${formatCurrency(invoice.change_amount ?? 0)}</td>
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
            clearCustomer();
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
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
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

        // ─── CAMERA BARCODE SCANNER ───────────────────────────────────────────────
        let cameraStream = null;
        let scannerActive = false;
        let zxingReader = null;

        async function openCameraModal() {
            const modal = new bootstrap.Modal(document.getElementById('cameraScanModal'));
            modal.show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('cameraScanModal').addEventListener('shown.bs.modal', startCameraScanner);
            document.getElementById('cameraScanModal').addEventListener('hidden.bs.modal', stopCameraScanner);
        });

        async function startCameraScanner() {
            const video = document.getElementById('cameraVideo');
            const statusEl = document.getElementById('cameraStatus');
            const switchBtn = document.getElementById('switchCameraBtn');

            scannerActive = true;
            statusEl.textContent =
                '{{ app()->getLocale() === 'ar' ? 'جاري تشغيل الكاميرا...' : 'Starting camera...' }}';

            try {
                // Load ZXing dynamically
                if (!window.ZXing) {
                    statusEl.textContent =
                        '{{ app()->getLocale() === 'ar' ? 'جاري تحميل مكتبة المسح...' : 'Loading scanner library...' }}';
                    await loadScript('https://cdn.jsdelivr.net/npm/@zxing/library@0.19.1/umd/index.min.js');
                }

                zxingReader = new ZXing.BrowserMultiFormatReader();

                // Get available cameras
                // Get available cameras
                // Get available cameras
                const devices = await zxingReader.listVideoInputDevices();

                if (!devices.length) {
                    throw new Error('No camera found');
                }

                // في اللابتوب اختار أول كاميرا مباشرة
                let selectedDevice = devices[0];

                // لو موبايل حاول يجيب الخلفية
                const backCam = devices.find(d =>
                    /back|rear|environment/i.test(d.label)
                );

                if (backCam) {
                    selectedDevice = backCam;
                }

                window._cameraDevices = devices;
                window._currentCamIdx = devices.indexOf(selectedDevice);

                if (devices.length > 1) {
                    switchBtn.style.display = 'inline-flex';
                }

                switchBtn.onclick = async () => {

    if (!window._cameraDevices?.length) return;

    window._currentCamIdx =
        (window._currentCamIdx + 1) % window._cameraDevices.length;

    const nextDevice =
        window._cameraDevices[window._currentCamIdx];

    statusEl.innerHTML =
        '<i class="fas fa-sync-alt fa-spin me-1"></i>' +
        '{{ app()->getLocale() === 'ar' ? 'جاري تبديل الكاميرا...' : 'Switching camera...' }}';

    await startDecode(nextDevice.deviceId);
};
                startDecode(selectedDevice.deviceId);

            } catch (err) {
                statusEl.innerHTML =
                    `<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>${err.message || '{{ app()->getLocale() === 'ar' ? 'تعذر الوصول للكاميرا' : 'Cannot access camera' }}'}</span>`;
                console.error('Camera error:', err);
            }
        }

        async function startDecode(deviceId) {
    const video = document.getElementById('cameraVideo');
    const statusEl = document.getElementById('cameraStatus');
    const overlay = document.getElementById('scanOverlay');

    statusEl.textContent =
        '{{ app()->getLocale() === 'ar' ? 'وجّه الكاميرا نحو الباركود...' : 'Point camera at barcode...' }}';

    // وقف أي قراءة قديمة
    try {
        zxingReader.reset();
    } catch (e) {}

    zxingReader.decodeFromConstraints(
        {
            video: {
                deviceId: deviceId ? { exact: deviceId } : undefined,
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        },
        video,
        (result, err) => {

            if (!scannerActive) return;

            if (result) {
                const code = result.getText();

                overlay.style.borderColor = '#22c55e';
                overlay.style.boxShadow =
                    '0 0 0 4px rgba(34,197,94,0.4)';

                setTimeout(() => {
                    overlay.style.borderColor = '';
                    overlay.style.boxShadow = '';
                }, 500);

                if (POS_SETTINGS.posSound) beep();

                bootstrap.Modal
                    .getInstance(
                        document.getElementById('cameraScanModal')
                    )
                    .hide();

                document.getElementById('searchInput').value = code;

                handleSearch(code, true);
            }
        }
    );
}
        function stopCameraScanner() {
            scannerActive = false;
            if (zxingReader) {
                try {
                    zxingReader.reset();
                } catch (e) {}
                zxingReader = null;
            }
        }

        function loadScript(src) {
            return new Promise((resolve, reject) => {
                if (document.querySelector(`script[src="${src}"]`)) {
                    resolve();
                    return;
                }
                const s = document.createElement('script');
                s.src = src;
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }

        // ─── EVENT DELEGATION: cart table ────────────────────────────────────────
        document.getElementById('cartBody').addEventListener('click', function(e) {
            const el = e.target.closest('[data-action]');
            if (!el) return;
            const idx = parseInt(el.dataset.idx);
            const action = el.dataset.action;
            if (action === 'dec-qty') changeQty(idx, -1);
            else if (action === 'inc-qty') changeQty(idx, 1);
            else if (action === 'remove') removeItem(idx);
        });

        document.getElementById('cartBody').addEventListener('change', function(e) {
            const el = e.target.closest('[data-action]');
            if (!el) return;
            const idx = parseInt(el.dataset.idx);
            const action = el.dataset.action;
            if (action === 'set-price') setPrice(idx, el.value);
            else if (action === 'set-qty') setQty(idx, el.value);
        });

        // ─── EVENT DELEGATION: search dropdown ───────────────────────────────────
        document.getElementById('searchResults').addEventListener('click', function(e) {
            const item = e.target.closest('[data-product-idx]');
            if (!item) return;
            selectProduct(lastSearchResults[parseInt(item.dataset.productIdx)]);
        });

        // ─── STATIC BUTTON LISTENERS ─────────────────────────────────────────────
        document.getElementById('cameraScanBtn').addEventListener('click', openCameraModal);
        document.getElementById('searchTriggerBtn').addEventListener('click', triggerSearch);
        document.getElementById('clearCartBtn').addEventListener('click', clearCart);
        document.getElementById('discountInput').addEventListener('change', updateTotals);
        document.getElementById('btnCash').addEventListener('click', () => setPayment('cash'));
        document.getElementById('btnCard').addEventListener('click', () => setPayment('card'));
        document.getElementById('btnTransfer').addEventListener('click', () => setPayment('transfer'));
        document.getElementById('cashReceived').addEventListener('input', calcChange);
        document.getElementById('completeSaleBtn').addEventListener('click', completeSale);
        document.getElementById('printInvoiceBtn').addEventListener('click', printInvoice);
        document.getElementById('newSaleBtn').addEventListener('click', newSale);
        const waInvoiceBtn = document.getElementById('waInvoiceBtn');
        if (waInvoiceBtn) waInvoiceBtn.addEventListener('click', sendInvoiceWhatsApp);

        // ─── INIT ─────────────────────────────────────────────────────────────────
        setPayment(POS_SETTINGS.defaultPayment);
        document.addEventListener('click', e => {
            if (!e.target.closest('.product-search')) closeSearch();
        });
        document.getElementById('searchInput').focus();
    </script>
@endpush
