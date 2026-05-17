{{--
=============================================================
REMAINING BLADE VIEWS - باقي الواجهات
=============================================================
Each file listed below with its path and content summary.
Full implementations follow the same pattern as pos/index.blade.php
=============================================================
--}}

{{-- ============================================================
FILE: resources/views/warehouse/index.blade.php
DESCRIPTION: Product management with search, CRUD, stock management
============================================================ --}}
@extends('layouts.app')
@section('title', __('pos.warehouse'))
@section('page-title', __('pos.warehouse'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-boxes me-2"></i>{{ __('pos.warehouse') }}</span>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#unitsModal">
                <i class="fas fa-ruler me-1"></i>{{ __('pos.manage_units') }}
            </button>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus me-1"></i>{{ __('pos.add_product') }}
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" id="productSearch"
                    placeholder="{{ __('pos.search') }}..." data-on-input="filterProducts">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="categoryFilter" data-on-change="filterProducts">
                    <option value="">{{ __('pos.category') }} - {{ __('pos.filter') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="stockFilter" data-on-change="filterProducts">
                    <option value="">{{ __('pos.status') }} - {{ __('pos.filter') }}</option>
                    <option value="low">{{ __('pos.low_stock') }}</option>
                    <option value="out">{{ __('pos.out_of_stock') }}</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>{{ __('pos.product_name') }}</th>
                        <th>{{ __('pos.barcode') }}</th>
                        <th>{{ __('pos.category') }}</th>
                        <th>{{ __('pos.unit') }}</th>
                        <th>{{ __('pos.selling_price') }}</th>
                        <th>{{ __('pos.cost_price') }}</th>
                        <th>{{ __('pos.current_stock') }}</th>
                        <th>{{ __('pos.status') }}</th>
                        <th>{{ __('pos.actions') }}</th>
                    </tr>
                </thead>
                <tbody id="productsBody">
                    <tr><td colspan="10" class="text-center py-4"><div class="spinner-border"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add/Edit Product Modal --}}
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalTitle">{{ __('pos.add_product') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="productId">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">{{ __('pos.product_name') }} *</label>
                        <input type="text" class="form-control" id="productName" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('pos.selling_price') }} *</label>
                        <input type="number" class="form-control" id="productPrice" step="0.01" min="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('pos.cost_price') }}</label>
                        <input type="number" class="form-control" id="productCostPrice" step="0.01" min="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('pos.current_stock') }}</label>
                        <input type="number" class="form-control" id="productQuantity" min="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('pos.min_stock') }}</label>
                        <input type="number" class="form-control" id="productMinStock" min="0" value="5">
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('pos.barcode') }}</label>
                        <input type="text" class="form-control" id="productBarcode">
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('pos.category') }}</label>
                        <input type="text" class="form-control" id="productCategory">
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('pos.unit') }}</label>
                        <select class="form-select" id="productUnitId">
                            <option value="">{{ __('pos.no_unit') }}</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('pos.suppliers') }}</label>
                        <input type="text" class="form-control" id="productSupplier">
                    </div>
                    <div class="col-12" id="productWarehouseRow">
                        <label class="form-label">{{ __('pos.warehouse') }}</label>
                        <select class="form-select" id="productWarehouseId">
                            <option value="">{{ app()->getLocale() === 'ar' ? 'المخزن الافتراضي' : 'Default warehouse' }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">{{ __('pos.cancel') }}</button>
                <button class="btn btn-primary" data-fn="saveProduct">{{ __('pos.save') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Add Stock Modal --}}
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('pos.add_stock') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="stockProductId">
                <p id="stockProductName" class="fw-semibold mb-3"></p>
                <div class="mb-3">
                    <label class="form-label">{{ __('pos.quantity') }} *</label>
                    <input type="number" class="form-control" id="stockQuantity" min="1" value="1">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('pos.warehouse') }}</label>
                    <select class="form-select" id="stockWarehouseId">
                        <option value="">{{ app()->getLocale() === 'ar' ? 'المخزن الافتراضي' : 'Default warehouse' }}</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('pos.notes') }}</label>
                    <input type="text" class="form-control" id="stockReason">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">{{ __('pos.cancel') }}</button>
                <button class="btn btn-success" data-fn="submitAddStock">{{ __('pos.add_stock') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ─── BARCODE MODAL ──────────────────────────────────────────────────────── --}}
