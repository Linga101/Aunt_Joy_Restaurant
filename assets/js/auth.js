/**
 * Authentication utilities (login + register)
 */

document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", handleLogin);
    }

    const registerForm = document.getElementById("registerForm");
    if (registerForm) {
        registerForm.addEventListener("submit", handleRegister);
    }
});

async function handleLogin(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector("button[type='submit']");

    if (!validateForm(form)) {
        showAlert("Please fill in all required fields.", "warning");
        return;
    }

    const payload = {
        username: form.username.value.trim(),
        password: form.password.value,
    };

    try {
        showLoading(submitBtn);
        const result = await apiCall("auth/login.php", "POST", payload);
        showAlert(result.message || "Login successful", "success");

        const userRole = result.data?.role_name;
        const redirects = {
            Customer: "/aunt_joy/views/customer/menu.php",
            Administrator: "/aunt_joy/views/admin/dashboard.php",
            "Sales Personnel": "/aunt_joy/views/sales/dashboard.php",
            Manager: "/aunt_joy/views/manager/dashboard.php",
        };

        setTimeout(() => {
            window.location.href = redirects[userRole] || "/aunt_joy/index.php";
        }, 500);
    } catch (error) {
        showAlert(error.message || "Login failed", "error");
    } finally {
        hideLoading(submitBtn);
    }
}

async function handleRegister(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector("button[type='submit']");

    if (!validateForm(form)) {
        showAlert("Please fill in all required fields.", "warning");
        return;
    }

    const password = form.password.value;
    const confirmPassword = form.confirm_password.value;

    if (password !== confirmPassword) {
        showAlert("Passwords do not match.", "error");
        return;
    }

    const payload = {
        full_name: form.full_name.value.trim(),
        username: form.username.value.trim(),
        email: form.email.value.trim(),
        phone_number: form.phone_number.value.trim(),
        password,
    };

    try {
        showLoading(submitBtn);
        const result = await apiCall("auth/register.php", "POST", payload);
        showAlert(result.message || "Registration successful", "success");
        setTimeout(() => {
            window.location.href = "/aunt_joy/views/customer/menu.php";
        }, 600);
    } catch (error) {
        showAlert(error.message || "Registration failed", "error");
    } finally {
        hideLoading(submitBtn);
    }
}

