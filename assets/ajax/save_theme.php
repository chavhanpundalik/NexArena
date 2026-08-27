<?php

session_start();

require_once "../db_connect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Check if database connection exists
if (!isset($conn) || !$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$dark_mode = isset($_POST['dark_mode']) ? (int) $_POST['dark_mode'] : 0;

// Check if settings exist
$check_sql = "SELECT setting_id FROM user_settings WHERE user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$exists = $check_result->num_rows > 0;
$check_stmt->close();

if ($exists) {
    // Update settings
    $update_sql = "UPDATE user_settings SET dark_mode = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $dark_mode, $user_id);
    $success = $update_stmt->execute();
    $update_stmt->close();
} else {
    // Insert settings
    $insert_sql = "INSERT INTO user_settings (user_id, dark_mode) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ii", $user_id, $dark_mode);
    $success = $insert_stmt->execute();
    $insert_stmt->close();
}

$conn->close();

header('Content-Type: application/json');
echo json_encode(['success' => $success]);

?>
