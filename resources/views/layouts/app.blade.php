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
            <a href="{{ route('purchase-orders') }}"
                class="{{ request()->routeIs('purchase-orders') ? 'active' : '' }}">
                <i class="fas fa-file-invoice"></i> {{ __('pos.purchase_orders') }}
            </a>
            <a href="{{ route('supplier-payments') }}"
                class="{{ request()->routeIs('supplier-payments') ? 'active' : '' }}">
                <i class="fas fa-money-bill-wave"></i> {{ __('pos.supplier_payments') }}
            </a>
            <a href="{{ route('supplier-accounts') }}"
                class="{{ request()->routeIs('supplier-accounts') ? 'active' : '' }}">
                <i class="fas fa-balance-scale"></i> {{ __('pos.supplier_accounts') }}
            </a>
            <a href="{{ route('accounting') }}" class="{{ request()->routeIs('accounting') ? 'active' : '' }}">
                <i class="fas fa-book"></i> {{ __('pos.accounting') }}
            </a>
            <a href="{{ route('reports') }}" class="{{ request()->routeIs('reports') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i> {{ __('pos.reports') }}
            </a>
            <a href="{{ route('financial-reports') }}"
                class="{{ request()->routeIs('financial-reports') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i> {{ __('pos.financial_reports') }}
            </a>
            <a href="{{ route('returns') }}" class="{{ request()->routeIs('returns') ? 'active' : '' }}">
                <i class="fas fa-undo"></i> {{ __('pos.returns') }}
            </a>
            <a href="{{ route('settings') }}" class="{{ request()->routeIs('settings') ? 'active' : '' }}">
                <i class="fas fa-cog"></i> {{ __('pos.settings') }}
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
                        <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->role }}</span></li>
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
            const toastEl = document.createElement('div');
            toastEl.className =
                `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
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

    @stack('scripts')
</body>

</html>