// public/js/login.js
async function handleLogin() {
    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value;
    const alertBox = document.getElementById("alertBox");
    const btn = document.getElementById("loginBtn");
    const spinner = document.getElementById("loginSpinner");
    const text = document.getElementById("loginText");

    if (!username || !password) {
        alertBox.textContent = "Please enter username and password";
        alertBox.classList.remove("d-none");
        return;
    }

    btn.disabled = true;
    spinner.classList.remove("d-none");
    alertBox.classList.add("d-none");

    try {
        const res = await fetch("/login", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
                Accept: "application/json",
            },
            body: JSON.stringify({ username, password }),
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            alertBox.textContent = data.message || "Login failed";
            alertBox.classList.remove("d-none");
        }
    } catch (e) {
        alertBox.textContent = "Connection error";
        alertBox.classList.remove("d-none");
    } finally {
        btn.disabled = false;
        spinner.classList.add("d-none");
    }
}

function togglePassword() {
    const p = document.getElementById("password");
    const i = document.getElementById("eyeIcon");
    if (p.type === "password") {
        p.type = "text";
        i.className = "fas fa-eye-slash";
    } else {
        p.type = "password";
        i.className = "fas fa-eye";
    }
}

// Initialize event listeners when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
    const loginBtn = document.getElementById("loginBtn");
    const passwordField = document.getElementById("password");
    const toggleBtn = document.querySelector("#password + button");

    if (loginBtn) {
        loginBtn.addEventListener("click", handleLogin);
    }

    if (toggleBtn) {
        toggleBtn.addEventListener("click", togglePassword);
    }

    document.addEventListener("keypress", function (e) {
        if (e.key === "Enter") handleLogin();
    });
});
