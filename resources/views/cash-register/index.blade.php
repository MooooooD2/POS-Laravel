@extends('layouts.app')
@section('title', 'تسوية الخزينة')
@section('page-title', 'تسوية الخزينة')

@section('content')

{{-- الجلسة المفتوحة --}}
<div id="openSessionPanel" style="display:none">
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card green text-center">
                <p class="mb-1 small opacity-75">مبيعات نقدي</p>
                <h4 class="mb-0 fw-bold" id="liveCashSales">-</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card blue text-center">
                <p class="mb-1 small opacity-75">مبيعات كارت/تحويل</p>
                <h4 class="mb-0 fw-bold" id="liveOtherSales">-</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card orange text-center">
                <p class="mb-1 small opacity-75">إجمالي المبيعات</p>
                <h4 class="mb-0 fw-bold" id="liveTotalSales">-</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card red text-center">
                <p class="mb-1 small opacity-75">المرتجعات</p>
                <h4 class="mb-0 fw-bold" id="liveReturns">-</h4>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-cash-register me-2 text-success"></i>الجلسة الحالية</span>
            <span class="badge bg-success fs-6" id="sessionNumber">-</span>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <table class="table table-bordered table-sm">
                        <tr><th class="bg-light" width="45%">الكاشير</th><td id="sessName">-</td></tr>
                        <tr><th class="bg-light">وقت الفتح</th><td id="sessOpened">-</td></tr>
                        <tr><th class="bg-light">رصيد الفتح</th><td id="sessOpening" class="fw-bold">-</td></tr>
                        <tr><th class="bg-light">عدد الفواتير</th><td id="sessInvoices">-</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-info">
                        <strong>المتوقع في الدرج:</strong><br>
                        رصيد الفتح + مبيعات نقدي - مرتجعات نقدي<br>
                        <span class="fs-5 fw-bold" id="expectedCash">-</span>
                    </div>
                </div>
            </div>

            {{-- تسوية الإغلاق --}}
            <div class="border rounded p-3 bg-light">
                <h6 class="fw-bold mb-3"><i class="fas fa-lock me-2"></i>إغلاق وتسوية الخزينة</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">المبلغ الفعلي في الدرج *</label>
                        <input type="number" class="form-control form-control-lg" id="actualCash"
                            step="0.01" min="0" placeholder="0.00" oninput="calcDifference()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">الفرق</label>
                        <div class="form-control form-control-lg fw-bold text-center" id="diffDisplay"
                            style="background:#f8f9fa;">-</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">ملاحظات</label>
                        <input type="text" class="form-control" id="closeNotes"
                            placeholder="سبب الفرق إن وجد...">
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-warning" onclick="printShiftReport()">
                        <i class="fas fa-print me-1"></i>طباعة تقرير الوردية
                    </button>
                    <button class="btn btn-danger" onclick="closeSession()">
                        <i class="fas fa-lock me-1"></i>إغلاق الخزينة
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- فتح جلسة جديدة --}}
<div id="noSessionPanel" style="display:none">
    <div class="text-center py-5">
        <i class="fas fa-cash-register fa-4x mb-3 d-block text-muted opacity-50"></i>
        <h5>لا توجد جلسة مفتوحة</h5>
        <p class="text-muted">افتح الخزينة لبدء يوم العمل</p>
        <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#openModal">
            <i class="fas fa-play me-2"></i>فتح الخزينة
        </button>
    </div>
</div>

{{-- سجل الجلسات --}}
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-history me-2"></i>سجل الجلسات السابقة</span>
        <button class="btn btn-danger btn-sm" onclick="printHistory()">
            <i class="fas fa-print me-1"></i>طباعة
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>رقم الجلسة</th><th>الكاشير</th><th>فتح</th><th>إغلاق</th>
                        <th>مبيعات</th><th>نقدي متوقع</th><th>نقدي فعلي</th>
                        <th>الفرق</th><th>الحالة</th><th></th>
                    </tr>
                </thead>
                <tbody id="historyBody">
                    <tr><td colspan="10" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal: فتح الخزينة --}}
<div class="modal fade" id="openModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-play me-2"></i>فتح الخزينة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">رصيد بداية الخزينة (نقدي موجود في الدرج) *</label>
                    <input type="number" class="form-control form-control-lg" id="openingAmount"
                        step="0.01" min="0" placeholder="0.00">
                    <div class="form-text">المبلغ الموجود في درج الكاشير قبل بدء البيع</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">ملاحظات</label>
                    <input type="text" class="form-control" id="openNotes">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button class="btn btn-success" onclick="openSession()">
                    <i class="fas fa-play me-1"></i>بدء الجلسة
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentSession = null;
let liveStats      = null;

