{{-- FILE: resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('pos.app_name'))</title>

    {{-- Bootstrap RTL/LTR --}}
    @if(app()->getLocale() === 'ar')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    @else
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    @endif

    {{-- Icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Arabic Font --}}
    @if(app()->getLocale() === 'ar')
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    @endif

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --sidebar-width: 260px;
            --sidebar-bg: #1e293b;
            --sidebar-text: #cbd5e1;
            --sidebar-active: #3b82f6;
        }

        body {
            font-family: {{ app()->getLocale() === 'ar' ? "'Cairo', sans-serif" : "'Segoe UI', sans-serif" }};
            background: #f1f5f9;
            min-height: 100vh;
        }

        /* Sidebar */
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--sidebar-bg);
            position: fixed;
            {{ app()->getLocale() === 'ar' ? 'right: 0;' : 'left: 0;' }}
            top: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: all 0.2s;
            border-radius: 0.375rem;
            margin: 0.15rem 0.5rem;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: var(--sidebar-active);
            color: #fff;
        }

        .sidebar-menu a i { width: 20px; text-align: center; }

        /* Main content */
        #main-content {
            {{ app()->getLocale() === 'ar' ? 'margin-right: var(--sidebar-width);' : 'margin-left: var(--sidebar-width);' }}
            min-height: 100vh;
        }

        /* Top navbar */
        #topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-content { padding: 1.5rem; }

        /* Cards */
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.08);
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid #f1f5f9;
            padding: 1rem 1.25rem;
            font-weight: 600;
        }

        /* Stats cards */
        .stat-card {
            border-radius: 0.75rem;
            color: #fff;
            padding: 1.25rem;
        }

        .stat-card.blue   { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-card.green  { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-card.orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-card.red    { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .stat-card.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

        /* Badges */
        .badge-low-stock  { background: #fef3c7; color: #92400e; }
        .badge-out-stock  { background: #fee2e2; color: #991b1b; }
        .badge-in-stock   { background: #d1fae5; color: #065f46; }

        /* Responsive */
        @media (max-width: 768px) {
            #sidebar { transform: translateX({{ app()->getLocale() === 'ar' ? '100%' : '-100%' }}); }
            #sidebar.show { transform: translateX(0); }
            #main-content { margin: 0 !important; }
        }

        /* Toast notifications */
        .toast-container { position: fixed; top: 1rem; {{ app()->getLocale() === 'ar' ? 'left: 1rem;' : 'right: 1rem;' }} z-index: 9999; }
    </style>

    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<nav id="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-cash-register me-2"></i>
        {{ __('pos.app_name') }}
    </div>
    <div class="sidebar-menu mt-2">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fas fa-tachometer-alt"></i> {{ __('pos.dashboard') }}
        </a>
        <a href="{{ route('pos') }}" class="{{ request()->routeIs('pos') ? 'active' : '' }}">
            <i class="fas fa-cash-register"></i> {{ __('pos.pos') }}
        </a>
        <a href="{{ route('warehouse') }}" class="{{ request()->routeIs('warehouse') ? 'active' : '' }}">
            <i class="fas fa-boxes"></i> {{ __('pos.warehouse') }}
        </a>
        <a href="{{ route('suppliers') }}" class="{{ request()->routeIs('suppliers') ? 'active' : '' }}">
            <i class="fas fa-truck"></i> {{ __('pos.suppliers') }}
        </a>
        <a href="{{ route('purchase-orders') }}" class="{{ request()->routeIs('purchase-orders') ? 'active' : '' }}">
            <i class="fas fa-file-invoice"></i> {{ __('pos.purchase_orders') }}
        </a>
        <a href="{{ route('supplier-payments') }}" class="{{ request()->routeIs('supplier-payments') ? 'active' : '' }}">
            <i class="fas fa-money-bill-wave"></i> {{ __('pos.supplier_payments') }}
        </a>
        <a href="{{ route('supplier-accounts') }}" class="{{ request()->routeIs('supplier-accounts') ? 'active' : '' }}">
            <i class="fas fa-balance-scale"></i> {{ __('pos.supplier_accounts') }}
        </a>
        <a href="{{ route('accounting') }}" class="{{ request()->routeIs('accounting') ? 'active' : '' }}">
            <i class="fas fa-book"></i> {{ __('pos.accounting') }}
        </a>
        <a href="{{ route('reports') }}" class="{{ request()->routeIs('reports') ? 'active' : '' }}">
            <i class="fas fa-chart-bar"></i> {{ __('pos.reports') }}
        </a>
        <a href="{{ route('financial-reports') }}" class="{{ request()->routeIs('financial-reports') ? 'active' : '' }}">
            <i class="fas fa-file-alt"></i> {{ __('pos.financial_reports') }}
        </a>
        <a href="{{ route('returns') }}" class="{{ request()->routeIs('returns') ? 'active' : '' }}">
            <i class="fas fa-undo"></i> {{ __('pos.returns') }}
        </a>
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
            {{-- Language Toggle --}}
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-globe"></i>
                    {{ app()->getLocale() === 'ar' ? 'العربية' : 'English' }}
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('lang.switch', 'ar') }}">🇪🇬 العربية</a></li>
                    <li><a class="dropdown-item" href="{{ route('lang.switch', 'en') }}">🇺🇸 English</a></li>
                </ul>
            </div>

            {{-- User Menu --}}
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle"></i>
                    {{ auth()->user()->full_name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->role }}</span></li>
                    <li><hr class="dropdown-divider"></li>
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
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>`;
        document.getElementById('toastContainer').appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
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
</script>

@stack('scripts')
</body>
</html>
