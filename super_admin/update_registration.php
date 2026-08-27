<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$regId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($regId <= 0 || !in_array($action, ['approve', 'reject', 'cancel'])) {
    header("Location: registrations.php");
    exit();
}

// Map action to status
$newStatus = '';
if ($action === 'approve') $newStatus = 'approved';
elseif ($action === 'reject') $newStatus = 'rejected';
elseif ($action === 'cancel') $newStatus = 'cancelled';

$stmt = $conn->prepare("UPDATE event_registrations SET status = ? WHERE registration_id = ?");
$stmt->bind_param("si", $newStatus, $regId);
$stmt->execute();
$stmt->close();

// Optionally, if approved, you might set approved_at timestamp (if column exists)
// We'll set approved_at to NOW if approved
if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE event_registrations SET approved_at = NOW() WHERE registration_id = ?");
    $stmt->bind_param("i", $regId);
    $stmt->execute();
    $stmt->close();
}

// Redirect back with message
header("Location: registrations.php?message=" . $action . "d");
exit();
?>