<div class="modal fade" id="barcodeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-barcode me-2"></i>{{ app()->getLocale() === 'ar' ? 'باركود المنتج' : 'Product Barcode' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4" id="barcodeModalBody">
                <p id="barcodeProductName" class="fw-bold mb-1"></p>
                <p id="barcodeProductPrice" class="text-success mb-3"></p>
                <div id="barcodeContainer" class="d-flex justify-content-center mb-2">
                    <svg id="barcodeSvg"></svg>
                </div>
                <p id="barcodeValue" class="text-muted small font-monospace mb-3"></p>
                <div id="barcodeGenerateSection" class="d-none">
                    <p class="text-warning small">{{ app()->getLocale() === 'ar' ? 'لا يوجد باركود، قم بتوليد واحد:' : 'No barcode. Generate one:' }}</p>
                    <button class="btn btn-sm btn-outline-primary" data-fn="generateBarcode">
                        <i class="fas fa-magic me-1"></i>{{ app()->getLocale() === 'ar' ? 'توليد باركود' : 'Generate Barcode' }}
                    </button>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button class="btn btn-success" data-fn="printBarcode">
                    <i class="fas fa-print me-1"></i>{{ app()->getLocale() === 'ar' ? 'طباعة' : 'Print' }}
                </button>
                <button class="btn btn-outline-secondary" data-fn="downloadBarcode">
                    <i class="fas fa-download me-1"></i>{{ app()->getLocale() === 'ar' ? 'تحميل' : 'Download' }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Units Management Modal --}}
<div class="modal fade" id="unitsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-ruler me-2"></i>{{ __('pos.manage_units') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Add / Edit form --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <input type="hidden" id="unitId">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label">{{ __('pos.unit_name') }} *</label>
                                <input type="text" class="form-control" id="unitName" placeholder="{{ __('pos.unit_name') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('pos.unit_abbreviation') }}</label>
                                <input type="text" class="form-control" id="unitAbbreviation" placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: كجم، لتر' : 'e.g. kg, L' }}">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100" data-fn="saveUnit">
                                    <i class="fas fa-save me-1"></i>{{ __('pos.save') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Units list --}}
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>{{ __('pos.unit_name') }}</th>
                                <th>{{ __('pos.unit_abbreviation') }}</th>
                                <th>{{ __('pos.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="unitsBody">
                            <tr><td colspan="4" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></td></tr>
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
let allProducts = [];
let renderedProducts = [];
let warehouseOpts = '';
let allUnits = [];
let unitOpts = '';

async function loadWarehouses() {
    const res = await apiCall('{{ url("/api/warehouses") }}');
    const list = Array.isArray(res) ? res : (res.data ?? []);
    warehouseOpts = list.map(w =>
        `<option value="${w.id}"${w.is_default ? ' selected' : ''}>${w.name}</option>`
    ).join('');
    ['productWarehouseId', 'stockWarehouseId'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            const blank = `<option value="">${LOCALE === 'ar' ? 'المخزن الافتراضي' : 'Default warehouse'}</option>`;
            el.innerHTML = blank + warehouseOpts;
        }
    });
}

async function loadProducts() {
    const res  = await apiCall('{{ route("products.all") }}');
    const rawP = res.products ?? [];
    allProducts = Array.isArray(rawP) ? rawP : (Array.isArray(rawP.data) ? rawP.data : []);
    populateCategoryFilter();
    renderProducts(allProducts);
}

function populateCategoryFilter() {
    const cats = [...new Set(allProducts.map(p => p.category).filter(Boolean))];
    const sel  = document.getElementById('categoryFilter');
    sel.innerHTML = `<option value="">{{ __('pos.category') }}</option>` +
        cats.map(c => `<option value="${c}">${c}</option>`).join('');
}

function filterProducts() {
    const search  = document.getElementById('productSearch').value.toLowerCase();
    const cat     = document.getElementById('categoryFilter').value;
    const stock   = document.getElementById('stockFilter').value;

    const filtered = allProducts.filter(p => {
        const matchSearch = !search || p.name.toLowerCase().includes(search) || (p.barcode || '').includes(search);
        const matchCat    = !cat    || p.category === cat;
        const matchStock  = !stock  || (stock === 'low' && p.low_stock && p.quantity > 0) || (stock === 'out' && p.quantity === 0);
        return matchSearch && matchCat && matchStock;
    });
    renderProducts(filtered);
}

function renderProducts(products) {
    renderedProducts = products;
    document.getElementById('productsBody').innerHTML = products.length
        ? products.map((p, i) => `
            <tr>
                <td>${i+1}</td>
                <td class="fw-semibold">${p.name}</td>
                <td><code>${p.barcode || '-'}</code></td>
                <td>${p.category || '-'}</td>
                <td>${p.unit_abbreviation
                    ? `<span class="badge bg-info text-dark">${p.unit_abbreviation}</span>`
                    : p.unit_name
                    ? `<span class="badge bg-secondary">${p.unit_name}</span>`
                    : '<span class="text-muted">-</span>'}</td>
                <td class="text-success fw-semibold">${formatCurrency(p.price)}</td>
                <td class="text-muted">${formatCurrency(p.cost_price)}</td>
                <td class="fw-bold ${p.quantity === 0 ? 'text-danger' : p.low_stock ? 'text-warning' : 'text-success'}">${p.quantity}</td>
                <td>
                    ${p.quantity === 0
                        ? '<span class="badge bg-danger">{{ __("pos.out_of_stock") }}</span>'
                        : p.low_stock
                        ? '<span class="badge badge-low-stock">{{ __("pos.low_stock") }}</span>'
                        : '<span class="badge badge-in-stock">OK</span>'}
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-warning text-white" title="باركود" data-action="barcode" data-idx="${i}"><i class="fas fa-barcode"></i></button>
                        <button class="btn btn-success" data-action="add-stock" data-idx="${i}"><i class="fas fa-plus"></i></button>
                        <button class="btn btn-primary" data-action="edit" data-idx="${i}"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger" data-action="delete" data-idx="${i}"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>`).join('')
        : '<tr><td colspan="10" class="text-center text-muted py-4">{{ __("pos.no_data") }}</td></tr>';
}

function editProduct(p) {
    document.getElementById('productId').value        = p.id;
    document.getElementById('productName').value      = p.name;
    document.getElementById('productPrice').value     = p.price;
    document.getElementById('productCostPrice').value = p.cost_price;
    document.getElementById('productMinStock').value  = p.min_stock;
    document.getElementById('productBarcode').value   = p.barcode || '';
    document.getElementById('productCategory').value  = p.category || '';
    document.getElementById('productSupplier').value  = p.supplier || '';
    document.getElementById('productUnitId').value    = p.unit_id || '';
    const qtyInput = document.getElementById('productQuantity');
    qtyInput.value    = p.quantity ?? 0;
    qtyInput.disabled = true;
    document.getElementById('productWarehouseRow').style.display = 'none';
    document.getElementById('productModalTitle').textContent = '{{ __("pos.edit_product") }}';
    new bootstrap.Modal(document.getElementById('addProductModal')).show();
}

async function saveProduct() {
    const id          = document.getElementById('productId').value;
    const warehouseId = document.getElementById('productWarehouseId').value;
    const unitVal = document.getElementById('productUnitId').value;
    const data = {
        name:             document.getElementById('productName').value,
        price:            document.getElementById('productPrice').value,
        cost_price:       document.getElementById('productCostPrice').value,
        initial_quantity: document.getElementById('productQuantity').value || 0,
        min_stock:        document.getElementById('productMinStock').value,
        barcode:          document.getElementById('productBarcode').value,
        category:         document.getElementById('productCategory').value,
        supplier:         document.getElementById('productSupplier').value,
        unit_id:          unitVal ? parseInt(unitVal) : null,
    };
    if (!id && warehouseId) data.warehouse_id = parseInt(warehouseId);

    const url    = id ? `/api/products/${id}` : '{{ route("products.store") }}';
    const method = id ? 'PUT' : 'POST';
    const res    = await apiCall(url, method, data);

    if (res.success) {
        showToast('{{ __("pos.success") }}');
        bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
        document.getElementById('productId').value = '';
        document.getElementById('productQuantity').disabled = false;
        loadProducts();
    } else {
        showToast(res.message || '{{ __("pos.error") }}', 'danger');
    }
}

function showAddStock(id, name) {
    document.getElementById('stockProductId').value         = id;
    document.getElementById('stockProductName').textContent = name;
    document.getElementById('stockQuantity').value          = 1;
    document.getElementById('stockReason').value            = '';
    // Pre-select default warehouse
    const sel = document.getElementById('stockWarehouseId');
    sel.innerHTML = `<option value="">${LOCALE === 'ar' ? 'المخزن الافتراضي' : 'Default warehouse'}</option>` + warehouseOpts;
    new bootstrap.Modal(document.getElementById('addStockModal')).show();
}

async function submitAddStock() {
    const id          = document.getElementById('stockProductId').value;
    const warehouseId = document.getElementById('stockWarehouseId').value;
    const payload = {
        quantity: document.getElementById('stockQuantity').value,
        reason:   document.getElementById('stockReason').value,
    };
    if (warehouseId) payload.warehouse_id = parseInt(warehouseId);
    const res = await apiCall(`/api/products/${id}/add-stock`, 'POST', payload);
    if (res.success) {
        showToast('{{ __("pos.stock_added") }}');
        bootstrap.Modal.getInstance(document.getElementById('addStockModal')).hide();
        loadProducts();
    } else {
        showToast(res.message, 'danger');
    }
}

async function deleteProduct(id) {
    if (!confirm('{{ __("pos.confirm_delete") }}')) return;
    const res = await apiCall(`/api/products/${id}`, 'DELETE');
    if (res.success) { showToast('{{ __("pos.success") }}'); loadProducts(); }
    else showToast(res.message, 'danger');
}

// Reset modal on open (only when triggered by the Add button, not editProduct())
document.getElementById('addProductModal').addEventListener('show.bs.modal', function(e) {
    if (!e.relatedTarget) return;
    document.getElementById('productId').value = '';
    document.getElementById('productName').value = '';
    document.getElementById('productUnitId').value = '';
    document.getElementById('productQuantity').disabled = false;
    document.getElementById('productWarehouseRow').style.display = '';
    document.getElementById('productModalTitle').textContent = '{{ __("pos.add_product") }}';
});

// ── Units ─────────────────────────────────────────────────────────────────────

async function loadUnits() {
    const res = await apiCall('{{ route("units.all") }}');
    allUnits  = res.units ?? [];
    unitOpts  = allUnits.map(u =>
        `<option value="${u.id}">${u.name}${u.abbreviation ? ' (' + u.abbreviation + ')' : ''}</option>`
    ).join('');
    document.getElementById('productUnitId').innerHTML =
        `<option value="">{{ __('pos.no_unit') }}</option>` + unitOpts;
    renderUnits();
}

function renderUnits() {
    const tbody = document.getElementById('unitsBody');
    if (!tbody) return;
    tbody.innerHTML = allUnits.length
        ? allUnits.map((u, i) => `
            <tr>
                <td>${i + 1}</td>
                <td class="fw-semibold">${u.name}</td>
                <td><span class="badge bg-secondary">${u.abbreviation || '-'}</span></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-primary" data-unit-action="edit" data-idx="${i}"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger" data-unit-action="delete" data-idx="${i}"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>`).join('')
        : `<tr><td colspan="4" class="text-center text-muted py-3">{{ __('pos.no_data') }}</td></tr>`;
}

async function saveUnit() {
    const id   = document.getElementById('unitId').value;
    const data = {
        name:         document.getElementById('unitName').value.trim(),
        abbreviation: document.getElementById('unitAbbreviation').value.trim(),
    };
    if (!data.name) { showToast('{{ __("pos.unit_name") }} {{ __("pos.required") ?? "required" }}', 'error'); return; }

    const url    = id ? `/api/units/${id}` : '{{ route("units.store") }}';
    const method = id ? 'PUT' : 'POST';
    const res    = await apiCall(url, method, data);

    if (res.success) {
        showToast('{{ __("pos.success") }}');
        document.getElementById('unitId').value = '';
        document.getElementById('unitName').value = '';
        document.getElementById('unitAbbreviation').value = '';
        await loadUnits();
    } else {
        showToast(res.message || '{{ __("pos.error") }}', 'error');
    }
}

async function deleteUnit(id) {
    if (!confirm('{{ __("pos.confirm_delete") }}')) return;
    const res = await apiCall(`/api/units/${id}`, 'DELETE');
    if (res.success) { showToast('{{ __("pos.success") }}'); loadUnits(); }
    else showToast(res.message, 'error');
}

document.getElementById('unitsModal').addEventListener('click', function(e) {
    const btn = e.target.closest('[data-unit-action]');
    if (!btn) return;
    const u = allUnits[parseInt(btn.dataset.idx)];
    if (!u) return;
    if (btn.dataset.unitAction === 'edit') {
        document.getElementById('unitId').value = u.id;
        document.getElementById('unitName').value = u.name;
        document.getElementById('unitAbbreviation').value = u.abbreviation || '';
    } else if (btn.dataset.unitAction === 'delete') {
        deleteUnit(u.id);
    }
});

document.getElementById('unitsModal').addEventListener('show.bs.modal', loadUnits);

// ─────────────────────────────────────────────────────────────────────────────

loadWarehouses();
loadUnits();
loadProducts();

document.getElementById('productsBody').addEventListener('click', function(e) {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const p = renderedProducts[parseInt(btn.dataset.idx)];
    if (!p) return;
    if (btn.dataset.action === 'barcode')    showBarcode(p.id, p.name||'', p.barcode||'', p.price);
    else if (btn.dataset.action === 'add-stock') showAddStock(p.id, p.name||'');
    else if (btn.dataset.action === 'edit')   editProduct(p);
    else if (btn.dataset.action === 'delete') deleteProduct(p.id);
});

// ─── BARCODE GENERATOR ────────────────────────────────────────────────────────
let _barcodeProductId = null;
let _currentBarcodeValue = '';

async function loadJsBarcode() {
    if (window.JsBarcode) return;
    await new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js';
        s.onload = resolve; s.onerror = reject;
        document.head.appendChild(s);
    });
}

async function showBarcode(id, name, barcode, price) {
    _barcodeProductId = id;
    _currentBarcodeValue = barcode;
    document.getElementById('barcodeProductName').textContent = name;
    document.getElementById('barcodeProductPrice').textContent = price ? formatCurrency(price) : '';

    await loadJsBarcode();

    if (barcode) {
        document.getElementById('barcodeGenerateSection').classList.add('d-none');
        renderBarcode(barcode);
    } else {
        document.getElementById('barcodeSvg').innerHTML = '';
        document.getElementById('barcodeValue').textContent = '';
        document.getElementById('barcodeGenerateSection').classList.remove('d-none');
    }

    new bootstrap.Modal(document.getElementById('barcodeModal')).show();
}

function renderBarcode(value) {
    try {
        JsBarcode('#barcodeSvg', value, {
            format: 'CODE128',
            width: 2,
            height: 80,
            displayValue: true,
            fontSize: 14,
            margin: 10,
            background: '#ffffff',
            lineColor: '#000000',
        });
        document.getElementById('barcodeValue').textContent = value;
        _currentBarcodeValue = value;
    } catch(e) {
        document.getElementById('barcodeValue').textContent = '{{ app()->getLocale() === "ar" ? "باركود غير صالح" : "Invalid barcode" }}';
    }
}

async function generateBarcode() {
    // EAN13-style: timestamp-based unique code
    const code = String(Date.now()).slice(-12).padStart(12, '0');
    // Save barcode to product via API
    const res = await apiCall(`/api/products/${_barcodeProductId}`, 'PUT', { barcode: code });
    if (res.success) {
        document.getElementById('barcodeGenerateSection').classList.add('d-none');
        renderBarcode(code);
        loadProducts();
    }
}

function printBarcode() {
    const name = document.getElementById('barcodeProductName').textContent;
    const price = document.getElementById('barcodeProductPrice').textContent;
    const svgEl = document.getElementById('barcodeSvg');
    const svgData = new XMLSerializer().serializeToString(svgEl);
    const svgBase64 = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)));

    const win = window.open('', '_blank', 'width=400,height=300');
    win.document.write(`<!DOCTYPE html><html><head><title>Barcode</title>
    <style>
        body { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100vh; margin:0; font-family:sans-serif; }
        .label { text-align:center; padding:16px; border:1px solid #ddd; border-radius:8px; }
        .prod-name { font-weight:bold; font-size:14px; margin-bottom:4px; }
        .prod-price { color:#16a34a; font-size:13px; margin-bottom:8px; }
    </style></head><body>
    <div class="label">
        <div class="prod-name">${name}</div>
        <div class="prod-price">${price}</div>
        <img src="${svgBase64}" style="max-width:260px">
    </div>
    <script>window.onload=()=>{window.print();window.close();}<\/script>
    </body></html>`);
    win.document.close();
}

function downloadBarcode() {
    const svgEl = document.getElementById('barcodeSvg');
    const svgData = new XMLSerializer().serializeToString(svgEl);
    const blob = new Blob([svgData], { type: 'image/svg+xml' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `barcode-${_currentBarcodeValue || 'product'}.svg`;
    a.click();
}

function formatCurrency(v) {
    return new Intl.NumberFormat('{{ app()->getLocale() }}', { minimumFractionDigits: 2 }).format(v);
}
</script>
@endpush
