// public/js/login.js

var _loginBusy = false;

function handleLogin() {
    if (_loginBusy) return;

    var username  = document.getElementById("username").value.trim();
    var password  = document.getElementById("password").value;
    var alertBox  = document.getElementById("alertBox");
    var btn       = document.getElementById("loginBtn");
    var spinner   = document.getElementById("loginSpinner");

    alertBox.classList.add("d-none");

    if (!username || !password) {
        alertBox.textContent = "أدخل اسم المستخدم وكلمة المرور";
        alertBox.classList.remove("d-none");
        return;
    }

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfMeta) {
        alertBox.textContent = "حدث خطأ. أعد تحميل الصفحة.";
        alertBox.classList.remove("d-none");
        return;
    }

    _loginBusy       = true;
    btn.disabled     = true;
    spinner.classList.remove("d-none");

    fetch(window.LOGIN_URL || "/login", {
        method: "POST",
        credentials: "same-origin",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfMeta.content,
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({ username: username, password: password })
    })
    .then(function(res) {
        if (res.status === 419) {
            alertBox.textContent = "انتهت الجلسة. جاري تحديث الصفحة...";
            alertBox.classList.remove("d-none");
            setTimeout(function() { window.location.reload(); }, 1500);
            return null;
        }
        if (res.status === 429) {
            alertBox.textContent = "محاولات كثيرة. انتظر دقيقة وحاول مجدداً.";
            alertBox.classList.remove("d-none");
            return null;
        }
        return res.json();
    })
    .then(function(data) {
        if (!data) return;
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            alertBox.textContent = data.message || "بيانات خاطئة";
            alertBox.classList.remove("d-none");
        }
    })
    .catch(function() {
        alertBox.textContent = "خطأ في الاتصال. تحقق من الإنترنت وحاول مجدداً.";
        alertBox.classList.remove("d-none");
    })
    .finally(function() {
        _loginBusy       = false;
        btn.disabled     = false;
        spinner.classList.add("d-none");
    });
}

function togglePassword() {
    var p = document.getElementById("password");
    var i = document.getElementById("eyeIcon");
    if (!p || !i) return;
    if (p.type === "password") {
        p.type      = "text";
        i.className = "fas fa-eye-slash";
    } else {
        p.type      = "password";
        i.className = "fas fa-eye";
    }
}

// Bind events immediately — script is at bottom of <body>, DOM is ready
(function() {
    var form      = document.getElementById("loginForm");
    var toggleBtn = document.getElementById("togglePasswordBtn");

    if (form) {
        form.addEventListener("submit", function(e) {
            e.preventDefault();
            handleLogin();
        });
    }

    if (toggleBtn) {
        toggleBtn.addEventListener("click", togglePassword);
    }
})();
