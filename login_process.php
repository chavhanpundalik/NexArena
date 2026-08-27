<?php

session_start();

// Fix: Use correct path for db_connect.php
// Since login_process.php is in the root directory, db_connect.php should be in the same directory
include 'db_connect.php';

// ========================================
// CHECK REQUEST METHOD
// ========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

// ========================================
// GET LOGIN DATA
// ========================================

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// ========================================
// CHECK EMPTY FIELDS
// ========================================

if ($email === '' || $password === '') {
    header("Location: login.php?error=empty_fields");
    exit();
}

// ========================================
// FIND USER
// ========================================

$sql = $conn->prepare(
    "SELECT
        user_id,
        full_name,
        username,
        email,
        phone,
        password,
        role
     FROM users
     WHERE email = ?"
);

if (!$sql) {
    die("Database Prepare Error: " . $conn->error);
}

$sql->bind_param("s", $email);

// ========================================
// EXECUTE QUERY
// ========================================

if (!$sql->execute()) {
    die("Database Execute Error: " . $sql->error);
}

$result = $sql->get_result();

// ========================================
// CHECK USER
// ========================================

if ($result->num_rows !== 1) {
    $sql->close();
    $conn->close();
    header("Location: login.php?error=email_not_found");
    exit();
}

$user = $result->fetch_assoc();

// ========================================
// VERIFY PASSWORD
// ========================================

if (!password_verify($password, $user['password'])) {
    $sql->close();
    $conn->close();
    header("Location: login.php?error=wrong_password");
    exit();
}

// ========================================
// CREATE SESSION
// ========================================

$_SESSION['user_id'] = $user['user_id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['phone'] = $user['phone'];
$_SESSION['role'] = $user['role'];

// ========================================
// CLOSE DATABASE
// ========================================

$sql->close();
$conn->close();

// ========================================
// REDIRECT ACCORDING TO ROLE
// ========================================

if ($_SESSION['role'] === 'super_admin') {
    header("Location: super_admin/dashboard.php");
    exit();
} elseif ($_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit();
} else {
    header("Location: user/dashboard.php");
    exit();
}

?>