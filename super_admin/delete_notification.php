<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$notifId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($notifId <= 0) { header("Location: notifications.php"); exit(); }

$stmt = $conn->prepare("DELETE FROM notifications WHERE notification_id = ?");
$stmt->bind_param("i", $notifId);
$stmt->execute();
$stmt->close();

header("Location: notifications.php?deleted=1");
exit();
?>