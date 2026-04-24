{{-- FILE: resources/views/settings/index.blade.php --}}
@extends('layouts.app')
@section('title', __('pos.settings'))
@section('page-title', __('pos.settings'))

@push('styles')
<style>
    .settings-nav .nav-link { color: #64748b; border-radius: 0.5rem; margin-bottom: 0.25rem; }
    .settings-nav .nav-link.active { background: #3b82f6; color: #fff; }
    .settings-nav .nav-link i { width: 20px; }
    .setting-card { border: 1px solid #e2e8f0; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem; }
    .setting-card-header { background: #f8fafc; padding: 0.75rem 1rem; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
    .setting-row { padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .setting-row:last-child { border-bottom: none; }
    .setting-label { flex: 1; }
    .setting-label .label-text { font-weight: 500; font-size: 0.9rem; }
    .setting-label .label-desc { font-size: 0.78rem; color: #94a3b8; }
    .setting-control { flex: 0 0 220px; }
    .form-switch .form-check-input { width: 2.5em; height: 1.3em; cursor: pointer; }
    .save-btn-wrapper { position: sticky; bottom: 1rem; z-index: 99; }
    @media (max-width: 768px) {
        .setting-row { flex-direction: column; align-items: flex-start; }
        .setting-control { flex: 1; width: 100%; }
    }
</style>
@endpush

@section('content')
<div class="row g-4">
    {{-- Sidebar Nav --}}
    <div class="col-md-3">
        <div class="card">
            <div class="card-body p-2">
                <nav class="settings-nav nav flex-column">
                    <button class="nav-link active text-start" data-tab="general" onclick="showTab('general', this)">
                        <i class="fas fa-store me-2"></i>{{ __('pos.general_settings') }}
                    </button>
                    <button class="nav-link text-start" data-tab="tax" onclick="showTab('tax', this)">
                        <i class="fas fa-percent me-2"></i>{{ __('pos.tax_settings') }}
                    </button>
                    <button class="nav-link text-start" data-tab="invoice" onclick="showTab('invoice', this)">
                        <i class="fas fa-file-invoice me-2"></i>{{ __('pos.invoice_settings') }}
                    </button>
                    <button class="nav-link text-start" data-tab="pos" onclick="showTab('pos', this)">
                        <i class="fas fa-cash-register me-2"></i>{{ __('pos.pos_settings') }}
                    </button>
                </nav>
            </div>
        </div>
    </div>

    {{-- Settings Panels --}}
    <div class="col-md-9">

        {{-- General Settings --}}
        <div id="tab-general" class="settings-tab">
            <div class="setting-card">
                <div class="setting-card-header">
                    <i class="fas fa-store me-2 text-primary"></i>{{ __('pos.general_settings') }}
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.store_name') }}</div>
                        <div class="label-desc">اسم المتجر الذي يظهر في الفاتورة</div>
                    </div>
                    <div class="setting-control">
                        <input type="text" class="form-control" data-key="store_name" id="s_store_name">
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.store_address') }}</div>
                        <div class="label-desc">عنوان المتجر في الفاتورة</div>
                    </div>
                    <div class="setting-control">
                        <input type="text" class="form-control" data-key="store_address" id="s_store_address">
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.store_phone') }}</div>
                        <div class="label-desc">رقم هاتف المتجر</div>
                    </div>
                    <div class="setting-control">
                        <input type="text" class="form-control" data-key="store_phone" id="s_store_phone">
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.store_email') }}</div>
                        <div class="label-desc">البريد الإلكتروني للمتجر</div>
                    </div>
                    <div class="setting-control">
                        <input type="email" class="form-control" data-key="store_email" id="s_store_email">
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.currency') }}</div>
                        <div class="label-desc">رمز العملة المستخدمة (EGP, USD, SAR...)</div>
                    </div>
                    <div class="setting-control">
                        <input type="text" class="form-control" data-key="currency" id="s_currency" maxlength="5">
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.currency_symbol') }}</div>
                        <div class="label-desc">رمز العملة المعروض (ج.م، $، ريال...)</div>
                    </div>
                    <div class="setting-control">
                        <input type="text" class="form-control" data-key="currency_symbol" id="s_currency_symbol" maxlength="10">
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.default_language') }}</div>
                        <div class="label-desc">اللغة الافتراضية عند تسجيل الدخول</div>
                    </div>
                    <div class="setting-control">
                        <select class="form-select" data-key="default_language" id="s_default_language">
                            <option value="ar">العربية</option>
                            <option value="en">English</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tax Settings --}}
        <div id="tab-tax" class="settings-tab d-none">
            <div class="setting-card">
                <div class="setting-card-header">
                    <i class="fas fa-percent me-2 text-warning"></i>{{ __('pos.tax_settings') }}
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.tax_enabled') }}</div>
                        <div class="label-desc">تفعيل حساب الضريبة على الفواتير</div>
                    </div>
                    <div class="setting-control">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" data-key="tax_enabled" id="s_tax_enabled"
                                onchange="toggleTaxFields()">
                        </div>
                    </div>
                </div>

                <div id="taxFields">
                    <div class="setting-row">
                        <div class="setting-label">
                            <div class="label-text">{{ __('pos.tax_rate') }}</div>
                            <div class="label-desc">نسبة الضريبة المضافة على الفاتورة (مثال: 14 للـ VAT)</div>
                        </div>
                        <div class="setting-control">
                            <div class="input-group">
                                <input type="number" class="form-control" data-key="tax_rate" id="s_tax_rate"
                                    min="0" max="100" step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <div class="label-text">{{ __('pos.tax_name') }} (عربي)</div>
                            <div class="label-desc">اسم الضريبة يظهر في الفاتورة بالعربية</div>
                        </div>
                        <div class="setting-control">
                            <input type="text" class="form-control" data-key="tax_name_ar" id="s_tax_name_ar">
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <div class="label-text">{{ __('pos.tax_name') }} (English)</div>
                            <div class="label-desc">Tax name shown on invoice in English</div>
                        </div>
                        <div class="setting-control">
                            <input type="text" class="form-control" data-key="tax_name_en" id="s_tax_name_en">
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <div class="label-text">{{ __('pos.tax_inclusive') }}</div>
                            <div class="label-desc">إذا فُعِّل، فالسعر يشمل الضريبة. إذا أُوقف، تُضاف الضريبة على السعر</div>
                        </div>
                        <div class="setting-control">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" data-key="tax_inclusive" id="s_tax_inclusive">
                            </div>
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <div class="label-text">{{ __('pos.tax_number') }}</div>
                            <div class="label-desc">الرقم الضريبي للمتجر</div>
                        </div>
                        <div class="setting-control">
                            <input type="text" class="form-control" data-key="tax_number" id="s_tax_number">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tax Preview --}}
            <div class="card border-info" id="taxPreview" style="display:none">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-eye me-2"></i>معاينة حساب الضريبة / Tax Calculation Preview
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted small">سعر المنتج / Product Price</label>
                            <input type="number" class="form-control" id="previewPrice" value="100" oninput="updateTaxPreview()">
                        </div>
                        <div class="col-md-8">
                            <div class="bg-light rounded p-3 mt-3" id="taxPreviewResult">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="text-muted small">قبل الضريبة</div>
                                        <div class="fw-bold" id="previewBefore">-</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-muted small">الضريبة</div>
                                        <div class="fw-bold text-warning" id="previewTax">-</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-muted small">الإجمالي</div>
                                        <div class="fw-bold text-success" id="previewTotal">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Invoice Settings --}}
        <div id="tab-invoice" class="settings-tab d-none">
            <div class="setting-card">
                <div class="setting-card-header">
                    <i class="fas fa-file-invoice me-2 text-success"></i>{{ __('pos.invoice_settings') }}
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.invoice_prefix') }}</div>
                        <div class="label-desc">بادئة رقم الفاتورة (مثال: INV سيعطي INV-20240101-0001)</div>
                    </div>
                    <div class="setting-control">
                        <input type="text" class="form-control" data-key="invoice_prefix" id="s_invoice_prefix" maxlength="10">
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.invoice_footer') }}</div>
                        <div class="label-desc">نص يظهر في أسفل الفاتورة المطبوعة</div>
                    </div>
                    <div class="setting-control">
                        <textarea class="form-control" data-key="invoice_footer" id="s_invoice_footer" rows="2"></textarea>
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.show_tax_invoice') }}</div>
                        <div class="label-desc">إظهار سطر الضريبة في الفاتورة المطبوعة</div>
                    </div>
                    <div class="setting-control">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" data-key="show_tax_invoice" id="s_show_tax_invoice">
                        </div>
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.auto_print') }}</div>
                        <div class="label-desc">طباعة الفاتورة تلقائياً بعد إتمام البيع</div>
                    </div>
                    <div class="setting-control">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" data-key="auto_print" id="s_auto_print">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- POS Settings --}}
        <div id="tab-pos" class="settings-tab d-none">
            <div class="setting-card">
                <div class="setting-card-header">
                    <i class="fas fa-cash-register me-2 text-danger"></i>{{ __('pos.pos_settings') }}
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.default_payment') }}</div>
                        <div class="label-desc">طريقة الدفع المحددة افتراضياً عند فتح نقطة البيع</div>
                    </div>
                    <div class="setting-control">
                        <select class="form-select" data-key="default_payment" id="s_default_payment">
                            <option value="cash">{{ __('pos.cash') }}</option>
                            <option value="card">{{ __('pos.card') }}</option>
                            <option value="transfer">{{ __('pos.transfer') }}</option>
                        </select>
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.pos_sound') }}</div>
                        <div class="label-desc">صوت تنبيه عند مسح الباركود بنجاح</div>
                    </div>
                    <div class="setting-control">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" data-key="pos_sound" id="s_pos_sound">
                        </div>
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.low_stock_alert') }}</div>
                        <div class="label-desc">تنبيه الكاشير عند بيع منتج منخفض المخزون</div>
                    </div>
                    <div class="setting-control">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" data-key="low_stock_alert" id="s_low_stock_alert">
                        </div>
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <div class="label-text">{{ __('pos.allow_negative_stock') }}</div>
                        <div class="label-desc">السماح بإتمام البيع حتى لو نفذ المخزون</div>
                    </div>
                    <div class="setting-control">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" data-key="allow_negative_stock" id="s_allow_negative_stock">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Save Button --}}
        <div class="save-btn-wrapper">
            <div class="d-flex gap-2 justify-content-end">
                <button class="btn btn-secondary" onclick="loadSettings()">
                    <i class="fas fa-undo me-1"></i>{{ __('pos.cancel') }}
                </button>
                <button class="btn btn-primary btn-lg px-4" onclick="saveSettings()" id="saveBtn">
                    <i class="fas fa-save me-2"></i>{{ __('pos.save') }}
                    <span id="saveSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
