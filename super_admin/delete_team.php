<?php
session_start();
require_once "../db_connect.php";

// --- Access control ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$teamId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($teamId <= 0) { 
    header("Location: teams.php"); 
    exit(); 
}

// Get team details
$stmt = $conn->prepare("SELECT team_name, sport FROM teams WHERE team_id = ?");
$stmt->bind_param("i", $teamId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { 
    header("Location: teams.php"); 
    exit(); 
}
$team = $result->fetch_assoc();
$stmt->close();

// Check if team has any team members
$hasRelations = false;
$memberCount = 0;
$checkMembers = $conn->prepare("SELECT COUNT(*) as count FROM team_members WHERE team_id = ?");
$checkMembers->bind_param("i", $teamId);
$checkMembers->execute();
$memberResult = $checkMembers->get_result();
$memberCount = $memberResult->fetch_assoc()['count'];
$checkMembers->close();

if ($memberCount > 0) {
    $hasRelations = true;
}

if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete team members first
        $delMembers = $conn->prepare("DELETE FROM team_members WHERE team_id = ?");
        $delMembers->bind_param("i", $teamId);
        $delMembers->execute();
        $delMembers->close();
        
        // Delete any other related records (like team stats, etc.)
        // Add more DELETE statements here if needed
        
        // Finally delete the team
        $delTeam = $conn->prepare("DELETE FROM teams WHERE team_id = ?");
        $delTeam->bind_param("i", $teamId);
        $delTeam->execute();
        $delTeam->close();
        
        $conn->commit();
        header("Location: teams.php?deleted=1");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        // If transaction fails, try with foreign key checks disabled
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
        
        $delTeam = $conn->prepare("DELETE FROM teams WHERE team_id = ?");
        $delTeam->bind_param("i", $teamId);
        $delTeam->execute();
        $delTeam->close();
        
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
        
        header("Location: teams.php?deleted=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Team | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <style>
        .confirm-container { max-width:500px; margin:80px auto; background:#fff; padding:40px; border-radius:18px; border:1px solid #e5e7eb; text-align:center; }
        .confirm-icon { font-size:48px; color:#dc2626; background:#fef2f2; width:80px; height:80px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; }
        .confirm-container h2 { font-size:24px; margin-bottom:10px; }
        .confirm-container p { color:#71717a; margin-bottom:10px; }
        .warning-note { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; padding:12px; border-radius:8px; margin:20px 0; font-size:14px; }
        .info-note { background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; padding:12px; border-radius:8px; margin:20px 0; font-size:14px; }
        .confirm-actions { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; margin-top:25px; }
        .btn-danger { background:#dc2626; color:#fff; border:none; padding:12px 28px; border-radius:10px; font-weight:700; font-size:15px; text-decoration:none; display:inline-flex; align-items:center; gap:8px; transition:0.25s; }
        .btn-danger:hover { background:#b91c1c; transform:translateY(-2px); }
        .btn-secondary { background:#f4f4f5; color:#1f2937; border:1px solid #e5e7eb; padding:12px 28px; border-radius:10px; font-weight:700; font-size:15px; text-decoration:none; display:inline-flex; align-items:center; gap:8px; transition:0.25s; }
        .btn-secondary:hover { background:#e4e4e7; }
    </style>
</head>
<body>
<?php include "sidebar.php"; ?>
<main class="users-main">
    <div class="confirm-container">
        <div class="confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h2>Delete Team?</h2>
        <p>You are about to delete <strong><?= htmlspecialchars($team['team_name']); ?></strong></p>
        <p style="color:#71717a;font-size:14px;">Sport: <?= htmlspecialchars($team['sport'] ?? 'Not specified'); ?></p>
        
        <?php if ($hasRelations): ?>
            <div class="warning-note">
                <i class="fa-solid fa-info-circle"></i> 
                This team has <?= $memberCount; ?> member(s). They will also be removed from the team.
            </div>
        <?php else: ?>
            <div class="info-note">
                <i class="fa-solid fa-check-circle"></i> 
                This team has no members and can be safely deleted.
            </div>
        <?php endif; ?>
        
        <p style="color:#71717a;font-size:13px;margin-top:10px;">This action cannot be undone.</p>
        
        <div class="confirm-actions">
            <a href="delete_team.php?id=<?= $teamId; ?>&confirm=yes" class="btn-danger"><i class="fa-solid fa-trash"></i> Yes, Delete</a>
            <a href="teams.php" class="btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
        </div>
    </div>
</main>
</body>
</html>