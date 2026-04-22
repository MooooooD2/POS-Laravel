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
    document.getElementById('productsBody').innerHTML = products.length
        ? products.map((p, i) => `
            <tr>
                <td>${i+1}</td>
                <td class="fw-semibold">${p.name}</td>
                <td><code>${p.barcode || '-'}</code></td>
                <td>${p.category || '-'}</td>
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
                        <button class="btn btn-success" onclick="showAddStock(${p.id},'${p.name}')"><i class="fas fa-plus"></i></button>
                        <button class="btn btn-primary" onclick="editProduct(${JSON.stringify(p).replace(/"/g,'&quot;')})"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger" onclick="deleteProduct(${p.id})"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>`).join('')
        : '<tr><td colspan="9" class="text-center text-muted py-4">{{ __("pos.no_data") }}</td></tr>';
}

function editProduct(p) {
    document.getElementById('productId').value       = p.id;
    document.getElementById('productName').value     = p.name;
    document.getElementById('productPrice').value    = p.price;
    document.getElementById('productCostPrice').value= p.cost_price;
    document.getElementById('productMinStock').value = p.min_stock;
    document.getElementById('productBarcode').value  = p.barcode || '';
    document.getElementById('productCategory').value = p.category || '';
    document.getElementById('productSupplier').value = p.supplier || '';
    document.getElementById('productQuantity').disabled = true;
    document.getElementById('productModalTitle').textContent = '{{ __("pos.edit_product") }}';
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
</script>
@endpush