let allSettings = {};

async function loadSettings() {
    const res  = await apiCall('{{ route("settings.all") }}');
    allSettings = res.settings || {};

    // Flatten all settings into a key->value map
    const flat = {};
    Object.values(allSettings).forEach(group => {
        Object.entries(group).forEach(([key, cfg]) => {
            flat[key] = cfg.value;
        });
    });

    // Apply to inputs
    document.querySelectorAll('[data-key]').forEach(el => {
        const key = el.dataset.key;
        const val = flat[key];
        if (val === undefined) return;

        if (el.type === 'checkbox') {
            el.checked = val === '1' || val === 'true' || val === true;
        } else {
            el.value = val;
        }
    });

    toggleTaxFields();
    updateTaxPreview();
}

function toggleTaxFields() {
    const enabled = document.getElementById('s_tax_enabled').checked;
    document.getElementById('taxFields').style.opacity  = enabled ? '1' : '0.4';
    document.getElementById('taxFields').style.pointerEvents = enabled ? 'auto' : 'none';
    document.getElementById('taxPreview').style.display = enabled ? 'block' : 'none';
    updateTaxPreview();
}

function updateTaxPreview() {
    const enabled   = document.getElementById('s_tax_enabled')?.checked;
    const rate      = parseFloat(document.getElementById('s_tax_rate')?.value) || 0;
    const inclusive = document.getElementById('s_tax_inclusive')?.checked;
    const price     = parseFloat(document.getElementById('previewPrice')?.value) || 100;

    let beforeTax, taxAmount, total;

    if (!enabled || rate === 0) {
        beforeTax = price; taxAmount = 0; total = price;
    } else if (inclusive) {
        total     = price;
        taxAmount = price - (price / (1 + rate / 100));
        beforeTax = price - taxAmount;
    } else {
        beforeTax = price;
        taxAmount = price * (rate / 100);
        total     = price + taxAmount;
    }

    const fmt = n => n.toFixed(2);
    document.getElementById('previewBefore').textContent = fmt(beforeTax);
    document.getElementById('previewTax').textContent   = `+${fmt(taxAmount)} (${rate}%)`;
    document.getElementById('previewTotal').textContent = fmt(total);
}

