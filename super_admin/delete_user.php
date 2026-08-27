<?php
session_start();
require_once "../db_connect.php";

// --- Access control ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$loggedInId = (int)$_SESSION['user_id'];

// --- Get user ID ---
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($userId <= 0) {
    $_SESSION['delete_error'] = "Invalid user ID.";
    header("Location: users.php");
    exit();
}

// --- Check if user exists ---
$stmt = $conn->prepare("SELECT user_id, username, role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['delete_error'] = "User not found.";
    header("Location: users.php");
    exit();
}
$user = $result->fetch_assoc();
$stmt->close();

// --- Safety checks ---

// 1. Cannot delete yourself
if ($userId === $loggedInId) {
    $_SESSION['delete_error'] = "You cannot delete your own account.";
    header("Location: users.php");
    exit();
}

// 2. Cannot delete the last Super Admin
if ($user['role'] === 'super_admin') {
    $countStmt = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'super_admin'");
    $countRow = $countStmt->fetch_assoc();
    if ((int)$countRow['total'] <= 1) {
        $_SESSION['delete_error'] = "Cannot delete the only Super Admin. At least one Super Admin must exist.";
        header("Location: users.php");
        exit();
    }
}

// --- Confirmation ---
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if ($confirmed) {
    // Proceed with deletion
    $deleteStmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $deleteStmt->bind_param("i", $userId);
    if ($deleteStmt->execute()) {
        // --- LOG ACTIVITY ---
        log_activity($conn, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Admin', 'User Deleted', "Deleted user: {$user['username']} (ID: $userId) with role: {$user['role']}");

        $_SESSION['delete_success'] = "User '{$user['username']}' deleted successfully.";
    } else {
        $_SESSION['delete_error'] = "Database error: " . $conn->error;
    }
    $deleteStmt->close();
    header("Location: users.php");
    exit();
} else {
    // Show confirmation dialog using a simple HTML page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirm Delete | NexArena</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="assets/sidebar.css">
        <link rel="stylesheet" href="assets/users.css">
        <style>
            .confirm-container {
                max-width: 500px;
                margin: 80px auto;
                background: #fff;
                padding: 40px;
                border-radius: 18px;
                border: 1px solid #e5e7eb;
                box-shadow: 0 10px 30px rgba(0,0,0,0.07);
                text-align: center;
            }
            .confirm-icon {
                font-size: 48px;
                color: #dc2626;
                background: #fef2f2;
                width: 80px;
                height: 80px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
            }
            .confirm-container h2 {
                font-size: 24px;
                margin-bottom: 10px;
            }
            .confirm-container p {
                color: #71717a;
                margin-bottom: 30px;
            }
            .confirm-actions {
                display: flex;
                gap: 14px;
                justify-content: center;
                flex-wrap: wrap;
            }
            .btn-danger {
                background: #dc2626;
                color: #fff;
                border: none;
                padding: 12px 28px;
                border-radius: 10px;
                font-weight: 700;
                font-size: 15px;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: 0.25s;
            }
            .btn-danger:hover {
                background: #b91c1c;
                transform: translateY(-2px);
            }
            .btn-secondary {
                background: #f4f4f5;
                color: #1f2937;
                border: 1px solid #e5e7eb;
                padding: 12px 28px;
                border-radius: 10px;
                font-weight: 700;
                font-size: 15px;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: 0.25s;
            }
            .btn-secondary:hover {
                background: #e4e4e7;
            }
            .user-name {
                font-weight: 800;
                color: #1f2937;
            }
        </style>
    </head>
    <body>
        <?php include "sidebar.php"; ?>
        <main class="users-main">
            <div class="confirm-container">
                <div class="confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <h2>Are you sure?</h2>
                <p>
                    You are about to delete user <strong class="user-name"><?= htmlspecialchars($user['username']); ?></strong>.
                    This action cannot be undone.
                </p>
                <div class="confirm-actions">
                    <a href="delete_user.php?id=<?= $userId; ?>&confirm=yes" class="btn-danger">
                        <i class="fa-solid fa-trash"></i> Yes, Delete
                    </a>
                    <a href="users.php" class="btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit();
}
?>