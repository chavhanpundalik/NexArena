<?php
session_start();
?>

<?php

if (
    isset($_GET['registered']) &&
    $_GET['registered'] == 'success'
) {

    echo '
    <div class="success-message">
        Registration successful! Please login.
    </div>';

}


if (
    isset($_GET['error']) &&
    $_GET['error'] == 'wrong_password'
) {

    echo '
    <div class="error-message">
        Incorrect password. Please try again.
    </div>';

}


if (
    isset($_GET['error']) &&
    $_GET['error'] == 'email_not_found'
) {

    echo '
    <div class="error-message">
        Email is not registered.
    </div>';

}

?>


<?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>

    <div class="logout-success">

        <div class="logout-success-icon">
            ✓
        </div>

        <span>
            You have been logged out successfully.
        </span>

    </div>

<?php endif; ?>

<?php if (isset($_GET['login']) && $_GET['login'] === 'success'): ?>
    <div class="success-message">
        Login successful! Welcome back.
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexArena - Login</title>

    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">

</head>

<body>

<?php include 'assets/css/nav_bar.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const menuToggle = document.getElementById("menuToggle");
    const mainNav = document.getElementById("mainNav");

    if (menuToggle) {
        menuToggle.addEventListener("click", function () {
            mainNav.classList.toggle("active");

            if (mainNav.classList.contains("active")) {
                menuToggle.innerHTML = "✕";
            } else {
                menuToggle.innerHTML = "☰";
            }
        });
    }

    const navLinks = mainNav.querySelectorAll("a");

    navLinks.forEach(function (link) {
        link.addEventListener("click", function () {
            mainNav.classList.remove("active");
            if (menuToggle) menuToggle.innerHTML = "☰";
        });
    });

});
</script>

<!-- =========================
     LOGIN SECTION
========================= -->

<div class="login-container">

    <!-- LEFT SIDE - Info Panel -->
    <div class="login-info">
        <div class="login-brand">
            <span class="brand-icon">⚡</span>
            <span class="brand-name">Nex<span>Arena</span></span>
        </div>

        <div class="login-welcome">
            <h1>Welcome Back</h1>
            <p>Login to continue your sports journey</p>
        </div>

        <div class="login-features">
            <div class="login-feature">
                <div class="feature-icon">🏆</div>
                <div class="feature-content">
                    <h4>Compete & Win</h4>
                    <p>Join tournaments and compete with players worldwide</p>
                </div>
            </div>

            <div class="login-feature">
                <div class="feature-icon">🎮</div>
                <div class="feature-content">
                    <h4>Explore Events</h4>
                    <p>Discover exciting sports and gaming events</p>
                </div>
            </div>

            <div class="login-feature">
                <div class="feature-icon">👥</div>
                <div class="feature-content">
                    <h4>Connect & Play</h4>
                    <p>Build your team and play together</p>
                </div>
            </div>
        </div>

        <div class="login-footer-text">
            <p>© 2026 NexArena. All rights reserved.</p>
        </div>
    </div>

    <!-- RIGHT SIDE - Login Form -->
    <div class="login-form-box">

        <div class="login-header">
            <div class="login-header-icon">🔐</div>
            <h2>Sign In</h2>
            <p>Enter your credentials to access your account</p>
        </div>

        <form action="login_process.php" method="POST">

            <!-- EMAIL -->
            <div class="login-input-group">
                <label for="email">
                    <span class="input-icon">📧</span>
                    Email Address
                </label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>

            <!-- PASSWORD -->
            <div class="login-input-group">
                <label for="password">
                    <span class="input-icon">🔑</span>
                    Password
                </label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <!-- OPTIONS -->
            <div class="login-options">
                <label class="remember">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>
                <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
            </div>

            <!-- LOGIN BUTTON -->
            <button type="submit" class="login-submit">
                <span>Sign In</span>
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>

            <!-- DIVIDER -->
            <div class="login-divider">
                <span>Don't have an account?</span>
            </div>

            <!-- REGISTER -->
            <a href="registration.php" class="register-link">
                Create Account →
            </a>

        </form>

    </div>

</div>

<!-- Theme JavaScript -->
<script src="assets/js/theme.js"></script>

</body>
</html>