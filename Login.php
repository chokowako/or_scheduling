<?php
session_start();
// Grabs any error message passed from login_process.php if set
$error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
unset($_SESSION['login_error']); // Clear error after reading


?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>OR Scheduling System - Login</title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons CDN -->
    <link 
        rel="stylesheet" 
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Custom CSS -->
    <link
        rel="stylesheet"
        href="assets/css/login.css"
    >
</head>

<body>

<div class="login-container">

    <div class="card login-card">
        
        <!-- Top Gradient Header Banner -->
        <div class="login-header-banner">
            <div class="system-icon">
                <i class="bi bi-hospital"></i>
            </div>
        </div>

        <div class="card-body px-5 pb-5 pt-4">

            <h3 class="text-center fw-bold">
                OR Scheduling System
            </h3>

            <p class="text-center text-muted mb-4">
                Please sign in to continue
            </p>

            <!-- Error Alert Box (Displays if $_SESSION['login_error'] is set) -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2 small mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="login_process.php" method="POST" id="loginForm">

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input
                            type="text"
                            name="username"
                            class="form-control form-control-lg"
                            placeholder="Enter username"
                            required
                        >
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input
                            type="password"
                            name="password"
                            id="passwordInput"
                            class="form-control form-control-lg"
                            placeholder="Enter password"
                            required
                        >
                        <span class="input-group-text toggle-password" id="togglePassword">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                        <label class="form-check-label text-muted small" for="rememberMe">Remember me</label>
                    </div>
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>

                <button
                    type="submit"
                    id="submitBtn"
                    class="btn btn-primary btn-lg w-100 py-2"
                >
                    <span class="btn-text">Sign In</span>
                    <span class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                </button>

            </form>

            <div class="text-center mt-4">
                <small class="text-muted">
                    OR Scheduling System &copy; <?php echo date('Y'); ?>
                </small>
            </div>

        </div>
    </div>

</div>

<!-- Bootstrap JS Bundle (required for alert dismiss and dropdowns) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Interactive Script for Password Toggle & Loading State -->
<script>
    // Password visibility toggle
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('passwordInput');
    const toggleIcon = document.getElementById('toggleIcon');

    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle icon class between eye and eye-slash
        toggleIcon.classList.toggle('bi-eye');
        toggleIcon.classList.toggle('bi-eye-slash');
    });

    // Button loading state on submit
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const spinner = submitBtn.querySelector('.spinner-border');

    loginForm.addEventListener('submit', function (e) {
        submitBtn.setAttribute('disabled', 'true');
        btnText.textContent = 'Signing in...';
        spinner.classList.remove('d-none');
    });
</script>

</body>
</html>