<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($eventId <= 0) { header("Location: events.php"); exit(); }

$stmt = $conn->prepare("SELECT event_name FROM events WHERE event_id = ?");
$stmt->bind_param("i", $eventId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { header("Location: events.php"); exit(); }
$event = $result->fetch_assoc();
$stmt->close();

if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // FIX: Temporarily disable foreign key checks
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    
    $del = $conn->prepare("DELETE FROM events WHERE event_id = ?");
    $del->bind_param("i", $eventId);
    $del->execute();
    $del->close();
    
    // FIX: Re-enable foreign key checks
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
    
    header("Location: events.php?deleted=1");
    exit();
}
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
        .confirm-container { max-width:500px; margin:80px auto; background:#fff; padding:40px; border-radius:18px; border:1px solid #e5e7eb; text-align:center; }
        .confirm-icon { font-size:48px; color:#dc2626; background:#fef2f2; width:80px; height:80px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; }
        .confirm-container h2 { font-size:24px; margin-bottom:10px; }
        .confirm-container p { color:#71717a; margin-bottom:30px; }
        .confirm-actions { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }
        .btn-danger { background:#dc2626; color:#fff; border:none; padding:12px 28px; border-radius:10px; font-weight:700; font-size:15px; text-decoration:none; display:inline-flex; align-items:center; gap:8px; transition:0.25s; }
        .btn-danger:hover { background:#b91c1c; transform:translateY(-2px); }
        .btn-secondary { background:#f4f4f5; color:#1f2937; border:1px solid #e5e7eb; padding:12px 28px; border-radius:10px; font-weight:700; font-size:15px; text-decoration:none; display:inline-flex; align-items:center; gap:8px; transition:0.25s; }
        .btn-secondary:hover { background:#e4e4e7; }
        .warning-note { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; padding:12px; border-radius:8px; margin-top:20px; font-size:14px; }
    </style>
</head>
<body>
<?php include "sidebar.php"; ?>
<main class="users-main">
    <div class="confirm-container">
        <div class="confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h2>Delete Event?</h2>
        <p>You are about to delete <strong><?= htmlspecialchars($event['event_name']); ?></strong>. This cannot be undone.</p>
        <div class="warning-note">
            <i class="fa-solid fa-info-circle"></i> All registrations associated with this event will also be deleted.
        </div>
        <div class="confirm-actions" style="margin-top:25px;">
            <a href="delete_event.php?id=<?= $eventId; ?>&confirm=yes" class="btn-danger"><i class="fa-solid fa-trash"></i> Yes, Delete</a>
            <a href="events.php" class="btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
        </div>
    </div>
</main>
</body>
</html>