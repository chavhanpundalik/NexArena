<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration | NexArena</title>

    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/registration.css">
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

<?php if (isset($_GET['registered']) && $_GET['registered'] === 'success'): ?>
    <div class="success-message">
        Registration successful! Please login.
    </div>
<?php endif; ?>

<div class="register-container">

    <!-- =========================
         LEFT INFORMATION BOX
    ========================== -->

    <div class="info-box">
        <div class="info-brand">
            <span class="brand-icon">⚡</span>
            <span class="brand-name">Nex<span>Arena</span></span>
        </div>

        <div class="info-welcome">
            <h1>Join the <span>Community</span></h1>
            <p>Create your account and start your sports journey</p>
        </div>

        <div class="info-features">
            <div class="info-feature">
                <div class="feature-icon">🏆</div>
                <div class="feature-content">
                    <h4>Compete & Win</h4>
                    <p>Participate in exciting tournaments and events</p>
                </div>
            </div>

            <div class="info-feature">
                <div class="feature-icon">⚡</div>
                <div class="feature-content">
                    <h4>Fast Registration</h4>
                    <p>Create your account quickly and easily</p>
                </div>
            </div>

            <div class="info-feature">
                <div class="feature-icon">🎮</div>
                <div class="feature-content">
                    <h4>Sports & Gaming</h4>
                    <p>Explore different sports and gaming competitions</p>
                </div>
            </div>
        </div>

        <div class="info-footer">
            <p>Already have an account? <a href="login.php">Sign In</a></p>
        </div>
    </div>

    <!-- =========================
         RIGHT REGISTRATION FORM
    ========================== -->

    <div class="form-box">

        <div class="form-header">
            <div class="form-header-icon">✨</div>
            <h2>Create Account</h2>
            <p>Fill in the details to get started</p>
        </div>

        <form action="register_process.php" method="POST">

            <!-- FULL NAME -->
            <div class="input-group">
                <label for="fullname">
                    <span class="input-icon">👤</span>
                    Full Name
                </label>
                <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required>
            </div>

            <!-- USERNAME -->
            <div class="input-group">
                <label for="username">
                    <span class="input-icon">@</span>
                    Username
                </label>
                <input type="text" id="username" name="username" placeholder="Choose a username" required>
            </div>

            <!-- EMAIL -->
            <div class="input-group">
                <label for="email">
                    <span class="input-icon">📧</span>
                    Email Address
                </label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>

            <!-- PHONE -->
            <div class="input-group">
                <label for="phone">
                    <span class="input-icon">📱</span>
                    Phone Number
                </label>
                <input type="tel" id="phone" name="phone" placeholder="Phone number" required>
            </div>

            <!-- PASSWORD + CONFIRM PASSWORD -->
            <div class="input-row">
                <div class="input-group">
                    <label for="password">
                        <span class="input-icon">🔑</span>
                        Password
                    </label>
                    <input type="password" id="password" name="password" placeholder="Create password" required>
                </div>

                <div class="input-group">
                    <label for="confirm_password">
                        <span class="input-icon">✓</span>
                        Confirm Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                </div>
            </div>

            <!-- TERMS -->
            <div class="terms">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a></label>
            </div>

            <!-- BUTTON -->
            <button type="submit" class="register-btn">
                <span>Create Account</span>
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>

            <!-- LOGIN -->
            <div class="login-divider">
                <span>Already have an account?</span>
            </div>

            <a href="login.php" class="login-link">Sign In →</a>

        </form>

    </div>

</div>

<!-- Theme JavaScript -->
<script src="assets/js/theme.js"></script>

</body>
</html>