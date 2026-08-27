<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0 && $id != $_SESSION['user_id']) {
    // Only demote if they are 'admin' (not super_admin)
    $stmt = $conn->prepare("UPDATE users SET role = 'user' WHERE user_id = ? AND role = 'admin'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
header("Location: admins.php");
exit();