async function loadCurrentSession() {
    const res = await apiCall('{{ route("cash-session.current") }}');
    if (res.session) {
        currentSession = res.session;
        liveStats      = res.session;
        showOpenPanel();
    } else {
        showNoSessionPanel();
    }
    loadHistory();
}

function showOpenPanel() {
    document.getElementById('openSessionPanel').style.display = 'block';
    document.getElementById('noSessionPanel').style.display   = 'none';
    const s = currentSession;
    document.getElementById('sessionNumber').textContent  = s.session_number;
    document.getElementById('sessName').textContent       = s.cashier_name;
    document.getElementById('sessOpened').textContent     = formatDate(s.opened_at);
    document.getElementById('sessOpening').textContent    = formatCurrency(s.opening_amount);
    document.getElementById('sessInvoices').textContent   = s.invoices_count || 0;
    document.getElementById('liveCashSales').textContent  = formatCurrency(s.cash_sales || 0);
    document.getElementById('liveOtherSales').textContent = formatCurrency((s.card_sales||0) + (s.transfer_sales||0));
    document.getElementById('liveTotalSales').textContent = formatCurrency(s.total_sales || 0);
    document.getElementById('liveReturns').textContent    = formatCurrency(s.total_returns || 0);

    const expected = (s.opening_amount||0) + (s.cash_sales||0) - (s.cash_returns||0);
    document.getElementById('expectedCash').textContent = formatCurrency(expected);
    calcDifference();
}

function showNoSessionPanel() {
    document.getElementById('openSessionPanel').style.display = 'none';
    document.getElementById('noSessionPanel').style.display   = 'block';
}

function calcDifference() {
    if (!currentSession) return;
    const s        = currentSession;
    const expected = (s.opening_amount||0) + (s.cash_sales||0) - (s.cash_returns||0);
    const actual   = parseFloat(document.getElementById('actualCash').value) || 0;
    const diff     = actual - expected;
    const el       = document.getElementById('diffDisplay');
    if (!document.getElementById('actualCash').value) { el.textContent = '-'; el.style.color = ''; return; }
    el.textContent  = (diff >= 0 ? '+' : '') + formatCurrency(diff);
    el.style.color  = diff > 5 ? '#198754' : diff < -5 ? '#dc3545' : '#856404';
}

async function openSession() {
    const amount = document.getElementById('openingAmount').value;
    if (!amount) { showToast('أدخل رصيد البداية', 'danger'); return; }
    const res = await apiCall('{{ route("cash-session.open") }}', 'POST', {
        opening_amount: amount,
        notes: document.getElementById('openNotes').value,
    });
    if (res.success) {
        showToast('تم فتح الخزينة');
        bootstrap.Modal.getInstance(document.getElementById('openModal')).hide();
        loadCurrentSession();
    } else showToast(res.message || 'خطأ', 'danger');
}

async function closeSession() {
    const actual = document.getElementById('actualCash').value;
    if (!actual) { showToast('أدخل المبلغ الفعلي في الدرج', 'danger'); return; }
    if (!confirm('تأكيد إغلاق الخزينة؟')) return;
    const res = await apiCall(`/api/cash-session/${currentSession.id}/close`, 'POST', {
        actual_cash: actual,
        notes: document.getElementById('closeNotes').value,
    });
    if (res.success) {
        showToast('تم إغلاق الخزينة بنجاح');
        printShiftReport(res.session);
        loadCurrentSession();
    } else showToast(res.message || 'خطأ', 'danger');
}

