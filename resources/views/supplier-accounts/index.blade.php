{{-- FILE: resources/views/supplier-accounts/index.blade.php --}}
@extends('layouts.app')
@section('title', __('pos.supplier_accounts'))
@section('page-title', __('pos.supplier_accounts'))

@section('content')
<div class="row g-3">
    {{-- قائمة الموردين --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list me-2"></i>الموردين</span>
            </div>
            <div class="card-body p-2">
                <input type="text" class="form-control form-control-sm mb-2"
                    id="suppSearch" placeholder="بحث..." data-on-input="filterSuppliersList">
            </div>
            <div class="list-group list-group-flush" id="suppliersList" style="max-height:70vh;overflow-y:auto;">
                <div class="text-center py-4"><div class="spinner-border spinner-border-sm"></div></div>
            </div>
        </div>
    </div>

    {{-- تفاصيل الحساب --}}
    <div class="col-md-8">
        <div id="accountPanel" style="display:none">
            {{-- بيانات المورد --}}
            <div class="card mb-3">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0 fw-bold" id="acctSupplierName">-</h5>
                            <small class="text-muted" id="acctSupplierPhone">-</small>
                        </div>
                        <div class="col-auto d-flex gap-2">
                            <button class="btn btn-success btn-sm" data-fn="exportAccountExcel">
                                <i class="fas fa-file-excel me-1"></i>Excel
                            </button>
                            <button class="btn btn-danger btn-sm" data-fn="printAccountStatement">
                                <i class="fas fa-print me-1"></i>طباعة كشف الحساب
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- بطاقات الملخص --}}
            <div class="row g-3 mb-3">
                <div class="col-4">
                    <div class="stat-card red text-center">
                        <p class="mb-1 small opacity-75">إجمالي المديونية</p>
                        <h5 class="mb-0 fw-bold" id="acctTotalDebt">-</h5>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-card green text-center">
                        <p class="mb-1 small opacity-75">إجمالي المدفوع</p>
                        <h5 class="mb-0 fw-bold" id="acctTotalPaid">-</h5>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-card orange text-center">
                        <p class="mb-1 small opacity-75">المتبقي الآن</p>
                        <h5 class="mb-0 fw-bold" id="acctBalance">-</h5>
                    </div>
                </div>
            </div>

            {{-- جدول الحركات --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list-alt me-2"></i>كشف الحساب التفصيلي</span>
                    <div class="d-flex gap-2">
                        <input type="date" class="form-control form-control-sm" id="acctFrom"
                            data-on-change="filterEntries" style="width:140px">
                        <input type="date" class="form-control form-control-sm" id="acctTo"
                            data-on-change="filterEntries" style="width:140px">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>نوع الحركة</th>
                                    <th>المرجع</th>
                                    <th class="text-danger">مديونية</th>
                                    <th class="text-success">مدفوع</th>
                                    <th>الرصيد</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody id="acctBody">
                                <tr><td colspan="7" class="text-center text-muted py-3">اختر مورداً</td></tr>
                            </tbody>
                            <tfoot id="acctFooter" style="display:none">
                                <tr class="table-dark fw-bold">
                                    <td colspan="3" class="text-end">الإجمالي</td>
                                    <td class="text-danger" id="footerDebt">-</td>
                                    <td class="text-success" id="footerPaid">-</td>
                                    <td id="footerBalance">-</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="noSupplierPanel" class="text-center text-muted py-5">
            <i class="fas fa-balance-scale fa-4x mb-3 d-block opacity-25"></i>
            <p>اختر مورداً من القائمة لعرض حسابه</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script @nonce>
let allSuppliersAcct     = [];
let renderedSuppliersAcct = [];
let currentEntries       = [];
let currentSupplier      = null;

async function loadSuppliersList() {
    const res        = await apiCall('{{ route("suppliers.all") }}?all=1');
    allSuppliersAcct = res.suppliers?.data || res.suppliers || [];
    renderSuppliersList(allSuppliersAcct);

    // فتح مورد من URL لو موجود
    const urlParams = new URLSearchParams(window.location.search);
    const suppId    = urlParams.get('supplier');
    if (suppId) {
        const s = allSuppliersAcct.find(x => x.id == suppId);
        if (s) loadSupplierAccount(s.id, s.name, s.phone);
    }
}

function filterSuppliersList() {
    const q = document.getElementById('suppSearch').value.toLowerCase();
    renderSuppliersList(allSuppliersAcct.filter(s =>
        s.name.toLowerCase().includes(q) || (s.phone||'').includes(q)
    ));
}

function renderSuppliersList(suppliers) {
    renderedSuppliersAcct = suppliers;
    document.getElementById('suppliersList').innerHTML = suppliers.length
        ? suppliers.map((s, i) => `
            <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                id="suppBtn_${s.id}"
                data-action="load-account" data-idx="${i}">
                <div>
                    <div class="fw-semibold">${escapeHtml(s.name)}</div>
                    <small class="text-muted">${s.phone||'لا يوجد تليفون'}</small>
                </div>
                <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-muted"></i>
            </button>`).join('')
        : '<div class="p-3 text-muted text-center">لا يوجد موردين</div>';
}

async function loadSupplierAccount(id, name, phone) {
    currentSupplier = { id, name, phone };

    document.querySelectorAll('#suppliersList button').forEach(b => b.classList.remove('active'));
    const btn = document.getElementById(`suppBtn_${id}`);
    if (btn) btn.classList.add('active');

    document.getElementById('noSupplierPanel').style.display = 'none';
    document.getElementById('accountPanel').style.display    = 'block';
    document.getElementById('acctSupplierName').textContent  = name;
    document.getElementById('acctSupplierPhone').textContent = phone || 'لا يوجد تليفون';
    document.getElementById('acctBody').innerHTML =
        '<tr><td colspan="7" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></td></tr>';

    const res = await apiCall(`{{ url('/api/supplier-accounts') }}/${id}`);

    document.getElementById('acctTotalDebt').textContent = formatCurrency(res.total_debt);
    document.getElementById('acctTotalPaid').textContent = formatCurrency(res.total_payment);
    document.getElementById('acctBalance').textContent   = formatCurrency(res.balance);

    currentEntries = res.entries?.data || res.entries || [];
    filterEntries();
}

function filterEntries() {
    const from    = document.getElementById('acctFrom').value;
    const to      = document.getElementById('acctTo').value;
    let entries   = currentEntries;

    if (from) entries = entries.filter(e => e.created_at >= from);
    if (to)   entries = entries.filter(e => e.created_at <= to + ' 23:59:59');

    const typeLabels = {
        purchase_order: 'أمر شراء',
        payment:        'دفعة',
        adjustment:     'تسوية',
    };

    const totalDebt = entries.reduce((s,e) => s + parseFloat(e.debit||0), 0);
    const totalPaid = entries.reduce((s,e) => s + parseFloat(e.credit||0), 0);
    const netBal    = totalDebt - totalPaid;

    document.getElementById('acctBody').innerHTML = entries.length
        ? entries.map(e => `
            <tr>
                <td class="small">${formatDate(e.created_at)}</td>
                <td><span class="badge ${e.transaction_type==='payment' ? 'bg-success' : 'bg-primary'}">
                    ${typeLabels[e.transaction_type]||e.transaction_type}</span></td>
                <td><code class="small">${e.reference_number||'-'}</code></td>
                <td class="text-danger fw-semibold">${e.debit > 0 ? formatCurrency(e.debit) : '-'}</td>
                <td class="text-success fw-semibold">${e.credit > 0 ? formatCurrency(e.credit) : '-'}</td>
                <td class="fw-bold ${e.balance > 0 ? 'text-warning' : 'text-success'}">${formatCurrency(e.balance)}</td>
                <td class="text-muted small">${e.notes||'-'}</td>
            </tr>`).join('')
        : '<tr><td colspan="7" class="text-center text-muted py-3">لا توجد حركات</td></tr>';

    if (entries.length) {
        document.getElementById('acctFooter').style.display = '';
        document.getElementById('footerDebt').textContent    = formatCurrency(totalDebt);
        document.getElementById('footerPaid').textContent    = formatCurrency(totalPaid);
        document.getElementById('footerBalance').textContent = formatCurrency(netBal);
    } else {
        document.getElementById('acctFooter').style.display = 'none';
    }
}

function printAccountStatement() {
    if (!currentSupplier) return;
    const from    = document.getElementById('acctFrom').value;
    const to      = document.getElementById('acctTo').value;
    let entries   = currentEntries;
    if (from) entries = entries.filter(e => e.created_at >= from);
    if (to)   entries = entries.filter(e => e.created_at <= to + ' 23:59:59');

    const totalDebt = entries.reduce((s,e) => s + parseFloat(e.debit||0), 0);
    const totalPaid = entries.reduce((s,e) => s + parseFloat(e.credit||0), 0);
    const balance   = document.getElementById('acctBalance').textContent;

    const typeLabels = { purchase_order:'أمر شراء', payment:'دفعة', adjustment:'تسوية' };
    const rows = entries.map(e => `<tr>
        <td>${formatDate(e.created_at)}</td>
        <td>${typeLabels[e.transaction_type]||e.transaction_type}</td>
        <td>${e.reference_number||'-'}</td>
        <td class="text-danger">${e.debit>0 ? formatCurrency(e.debit) : '-'}</td>
        <td class="text-success">${e.credit>0 ? formatCurrency(e.credit) : '-'}</td>
        <td class="fw-bold">${formatCurrency(e.balance)}</td>
        <td>${e.notes||'-'}</td>
    </tr>`).join('');

    const w = window.open('','_blank');
    w.document.write(`<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="utf-8">
        <title>كشف حساب — ${currentSupplier.name}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
        <style>body{padding:20px;font-family:'Cairo',sans-serif;font-size:13px;}</style>
        </head><body>
        <h4 class="text-center mb-1">كشف حساب مورد</h4>
        <h5 class="text-center text-primary mb-1">${escapeHtml(currentSupplier.name)}</h5>
        <p class="text-center text-muted mb-1">تليفون: ${currentSupplier.phone||'-'}</p>
        <p class="text-center text-muted mb-3">
            ${from ? 'من ' + from : 'من البداية'} ${to ? 'إلى ' + to : 'حتى الآن'}
        </p>
        <div class="row g-2 mb-3 text-center">
            <div class="col-4"><div class="border rounded p-2">
                <small class="text-muted d-block">إجمالي المديونية</small>
                <strong class="text-danger">${formatCurrency(totalDebt)}</strong>
            </div></div>
            <div class="col-4"><div class="border rounded p-2">
                <small class="text-muted d-block">إجمالي المدفوع</small>
                <strong class="text-success">${formatCurrency(totalPaid)}</strong>
            </div></div>
            <div class="col-4"><div class="border rounded p-2 bg-warning-subtle">
                <small class="text-muted d-block">المتبقي الحالي</small>
                <strong class="fs-5">${balance}</strong>
            </div></div>
        </div>
        <table class="table table-bordered table-sm">
            <thead class="table-dark"><tr><th>التاريخ</th><th>النوع</th><th>المرجع</th>
            <th>مديونية</th><th>مدفوع</th><th>الرصيد</th><th>ملاحظات</th></tr></thead>
            <tbody>${rows}</tbody>
            <tfoot class="table-secondary fw-bold">
                <tr><td colspan="3" class="text-end">الإجمالي</td>
                    <td class="text-danger">${formatCurrency(totalDebt)}</td>
                    <td class="text-success">${formatCurrency(totalPaid)}</td>
                    <td>${formatCurrency(totalDebt - totalPaid)}</td><td></td></tr>
            </tfoot>
        </table>
        <div class="row mt-5 text-center">
            <div class="col-6"><p class="mb-0 small">توقيع المورد</p><p class="mt-4 border-top pt-2">${escapeHtml(currentSupplier.name)}</p></div>
            <div class="col-6"><p class="mb-0 small">توقيع المسؤول</p><p class="mt-4 border-top pt-2">___________________</p></div>
        </div>
        <p class="text-center text-muted small mt-3">تاريخ الطباعة: ${new Date().toLocaleString('ar-EG')}</p>
        </body></html>`);
    w.document.close(); w.focus(); w.print(); w.onafterprint=()=>w.close();
}

function exportAccountExcel() {
    if (!currentSupplier) return;
    const from  = document.getElementById('acctFrom').value;
    const to    = document.getElementById('acctTo').value;
    let entries = currentEntries;
    if (from) entries = entries.filter(e => e.created_at >= from);
    if (to)   entries = entries.filter(e => e.created_at <= to + ' 23:59:59');
    const typeLabels = { purchase_order:'أمر شراء', payment:'دفعة', adjustment:'تسوية' };
    const rows = [['التاريخ','النوع','المرجع','مديونية','مدفوع','الرصيد','ملاحظات']];
    entries.forEach(e => rows.push([
        e.created_at, typeLabels[e.transaction_type]||e.transaction_type,
        e.reference_number||'', e.debit||0, e.credit||0, e.balance||0, e.notes||''
    ]));
    const csv = '\uFEFF' + rows.map(r => r.map(v=>`"${v}"`).join(',')).join('\n');
    const a   = document.createElement('a');
    a.href    = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    a.download= `account_${currentSupplier.name}_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
}

function escapeHtml(str) {
    if (str == null) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

document.getElementById('suppliersList').addEventListener('click', function(e) {
    const btn = e.target.closest('[data-action="load-account"]');
    if (!btn) return;
    const s = renderedSuppliersAcct[parseInt(btn.dataset.idx)];
    if (s) loadSupplierAccount(s.id, s.name, s.phone||'');
});

loadSuppliersList();
</script>
@endpush
