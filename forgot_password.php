<?php
session_start();

$message = $_SESSION['forgot_message'] ?? '';
$message_type = $_SESSION['forgot_message_type'] ?? '';

unset($_SESSION['forgot_message']);
unset($_SESSION['forgot_message_type']);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Forgot Password | NexArena</title>

    <link
        rel="stylesheet"
        href="assets/css/forgot-password.css"
    >

</head>

<body>

<div class="forgot-page">

    <div class="forgot-card">

        <!-- Logo -->

        <div class="forgot-logo">

            <a href="index.php">

                <img
                    src="assets/images/logo.png"
                    alt="NexArena Logo"
                >

            </a>

        </div>


        <!-- Icon -->

        <div class="forgot-icon">
            🔐
        </div>


        <!-- Heading -->

        <div class="forgot-header">

            <span class="forgot-label">
                ACCOUNT RECOVERY
            </span>

            <h1>
                Forgot Password?
            </h1>

            <p>
                Enter the email address associated with your
                NexArena account and we'll send you a secure
                password reset link.
            </p>

        </div>


        <!-- Message -->

        <?php if (!empty($message)): ?>

            <div class="forgot-message <?= htmlspecialchars($message_type) ?>">

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <!-- Form -->

        <form
            action="forgot_password_process.php"
            method="POST"
            class="forgot-form"
        >

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your registered email"
                    required
                    autocomplete="email"
                >

            </div>


            <button
                type="submit"
                class="reset-btn"
            >
                Send Reset Link
                <span>→</span>
            </button>

        </form>


        <!-- Back Login -->

        <div class="back-login">

            <a href="login.php">
                ← Back to Login
            </a>

        </div>

    </div>

</div>

</body>

</html>