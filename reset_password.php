<?php

session_start();

include 'db_connect.php';


/* =========================================================
   GET TOKEN
========================================================= */

$token = trim($_GET['token'] ?? '');

if (empty($token)) {

    $error =
        'This password reset link is invalid.';

} else {

    $token_hash =
        hash('sha256', $token);


    /* =====================================================
       FIND VALID TOKEN
    ===================================================== */

    $stmt = $conn->prepare("
        SELECT
            pr.reset_id,
            pr.user_id,
            u.email
        FROM password_resets pr

        INNER JOIN users u
            ON u.user_id = pr.user_id

        WHERE pr.token_hash = ?
          AND pr.expires_at > NOW()
          AND pr.used_at IS NULL

        LIMIT 1
    ");

    $stmt->bind_param(
        "s",
        $token_hash
    );

    $stmt->execute();

    $result =
        $stmt->get_result();

    $reset =
        $result->fetch_assoc();

    $stmt->close();


    if (!$reset) {

        $error =
            'This password reset link is invalid or has expired.';

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Reset Password | NexArena
    </title>

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


        <?php if (!empty($error)): ?>

            <!-- Invalid Token -->

            <div class="forgot-icon">
                ⚠️
            </div>

            <div class="forgot-header">

                <span class="forgot-label">
                    PASSWORD RESET
                </span>

                <h1>
                    Link Expired
                </h1>

                <p>
                    <?= htmlspecialchars($error) ?>
                </p>

            </div>

            <div class="back-login">

                <a href="forgot_password.php">
                    Request a New Link
                </a>

            </div>


        <?php else: ?>

            <!-- Valid Token -->

            <div class="forgot-icon">
                🔑
            </div>

            <div class="forgot-header">

                <span class="forgot-label">
                    NEW PASSWORD
                </span>

                <h1>
                    Reset Password
                </h1>

                <p>
                    Create a new secure password for your
                    NexArena account.
                </p>

            </div>


            <!-- Reset Form -->

            <form
                action="reset_password_process.php"
                method="POST"
                class="forgot-form"
            >

                <input
                    type="hidden"
                    name="token"
                    value="<?= htmlspecialchars($token) ?>"
                >


                <!-- Password -->

                <div class="form-group">

                    <label for="password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter new password"
                        minlength="8"
                        required
                        autocomplete="new-password"
                    >

                </div>


                <!-- Confirm -->

                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm new password"
                        minlength="8"
                        required
                        autocomplete="new-password"
                    >

                </div>


                <!-- Password Rules -->

                <div class="password-rules">

                    <strong>Password requirements:</strong>

                    <ul>

                        <li>
                            At least 8 characters
                        </li>

                        <li>
                            Use a combination of letters,
                            numbers and symbols
                        </li>

                    </ul>

                </div>


                <button
                    type="submit"
                    class="reset-btn"
                >
                    Update Password
                    <span>→</span>
                </button>

            </form>


            <div class="back-login">

                <a href="login.php">
                    ← Back to Login
                </a>

            </div>

        <?php endif; ?>

    </div>

</div>


<script>

/* =========================================================
   PASSWORD MATCH CHECK
========================================================= */

const form =
    document.querySelector('.forgot-form');

if (form) {

    form.addEventListener('submit', function(event) {

        const password =
            document.getElementById('password');

        const confirmPassword =
            document.getElementById('confirm_password');

        if (
            password &&
            confirmPassword &&
            password.value !== confirmPassword.value
        ) {

            event.preventDefault();

            alert('Passwords do not match.');

        }

    });

}

</script>

</body>

</html>