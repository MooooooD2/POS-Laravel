{{-- FILE: resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('pos.app_name'))</title>

    {{-- Remove default favicon / No icon --}}
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text x='50' y='50' text-anchor='middle' dominant-baseline='middle' font-size='80'>🏪</text></svg>">

    {{-- Bootstrap RTL/LTR --}}
    @if (app()->getLocale() === 'ar')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    @else
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    @endif

    {{-- Icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Arabic Font --}}
    {{-- Arabic Font - Cairo (Most Reliable) --}}
    @if (app()->getLocale() === 'ar')
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap"
            rel="stylesheet">
    @endif
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">

    @stack('styles')
</head>

<body>

    {{-- Sidebar --}}
    <nav id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-cash-register me-2"></i>
            {{ __('pos.app_name') }}
        </div>
       {{-- Update the sidebar menu section --}}
<div class="sidebar-menu mt-2">
    {{-- Dashboard - All authenticated users --}}
    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i class="fas fa-tachometer-alt"></i> {{ __('pos.dashboard') }}
    </a>

    {{-- POS - Check permission instead of role --}}
    @permission('view_pos')
        <a href="{{ route('pos') }}" class="{{ request()->routeIs('pos') ? 'active' : '' }}">
            <i class="fas fa-cash-register"></i> {{ __('pos.pos') }}
        </a>
    @endpermission

    {{-- Returns - Check permission --}}
    @permission('view_returns')
        <a href="{{ route('returns') }}" class="{{ request()->routeIs('returns') ? 'active' : '' }}">
            <i class="fas fa-undo"></i> {{ __('pos.returns') }}
        </a>
    @endpermission

    {{-- Warehouse & Products - Check permission --}}
    @permission('view_warehouse')
        <a href="{{ route('warehouse') }}" class="{{ request()->routeIs('warehouse') ? 'active' : '' }}">
            <i class="fas fa-boxes"></i> {{ __('pos.warehouse') }}
        </a>
    @endpermission

    @permission('view_suppliers')
        <a href="{{ route('suppliers') }}" class="{{ request()->routeIs('suppliers') ? 'active' : '' }}">
            <i class="fas fa-truck"></i> {{ __('pos.suppliers') }}
        </a>
    @endpermission

    @permission('view_purchase_orders')
        <a href="{{ route('purchase-orders') }}" class="{{ request()->routeIs('purchase-orders') ? 'active' : '' }}">
            <i class="fas fa-file-invoice"></i> {{ __('pos.purchase_orders') }}
        </a>
    @endpermission

    @permission('view_supplier_payments')
        <a href="{{ route('supplier-payments') }}" class="{{ request()->routeIs('supplier-payments') ? 'active' : '' }}">
            <i class="fas fa-money-bill-wave"></i> {{ __('pos.supplier_payments') }}
        </a>

        <a href="{{ route('supplier-accounts') }}" class="{{ request()->routeIs('supplier-accounts') ? 'active' : '' }}">
            <i class="fas fa-balance-scale"></i> {{ __('pos.supplier_accounts') }}
        </a>
    @endpermission

    {{-- Reports - Check permission --}}
    @permission('view_reports')
        <a href="{{ route('reports') }}" class="{{ request()->routeIs('reports') ? 'active' : '' }}">
            <i class="fas fa-chart-bar"></i> {{ __('pos.reports') }}
        </a>
    @endpermission

    {{-- Accounting & Financial Reports - Check permission --}}
    @permission('view_accounting')
        <a href="{{ route('accounting') }}" class="{{ request()->routeIs('accounting') ? 'active' : '' }}">
            <i class="fas fa-book"></i> {{ __('pos.accounting') }}
        </a>
    @endpermission

    @permission('view_financial_reports')
        <a href="{{ route('financial-reports') }}" class="{{ request()->routeIs('financial-reports') ? 'active' : '' }}">
            <i class="fas fa-file-alt"></i> {{ __('pos.financial_reports') }}
        </a>
    @endpermission

    {{-- Settings - Check permission --}}
    @permission('view_settings')
        <a href="{{ route('settings') }}" class="{{ request()->routeIs('settings') ? 'active' : '' }}">
            <i class="fas fa-cog"></i> {{ __('pos.settings') }}
        </a>
    @endpermission
</div>
    </nav>

    {{-- Main Content --}}
    <div id="main-content">
        {{-- Topbar --}}
        <div id="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-md-none" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h6 class="mb-0 fw-semibold">@yield('page-title', __('pos.dashboard'))</h6>
            </div>
            <div class="d-flex align-items-center gap-3">
                {{-- Low Stock Notification Bell --}}
                <div class="dropdown" id="stockNotifDropdown">
                    <button class="btn btn-sm btn-outline-secondary position-relative" id="stockBellBtn"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside"
                        onclick="loadStockAlerts()" title="{{ app()->getLocale() === 'ar' ? 'تنبيهات المخزون' : 'Stock Alerts' }}">
                        <i class="fas fa-bell"></i>
                        <span id="stockBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                            style="font-size:0.6rem"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0 shadow"
                        style="min-width:320px; max-width:380px; max-height:420px; overflow-y:auto; border-radius:10px">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom bg-warning bg-opacity-10">
                            <span class="fw-bold small">
                                <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                {{ app()->getLocale() === 'ar' ? 'تنبيهات المخزون' : 'Stock Alerts' }}
                            </span>
                            <a href="{{ route('warehouse') }}" class="btn btn-xs btn-link btn-sm text-decoration-none p-0 small">
                                {{ app()->getLocale() === 'ar' ? 'عرض الكل' : 'View All' }}
                            </a>
                        </div>
                        <div id="stockAlertsList">
                            <div class="text-center py-3 text-muted small">
                                <i class="fas fa-spinner fa-spin me-1"></i>
                                {{ app()->getLocale() === 'ar' ? 'جاري التحميل...' : 'Loading...' }}
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Language Toggle --}}
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-globe"></i>
                        {{ app()->getLocale() === 'ar' ? 'العربية' : 'English' }}
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item {{ app()->getLocale() === 'ar' ? 'active' : '' }}"
                                href="{{ route('lang.switch', 'ar') }}">
                                🇪🇬 العربية
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}"
                                href="{{ route('lang.switch', 'en') }}">
                                🇺🇸 English
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- User Menu --}}
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i>
                        {{ auth()->user()->full_name }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        {{-- Display user role(s) properly --}}
                        <li>
                            <span class="dropdown-item-text text-muted small">
                                @php
                                    $roles = auth()->user()->getRoleNames();
                                @endphp
                                @if($roles->count() > 0)
                                    {{ ucfirst($roles->implode(', ')) }}
                                @else
                                    {{ __('pos.no_role') }}
                                @endif
                            </span>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>{{ __('pos.logout') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Page Content --}}
        <div class="page-content">
            @yield('content')
        </div>
    </div>

    {{-- Toast Container --}}
    <div class="toast-container" id="toastContainer"></div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // CSRF token for AJAX - رمز CSRF لطلبات AJAX
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const LOCALE = '{{ app()->getLocale() }}';

        // Helper: Show toast notification - عرض إشعار
        function showToast(message, type = 'success') {
            const colorMap = { success: 'success', error: 'danger', warning: 'warning text-dark' };
            const bgClass = colorMap[type] || 'success';
            const toastEl = document.createElement('div');
            toastEl.className = `toast align-items-center text-white bg-${bgClass} border-0`;
            toastEl.setAttribute('role', 'alert');
            toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>`;
            document.getElementById('toastContainer').appendChild(toastEl);
            const toast = new bootstrap.Toast(toastEl, {
                delay: 3000
            });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        }

        // Helper: API call - طلب API
        async function apiCall(url, method = 'GET', data = null) {
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                }
            };
            if (data) options.body = JSON.stringify(data);
            const res = await fetch(url, options);
            return res.json();
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }

        // Format currency - تنسيق العملة
        function formatCurrency(amount) {
            return new Intl.NumberFormat(LOCALE === 'ar' ? 'ar-EG' : 'en-US', {
                style: 'currency',
                currency: 'EGP',
                minimumFractionDigits: 2
            }).format(amount || 0);
        }

        // Format date - تنسيق التاريخ
        function formatDate(date) {
            return new Date(date).toLocaleDateString(LOCALE === 'ar' ? 'ar-EG' : 'en-US');
        }

        // Add RTL/LTR class to body based on locale
        const locale = '{{ app()->getLocale() }}';
        if (locale === 'ar') {
            document.body.classList.add('rtl');
        } else {
            document.body.classList.add('ltr');
        }
    </script>

    <script>
    // ── Stock Alert Notification Bell ──────────────────────────────────
    (function initStockBell() {
        const isAr = LOCALE === 'ar';

        async function fetchAlerts() {
            try {
                const res = await fetch('{{ route("dashboard.low-stock") }}', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
                });
                return await res.json();
            } catch (e) { return null; }
        }

        function renderAlerts(data) {
            const badge = document.getElementById('stockBadge');
            const list  = document.getElementById('stockAlertsList');
            if (!data) {
                list.innerHTML = `<div class="text-center py-3 text-danger small">${isAr ? 'فشل تحميل البيانات' : 'Failed to load'}</div>`;
                return;
            }

            const total = data.total_alerts;
            if (total > 0) {
                badge.textContent = total > 99 ? '99+' : total;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }

            if (total === 0) {
                list.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                        <span class="text-muted small">${isAr ? 'المخزون بخير، لا توجد تنبيهات' : 'All stock levels are fine'}</span>
                    </div>`;
                return;
            }

            let html = '';

            if (data.out_of_stock && data.out_of_stock.length > 0) {
                html += `<div class="px-3 pt-2 pb-1">
                    <span class="badge bg-danger mb-1">${isAr ? 'نفد المخزون' : 'Out of Stock'}</span>
                </div>`;
                data.out_of_stock.forEach(p => {
                    html += `<a href="{{ route('warehouse') }}" class="dropdown-item d-flex align-items-center gap-2 py-2 border-bottom text-decoration-none">
                        <span class="flex-shrink-0 text-danger"><i class="fas fa-times-circle"></i></span>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-semibold small text-truncate">${p.name}</div>
                            <div class="text-muted" style="font-size:0.72rem">
                                ${isAr ? 'الكمية: ' : 'Qty: '}<strong class="text-danger">0</strong>
                                ${p.category ? ' &bull; ' + p.category : ''}
                            </div>
                        </div>
                        <span class="badge bg-danger bg-opacity-15 text-white border border-danger" style="font-size:0.65rem">${isAr ? 'نفذ' : 'Empty'}</span>
                    </a>`;
                });
            }

            if (data.low_stock && data.low_stock.length > 0) {
                html += `<div class="px-3 pt-2 pb-1">
                    <span class="badge bg-warning text-dark mb-1">${isAr ? 'مخزون منخفض' : 'Low Stock'}</span>
                </div>`;
                data.low_stock.forEach(p => {
                    html += `<a href="{{ route('warehouse') }}" class="dropdown-item d-flex align-items-center gap-2 py-2 border-bottom text-decoration-none">
                        <span class="flex-shrink-0 text-warning"><i class="fas fa-exclamation-triangle"></i></span>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-semibold small text-truncate">${p.name}</div>
                            <div class="text-muted" style="font-size:0.72rem">
                                ${isAr ? 'الكمية: ' : 'Qty: '}<strong class="text-warning">${p.quantity}</strong>
                                ${isAr ? ' / الحد الأدنى: ' : ' / Min: '}<strong>${p.min_stock}</strong>
                                ${p.category ? ' &bull; ' + p.category : ''}
                            </div>
                        </div>
                        <span class="badge bg-warning bg-opacity-15 text-white border border-warning" style="font-size:0.65rem">${isAr ? 'منخفض' : 'Low'}</span>
                    </a>`;
                });
            }

            list.innerHTML = html;
        }

        window.loadStockAlerts = async function() {
            const list = document.getElementById('stockAlertsList');
            list.innerHTML = `<div class="text-center py-3 text-muted small"><i class="fas fa-spinner fa-spin me-1"></i>${isAr ? 'جاري التحميل...' : 'Loading...'}</div>`;
            const data = await fetchAlerts();
            renderAlerts(data);
        };

        // Auto-load badge count on page load
        fetchAlerts().then(data => {
            if (!data) return;
            const badge = document.getElementById('stockBadge');
            const total = data.total_alerts;
            if (total > 0) {
                badge.textContent = total > 99 ? '99+' : total;
                badge.classList.remove('d-none');
                // Show a toast on page load if there are alerts
                if (total > 0) {
                    const msg = isAr
                        ? `⚠️ تنبيه: ${total} منتج ${total === 1 ? 'قرب على النفاذ أو نفذ' : 'منتجات قربت على النفاذ أو نفذت'} من المخزون`
                        : `⚠️ Alert: ${total} product${total > 1 ? 's' : ''} with low or no stock`;
                    setTimeout(() => showToast(msg, 'warning'), 1000);
                }
            }
        });
    })();
    // ────────────────────────────────────────────────────────────────────
    </script>

    @stack('scripts')
</body>

</html>