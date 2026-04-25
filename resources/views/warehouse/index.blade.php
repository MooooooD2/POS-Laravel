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
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus me-1"></i>{{ __('pos.add_product') }}
        </button>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" id="productSearch"
                    placeholder="{{ __('pos.search') }}..." oninput="filterProducts()">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="categoryFilter" onchange="filterProducts()">
                    <option value="">{{ __('pos.category') }} - {{ __('pos.filter') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="stockFilter" onchange="filterProducts()">
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
                        <th>{{ __('pos.selling_price') }}</th>
                        <th>{{ __('pos.cost_price') }}</th>
                        <th>{{ __('pos.current_stock') }}</th>
                        <th>{{ __('pos.status') }}</th>
                        <th>{{ __('pos.actions') }}</th>
                    </tr>
                </thead>
                <tbody id="productsBody">
                    <tr><td colspan="9" class="text-center py-4"><div class="spinner-border"></div></td></tr>
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
                    <div class="col-12">
                        <label class="form-label">{{ __('pos.suppliers') }}</label>
                        <input type="text" class="form-control" id="productSupplier">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">{{ __('pos.cancel') }}</button>
                <button class="btn btn-primary" onclick="saveProduct()">{{ __('pos.save') }}</button>
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
                    <label class="form-label">{{ __('pos.notes') }}</label>
                    <input type="text" class="form-control" id="stockReason">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">{{ __('pos.cancel') }}</button>
                <button class="btn btn-success" onclick="submitAddStock()">{{ __('pos.add_stock') }}</button>
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
                    <button class="btn btn-sm btn-outline-primary" onclick="generateBarcode()">
                        <i class="fas fa-magic me-1"></i>{{ app()->getLocale() === 'ar' ? 'توليد باركود' : 'Generate Barcode' }}
                    </button>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button class="btn btn-success" onclick="printBarcode()">
                    <i class="fas fa-print me-1"></i>{{ app()->getLocale() === 'ar' ? 'طباعة' : 'Print' }}
                </button>
                <button class="btn btn-outline-secondary" onclick="downloadBarcode()">
                    <i class="fas fa-download me-1"></i>{{ app()->getLocale() === 'ar' ? 'تحميل' : 'Download' }}
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let allProducts = [];

async function loadProducts() {
    const res = await apiCall('{{ route("products.all") }}');
    allProducts = res.products || [];
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
    const tbody = document.getElementById('productsBody');
    
    if (!products.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">{{ __("pos.no_data") }}</td></tr>';
        return;
    }
    
    tbody.innerHTML = products.map((p, i) => {
        // Escape product name for JavaScript
        const escapedName = p.name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
        
        return `
            <tr>
                <td>${i+1}</td>
                <td class="fw-semibold">${escapeHtml(p.name)}</td>
                <td><code>${escapeHtml(p.barcode || '-')}</code></td>
                <td>${escapeHtml(p.category || '-')}</td>
                <td class="text-success fw-semibold">${formatCurrency(p.price)}</td>
                <td class="text-muted">${formatCurrency(p.cost_price || 0)}</td>
                <td class="fw-bold ${p.quantity === 0 ? 'text-danger' : p.low_stock ? 'text-warning' : 'text-success'}">${p.quantity}</td>
                <td>
                    ${p.quantity === 0
                        ? '<span class="badge bg-danger">{{ __("pos.out_of_stock") }}</span>'
                        : p.low_stock
                        ? '<span class="badge bg-warning text-dark">{{ __("pos.low_stock") }}</span>'
                        : '<span class="badge bg-success">OK</span>'}
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-warning text-white" title="{{ __('pos.barcode') }}" 
                            onclick="showBarcode(${p.id}, '${escapedName.replace(/'/g, "\\'")}', '${(p.barcode || '').replace(/'/g, "\\'")}', ${p.price})">
                            <i class="fas fa-barcode"></i>
                        </button>
                        <button class="btn btn-success" onclick="showAddStock(${p.id}, '${escapedName.replace(/'/g, "\\'")}')">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button class="btn btn-primary" onclick="editProduct(${p.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger" onclick="deleteProduct(${p.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}
// Helper function to escape HTML
function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
async function editProduct(productId) {
    // Find the product from allProducts array
    const product = allProducts.find(p => p.id === productId);
    if (!product) {
        showToast('{{ __("pos.product_not_found") }}', 'danger');
        return;
    }
    
    // Populate the form with product data
    document.getElementById('productId').value = product.id;
    document.getElementById('productName').value = product.name;
    document.getElementById('productPrice').value = product.price;
    document.getElementById('productCostPrice').value = product.cost_price || 0;
    document.getElementById('productMinStock').value = product.min_stock || 5;
    document.getElementById('productBarcode').value = product.barcode || '';
    document.getElementById('productCategory').value = product.category || '';
    document.getElementById('productSupplier').value = product.supplier || '';
    document.getElementById('productQuantity').disabled = true;
    document.getElementById('productQuantity').value = product.quantity;
    document.getElementById('productModalTitle').textContent = '{{ __("pos.edit_product") }}';
    
    // Show the modal
    new bootstrap.Modal(document.getElementById('addProductModal')).show();
}
async function saveProduct() {
    const id   = document.getElementById('productId').value;
    const data = {
        name:       document.getElementById('productName').value,
        price:      document.getElementById('productPrice').value,
        cost_price: document.getElementById('productCostPrice').value,
        quantity:   document.getElementById('productQuantity').value || 0,
        min_stock:  document.getElementById('productMinStock').value,
        barcode:    document.getElementById('productBarcode').value,
        category:   document.getElementById('productCategory').value,
        supplier:   document.getElementById('productSupplier').value,
    };

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
    document.getElementById('stockProductId').value   = id;
    document.getElementById('stockProductName').textContent = name;
    document.getElementById('stockQuantity').value    = 1;
    document.getElementById('stockReason').value      = '';
    new bootstrap.Modal(document.getElementById('addStockModal')).show();
}

async function submitAddStock() {
    const id  = document.getElementById('stockProductId').value;
    const res = await apiCall(`/api/products/${id}/add-stock`, 'POST', {
        quantity: document.getElementById('stockQuantity').value,
        reason:   document.getElementById('stockReason').value,
    });
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

// Reset modal on open
document.getElementById('addProductModal').addEventListener('show.bs.modal', function(e) {
    if (!e.relatedTarget) return;
    document.getElementById('productId').value = '';
    document.getElementById('productName').value = '';
    document.getElementById('productQuantity').disabled = false;
    document.getElementById('productModalTitle').textContent = '{{ __("pos.add_product") }}';
});

loadProducts();

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