async function saveSettings() {
    const btn     = document.getElementById('saveBtn');
    const spinner = document.getElementById('saveSpinner');
    btn.disabled  = true;
    spinner.classList.remove('d-none');

    const settings = [];
    document.querySelectorAll('[data-key]').forEach(el => {
        const key = el.dataset.key;
        let value;
        if (el.type === 'checkbox') {
            value = el.checked ? '1' : '0';
        } else {
            value = el.value;
        }
        settings.push({ key, value });
    });

    try {
        const res = await apiCall('{{ route("settings.update") }}', 'POST', { settings });
        if (res.success) {
            showToast(res.message || '{{ __("pos.settings_saved") }}');
        } else {
            showToast(res.message || '{{ __("pos.error") }}', 'danger');
        }
    } catch (e) {
        showToast('{{ __("pos.error") }}', 'danger');
    } finally {
        btn.disabled = false;
        spinner.classList.add('d-none');
    }
}

function showTab(tab, btn) {
    document.querySelectorAll('.settings-tab').forEach(t => t.classList.add('d-none'));
    document.querySelectorAll('.settings-nav .nav-link').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.remove('d-none');
    btn.classList.add('active');
}

// Load on init
loadSettings();
document.getElementById('s_tax_rate').addEventListener('input', updateTaxPreview);
document.getElementById('s_tax_inclusive').addEventListener('change', updateTaxPreview);
document.getElementById('previewPrice').addEventListener('input', updateTaxPreview);
</script>
@endpush