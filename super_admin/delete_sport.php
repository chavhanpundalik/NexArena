<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$sportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($sportId <= 0) { header("Location: sports.php"); exit(); }

$stmt = $conn->prepare("SELECT sport_name FROM sports WHERE sport_id = ?");
$stmt->bind_param("i", $sportId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { header("Location: sports.php"); exit(); }
$sport = $result->fetch_assoc();
$stmt->close();

if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Check if sport is used in any event or fixture (optional – block deletion if in use)
    $used = $conn->prepare("SELECT COUNT(*) FROM events WHERE sport_id = ? UNION SELECT COUNT(*) FROM fixtures WHERE sport_id = ?");
    $used->bind_param("ii", $sportId, $sportId);
    $used->execute();
    $used->bind_result($cnt);
    $used->fetch();
    $used->close();
    if ($cnt > 0) {
        // Instead of deleting, inform user that sport is in use
        header("Location: sports.php?error=Sport is in use by events or fixtures and cannot be deleted.");
        exit();
    }
    $del = $conn->prepare("DELETE FROM sports WHERE sport_id = ?");
    $del->bind_param("i", $sportId);
    $del->execute();
    $del->close();
    header("Location: sports.php?deleted=1");
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
    <link rel="stylesheet" href="assets/sports.css">
</head>
<body>
<?php include "sidebar.php"; ?>
<main class="users-main">
    <div class="confirm-container">
        <div class="confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h2>Delete Sport?</h2>
        <p>You are about to delete the sport: <strong><?= htmlspecialchars($sport['sport_name']); ?></strong>. This cannot be undone.</p>
        <div class="confirm-actions">
            <a href="delete_sport.php?id=<?= $sportId; ?>&confirm=yes" class="btn-danger"><i class="fa-solid fa-trash"></i> Yes, Delete</a>
            <a href="sports.php" class="btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
        </div>
    </div>
</main>
</body>
</html>