async function loadHistory() {
    const res      = await apiCall('{{ route("cash-session.history") }}');
    const sessions = res.sessions?.data || [];
    document.getElementById('historyBody').innerHTML = sessions.length
        ? sessions.map(s => {
            const diff   = s.difference;
            const diffHtml = diff !== null
                ? `<span class="fw-bold ${diff > 5 ? 'text-success' : diff < -5 ? 'text-danger' : 'text-warning'}">
                    ${diff >= 0 ? '+' : ''}${formatCurrency(diff)}</span>`
                : '-';
            return `<tr>
                <td><code>${s.session_number}</code></td>
                <td>${s.cashier_name}</td>
                <td class="small">${formatDate(s.opened_at)}</td>
                <td class="small">${s.closed_at ? formatDate(s.closed_at) : '-'}</td>
                <td class="text-success fw-semibold">${formatCurrency(s.total_sales)}</td>
                <td>${s.expected_cash !== null ? formatCurrency(s.expected_cash) : '-'}</td>
                <td>${s.actual_cash   !== null ? formatCurrency(s.actual_cash)   : '-'}</td>
                <td>${diffHtml}</td>
                <td><span class="badge ${s.status==='open' ? 'bg-success' : 'bg-secondary'}">
                    ${s.status==='open' ? 'مفتوحة' : 'مغلقة'}</span></td>
                <td><button class="btn btn-sm btn-outline-primary" onclick="printSessionReport(${JSON.stringify(s).replace(/"/g,'&quot;')})">
                    <i class="fas fa-print"></i></button></td>
            </tr>`;
        }).join('')
        : '<tr><td colspan="10" class="text-center text-muted py-3">لا توجد جلسات سابقة</td></tr>';
}

function printShiftReport(session) {
    const s = session || currentSession;
    if (!s) return;
    const expected = (s.opening_amount||0) + (s.cash_sales||s.expected_cash||0) - (s.cash_returns||s.total_returns||0);
    const actual   = s.actual_cash ?? parseFloat(document.getElementById('actualCash')?.value || 0);
    const diff     = actual - expected;
    const w = window.open('','_blank');
    w.document.write(`<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="utf-8">
        <title>تقرير وردية — ${s.session_number}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
        <style>body{padding:20px;font-family:'Cairo',sans-serif;font-size:13px;max-width:500px;margin:auto;}</style>
        </head><body>
        <h4 class="text-center fw-bold mb-1">تقرير إغلاق الخزينة</h4>
        <p class="text-center text-muted mb-3">${s.session_number} — ${new Date().toLocaleString('ar-EG')}</p>
        <table class="table table-bordered table-sm">
            <tr><th class="bg-light" width="55%">الكاشير</th><td>${s.cashier_name}</td></tr>
            <tr><th class="bg-light">وقت الفتح</th><td>${formatDate(s.opened_at)}</td></tr>
            <tr><th class="bg-light">رصيد بداية الوردية</th><td class="fw-bold">${formatCurrency(s.opening_amount||0)}</td></tr>
            <tr class="table-success"><th>مبيعات نقدي</th><td class="fw-bold">${formatCurrency(s.cash_sales||0)}</td></tr>
            <tr class="table-primary"><th>مبيعات كارت/تحويل</th><td class="fw-bold">${formatCurrency((s.card_sales||0)+(s.transfer_sales||0))}</td></tr>
            <tr class="table-warning"><th>إجمالي المبيعات</th><td class="fw-bold fs-6">${formatCurrency(s.total_sales||0)}</td></tr>
            <tr class="table-danger"><th>المرتجعات</th><td>${formatCurrency(s.total_returns||0)}</td></tr>
            <tr><th>عدد الفواتير</th><td>${s.invoices_count||0}</td></tr>
            <tr class="table-info"><th>المتوقع في الدرج</th><td class="fw-bold">${formatCurrency(expected)}</td></tr>
            <tr class="table-warning"><th>الموجود فعلاً</th><td class="fw-bold fs-5">${formatCurrency(actual)}</td></tr>
            <tr class="${diff > 5 ? 'table-success' : diff < -5 ? 'table-danger' : 'table-warning'}">
                <th>الفرق</th><td class="fw-bold fs-5">${diff >= 0 ? '+' : ''}${formatCurrency(diff)}</td></tr>
        </table>
        <div class="row mt-4 text-center">
            <div class="col-6"><p class="mb-5 small">توقيع الكاشير</p><p class="border-top pt-2">${s.cashier_name}</p></div>
            <div class="col-6"><p class="mb-5 small">توقيع المدير</p><p class="border-top pt-2">___________</p></div>
        </div>
        </body></html>`);
    w.document.close(); w.focus(); w.print(); w.onafterprint = () => w.close();
}

function printSessionReport(s) { printShiftReport(s); }

function printHistory() {
    // طباعة السجل من الـ DOM الحالي
    window.print();
}

loadCurrentSession();
// تحديث كل دقيقة
setInterval(loadCurrentSession, 60000);
</script>
@endpush
