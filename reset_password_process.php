<?php

session_start();

require_once 'includes/db.php';


/* =========================================================
   ONLY POST REQUEST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: login.php');
    exit;

}


/* =========================================================
   GET FORM DATA
========================================================= */

$token =
    trim($_POST['token'] ?? '');

$password =
    $_POST['password'] ?? '';

$confirm_password =
    $_POST['confirm_password'] ?? '';


/* =========================================================
   BASIC VALIDATION
========================================================= */

if (
    empty($token) ||
    empty($password) ||
    empty($confirm_password)
) {

    die('Please complete all fields.');

}


if ($password !== $confirm_password) {

    die('Passwords do not match.');

}


if (strlen($password) < 8) {

    die(
        'Password must contain at least 8 characters.'
    );

}


/* =========================================================
   HASH TOKEN
========================================================= */

$token_hash =
    hash('sha256', $token);


/* =========================================================
   FIND VALID RESET TOKEN
========================================================= */

$stmt = $conn->prepare("
    SELECT
        reset_id,
        user_id
    FROM password_resets
    WHERE token_hash = ?
      AND expires_at > NOW()
      AND used_at IS NULL
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


/* =========================================================
   INVALID TOKEN
========================================================= */

if (!$reset) {

    die(
        'This password reset link is invalid or expired.'
    );

}


/* =========================================================
   HASH NEW PASSWORD
========================================================= */

$new_password_hash =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );


/* =========================================================
   UPDATE USER PASSWORD
========================================================= */

$update_stmt = $conn->prepare("
    UPDATE users
    SET password = ?
    WHERE user_id = ?
");

$update_stmt->bind_param(
    "si",
    $new_password_hash,
    $reset['user_id']
);

$success =
    $update_stmt->execute();

$update_stmt->close();


if (!$success) {

    die(
        'Unable to update password. Please try again.'
    );

}


/* =========================================================
   MARK TOKEN AS USED
========================================================= */

$used_stmt = $conn->prepare("
    UPDATE password_resets
    SET used_at = NOW()
    WHERE reset_id = ?
");

$used_stmt->bind_param(
    "i",
    $reset['reset_id']
);

$used_stmt->execute();

$used_stmt->close();


/* =========================================================
   DELETE OTHER RESET TOKENS
========================================================= */

$cleanup_stmt = $conn->prepare("
    DELETE FROM password_resets
    WHERE user_id = ?
      AND reset_id != ?
");

$cleanup_stmt->bind_param(
    "ii",
    $reset['user_id'],
    $reset['reset_id']
);

$cleanup_stmt->execute();

$cleanup_stmt->close();


/* =========================================================
   SUCCESS
========================================================= */

$_SESSION['login_message'] =
    'Your password has been updated successfully. Please login with your new password.';

$_SESSION['login_message_type'] =
    'success';


header('Location: login.php');

exit;