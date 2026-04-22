{{-- FILE: resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('pos.login') }} - {{ __('pos.app_name') }}</title>
    @if(app()->getLocale() === 'ar')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    @else
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: {{ app()->getLocale() === 'ar' ? "'Cairo', sans-serif" : "'Segoe UI', sans-serif" }};
        }
        .login-card {
            background: #fff;
            border-radius: 1rem;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
            width: 100%;
            max-width: 420px;
            margin: auto;
        }
        .login-logo {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.8rem; color: #fff;
        }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
        .btn-login {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none; color: #fff; width: 100%;
            padding: 0.75rem; border-radius: 0.5rem;
            font-weight: 600; font-size: 1rem;
            transition: opacity 0.2s;
        }
        .btn-login:hover { opacity: 0.9; color: #fff; }
        .lang-switcher { position: fixed; top: 1rem; {{ app()->getLocale() === 'ar' ? 'left: 1rem;' : 'right: 1rem;' }} }
    </style>
</head>
<body>
    <div class="lang-switcher">
        <a href="{{ route('lang.switch', 'ar') }}" class="btn btn-sm btn-outline-light">🇪🇬 AR</a>
        <a href="{{ route('lang.switch', 'en') }}" class="btn btn-sm btn-outline-light">🇺🇸 EN</a>
    </div>

    <div class="container">
        <div class="login-card mx-auto">
            <div class="login-logo"><i class="fas fa-cash-register"></i></div>
            <h4 class="text-center fw-bold mb-1">{{ __('pos.app_name') }}</h4>
            <p class="text-center text-muted mb-4">{{ __('pos.login') }}</p>

            <div id="alertBox" class="alert alert-danger d-none" role="alert"></div>

            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('pos.username') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" placeholder="{{ __('pos.username') }}" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">{{ __('pos.password') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" placeholder="{{ __('pos.password') }}" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button class="btn-login btn" id="loginBtn" onclick="handleLogin()">
                <span id="loginText">{{ __('pos.login') }}</span>
                <span id="loginSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
            </button>

            <p class="text-center text-muted mt-3 mb-0 small">
                Default: admin / admin123
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function handleLogin() {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const alertBox = document.getElementById('alertBox');
            const btn      = document.getElementById('loginBtn');
            const spinner  = document.getElementById('loginSpinner');
            const text     = document.getElementById('loginText');

            if (!username || !password) {
                alertBox.textContent = 'Please enter username and password';
                alertBox.classList.remove('d-none');
                return;
            }

            btn.disabled = true;
            spinner.classList.remove('d-none');
            alertBox.classList.add('d-none');

            try {
                const res = await fetch('{{ route("login.post") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ username, password })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alertBox.textContent = data.message || 'Login failed';
                    alertBox.classList.remove('d-none');
                }
            } catch(e) {
                alertBox.textContent = 'Connection error';
                alertBox.classList.remove('d-none');
            } finally {
                btn.disabled = false;
                spinner.classList.add('d-none');
            }
        }

        function togglePassword() {
            const p = document.getElementById('password');
            const i = document.getElementById('eyeIcon');
            if (p.type === 'password') { p.type = 'text'; i.className = 'fas fa-eye-slash'; }
            else { p.type = 'password'; i.className = 'fas fa-eye'; }
        }

        document.addEventListener('keypress', e => { if (e.key === 'Enter') handleLogin(); });
    </script>
</body>
</html>
