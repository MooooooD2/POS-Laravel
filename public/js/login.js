// public/js/login.js

async function handleLogin() {
    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value;
    const alertBox = document.getElementById("alertBox");
    const btn = document.getElementById("loginBtn");
    const spinner = document.getElementById("loginSpinner");

    if (!username || !password) {
        alertBox.textContent = "Please enter username and password";
        alertBox.classList.remove("d-none");
        return;
    }

    btn.disabled = true;
    spinner.classList.remove("d-none");
    alertBox.classList.add("d-none");

    // Get CSRF token with validation
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfMeta) {
        alertBox.textContent = "CSRF token not found. Please refresh the page.";
        alertBox.classList.remove("d-none");
        btn.disabled = false;
        spinner.classList.add("d-none");
        return;
    }
    
    const csrfToken = csrfMeta.content;
    console.log("CSRF Token found:", csrfToken ? "Yes" : "No"); // Debug log

    try {
        const res = await fetch(window.LOGIN_URL || "/login", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({ username, password }),
        });
        
        // Handle specific status codes
        if (res.status === 419) {
            alertBox.textContent = "Session expired. Refreshing page...";
            alertBox.classList.remove("d-none");
            setTimeout(() => window.location.reload(), 1500);
            return;
        }

        if (res.status === 429) {
            alertBox.textContent = "Too many attempts. Please wait a minute and try again.";
            alertBox.classList.remove("d-none");
            return;
        }
        
        if (res.status === 422) {
            const data = await res.json();
            alertBox.textContent = data.message || "Validation failed";
            alertBox.classList.remove("d-none");
            return;
        }
        
        const data = await res.json();
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            alertBox.textContent = data.message || "Login failed";
            alertBox.classList.remove("d-none");
        }
    } catch (e) {
        console.error("Login error:", e);
        alertBox.textContent = "Connection error. Please try again.";
        alertBox.classList.remove("d-none");
    } finally {
        btn.disabled = false;
        spinner.classList.add("d-none");
    }
}

function togglePassword() {
    const p = document.getElementById("password");
    const i = document.getElementById("eyeIcon");
    if (p && i) {
        if (p.type === "password") {
            p.type = "text";
            i.className = "fas fa-eye-slash";
        } else {
            p.type = "password";
            i.className = "fas fa-eye";
        }
    }
}

// Initialize event listeners when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById("loginForm");
    const toggleBtn = document.getElementById("togglePasswordBtn");

    if (loginForm) {
        loginForm.addEventListener("submit", function (e) {
            e.preventDefault();
            handleLogin();
        });
    }

    if (toggleBtn) {
        toggleBtn.addEventListener("click", togglePassword);
    }
});