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

// Get team details with captain name
$sql = "SELECT 
            t.*,
            u.username AS captain_name,
            u.email AS captain_email
        FROM teams t
        LEFT JOIN users u ON t.captain_id = u.user_id
        WHERE t.team_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teamId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { 
    header("Location: teams.php"); 
    exit(); 
}
$team = $result->fetch_assoc();
$stmt->close();

// Get team members
$members = [];
$membersSql = "SELECT 
                    u.user_id,
                    u.username,
                    u.email,
                    tm.role,
                    tm.joined_at
                FROM team_members tm
                JOIN users u ON tm.user_id = u.user_id
                WHERE tm.team_id = ?
                ORDER BY tm.role DESC, u.username";
$stmt = $conn->prepare($membersSql);
$stmt->bind_param("i", $teamId);
$stmt->execute();
$membersResult = $stmt->get_result();
while ($row = $membersResult->fetch_assoc()) {
    $members[] = $row;
}
$stmt->close();

// Get registrations for this team
$registrations = [];
$regSql = "SELECT 
                r.registration_id,
                r.event_id,
                e.event_name,
                e.event_date,
                r.status AS registration_status,
                r.registered_at
            FROM registrations r
            JOIN events e ON r.event_id = e.event_id
            WHERE r.team_id = ?
            ORDER BY e.event_date DESC";
$stmt = $conn->prepare($regSql);
$stmt->bind_param("i", $teamId);
$stmt->execute();
$regResult = $stmt->get_result();
while ($row = $regResult->fetch_assoc()) {
    $registrations[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Team | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <style>
        .team-profile { max-width:900px; margin:40px auto; }
        .profile-header { background:#fff; border-radius:18px; border:1px solid #e5e7eb; padding:30px; margin-bottom:25px; display:flex; align-items:center; gap:25px; flex-wrap:wrap; }
        .team-avatar { width:100px; height:100px; border-radius:50%; background:linear-gradient(135deg,#8b5cf6,#6d28d9); display:flex; align-items:center; justify-content:center; color:#fff; font-size:40px; flex-shrink:0; }
        .team-avatar img { width:100%; height:100%; border-radius:50%; object-fit:cover; }
        .team-title { flex:1; }
        .team-title h1 { font-size:28px; margin:0 0 5px 0; }
        .team-title .subtitle { color:#71717a; }
        .team-title .subtitle i { margin-right:5px; }
        .status-badge { display:inline-block; padding:4px 14px; border-radius:20px; font-size:12px; font-weight:600; text-transform:uppercase; margin-left:10px; }
        .status-active { background:#dcfce7; color:#16a34a; }
        .status-inactive { background:#fef2f2; color:#dc2626; }
        .status-full { background:#fef3c7; color:#d97706; }
        .status-disbanded { background:#f3f4f6; color:#6b7280; }
        .profile-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .profile-actions .btn { padding:10px 20px; border-radius:10px; text-decoration:none; font-weight:600; font-size:14px; display:inline-flex; align-items:center; gap:8px; transition:0.25s; }
        .btn-edit { background:#8b5cf6; color:#fff; }
        .btn-edit:hover { background:#7c3aed; transform:translateY(-2px); }
        .btn-delete { background:#dc2626; color:#fff; }
        .btn-delete:hover { background:#b91c1c; transform:translateY(-2px); }
        .btn-back { background:#f4f4f5; color:#1f2937; border:1px solid #e5e7eb; }
        .btn-back:hover { background:#e4e4e7; }
        
        .info-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:15px; margin-bottom:25px; }
        .info-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px; }
        .info-card .label { font-size:12px; color:#71717a; text-transform:uppercase; letter-spacing:0.5px; }
        .info-card .value { font-size:16px; font-weight:600; margin-top:4px; }
        
        .section-card { background:#fff; border-radius:18px; border:1px solid #e5e7eb; padding:25px; margin-bottom:25px; }
        .section-card .section-title { font-size:18px; font-weight:700; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .section-card .section-title i { color:#8b5cf6; }
        
        .member-list { display:grid; gap:10px; }
        .member-item { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; background:#fafafa; border-radius:10px; border:1px solid #f3f4f6; }
        .member-item .member-info { display:flex; align-items:center; gap:12px; }
        .member-item .member-avatar { width:36px; height:36px; border-radius:50%; background:#e0e7ff; display:flex; align-items:center; justify-content:center; color:#4f46e5; font-weight:600; }
        .member-item .role-badge { padding:2px 12px; border-radius:12px; font-size:11px; font-weight:600; }
        .role-captain { background:#fef3c7; color:#d97706; }
        .role-member { background:#e0e7ff; color:#4f46e5; }
        .role-co-captain { background:#dbeafe; color:#2563eb; }
        
        .empty-state { text-align:center; padding:30px 20px; color:#71717a; }
        .empty-state i { font-size:40px; color:#d1d5db; margin-bottom:15px; }
        
        .registration-item { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f3f4f6; }
        .registration-item:last-child { border-bottom:none; }
        .registration-status { padding:2px 12px; border-radius:12px; font-size:11px; font-weight:600; }
        .reg-pending { background:#fef3c7; color:#d97706; }
        .reg-approved { background:#dcfce7; color:#16a34a; }
        .reg-rejected { background:#fef2f2; color:#dc2626; }
        .reg-cancelled { background:#f3f4f6; color:#6b7280; }
        
        @media (max-width:600px) { 
            .profile-header { flex-direction:column; text-align:center; }
            .info-grid { grid-template-columns:1fr 1fr; }
            .member-item { flex-direction:column; align-items:flex-start; gap:8px; }
        }
    </style>
</head>
<body>
<?php include "sidebar.php"; ?>
<main class="users-main">
    <div class="team-profile">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="team-avatar">
                <?php if (!empty($team['logo'])): ?>
                    <img src="../<?= htmlspecialchars($team['logo']); ?>" alt="Team Logo">
                <?php else: ?>
                    <i class="fa-solid fa-people-group"></i>
                <?php endif; ?>
            </div>
            <div class="team-title">
                <h1>
                    <?= htmlspecialchars($team['team_name']); ?>
                    <span class="status-badge status-<?= $team['status'] ?? 'inactive'; ?>">
                        <?= $team['status'] ?? 'Inactive'; ?>
                    </span>
                </h1>
                <div class="subtitle">
                    <i class="fa-regular fa-futbol"></i> <?= htmlspecialchars($team['sport'] ?? 'Not specified'); ?>
                    <?php if (!empty($team['game'])): ?>
                        • <i class="fa-solid fa-gamepad"></i> <?= htmlspecialchars($team['game']); ?>
                    <?php endif; ?>
                </div>
                <div class="subtitle" style="margin-top:4px;">
                    <i class="fa-regular fa-calendar"></i> Created: <?= date('M d, Y', strtotime($team['created_at'])); ?>
                </div>
            </div>
            <div class="profile-actions">
                <a href="edit_team.php?id=<?= $teamId; ?>" class="btn btn-edit"><i class="fa-solid fa-pen"></i> Edit</a>
                <a href="delete_team.php?id=<?= $teamId; ?>" class="btn btn-delete"><i class="fa-solid fa-trash"></i> Delete</a>
                <a href="teams.php" class="btn btn-back"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>
        </div>
        
        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-card">
                <div class="label">Captain</div>
                <div class="value">
                    <?php if (!empty($team['captain_name'])): ?>
                        <i class="fa-solid fa-crown" style="color:#f59e0b;"></i>
                        <?= htmlspecialchars($team['captain_name']); ?>
                    <?php else: ?>
                        <span style="color:#999;font-weight:400;">Not assigned</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-card">
                <div class="label">Max Players</div>
                <div class="value"><?= $team['max_players'] ?? 11; ?></div>
            </div>
            <div class="info-card">
                <div class="label">Region</div>
                <div class="value"><?= !empty($team['region']) ? htmlspecialchars($team['region']) : '<span style="color:#999;font-weight:400;">Not specified</span>'; ?></div>
            </div>
            <div class="info-card">
                <div class="label">Privacy</div>
                <div class="value">
                    <?= ($team['is_private'] ?? 0) ? '<i class="fa-solid fa-lock" style="color:#dc2626;"></i> Private' : '<i class="fa-solid fa-globe" style="color:#16a34a;"></i> Public'; ?>
                </div>
            </div>
        </div>
        
        <!-- Description -->
        <?php if (!empty($team['description'])): ?>
        <div class="section-card">
            <div class="section-title"><i class="fa-solid fa-align-left"></i> Description</div>
            <p style="margin:0;color:#374151;line-height:1.6;"><?= nl2br(htmlspecialchars($team['description'])); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Members -->
        <div class="section-card">
            <div class="section-title">
                <i class="fa-solid fa-users"></i> Team Members
                <span style="font-size:14px;font-weight:400;color:#71717a;margin-left:5px;">(<?= count($members); ?>)</span>
            </div>
            <?php if (!empty($members)): ?>
                <div class="member-list">
                    <?php foreach ($members as $member): ?>
                        <div class="member-item">
                            <div class="member-info">
                                <div class="member-avatar">
                                    <?= strtoupper(substr($member['username'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?= htmlspecialchars($member['username']); ?></strong>
                                    <span style="font-size:12px;color:#71717a;display:block;"><?= htmlspecialchars($member['email']); ?></span>
                                </div>
                            </div>
                            <div>
                                <span class="role-badge role-<?= $member['role'] ?? 'member'; ?>">
                                    <?= ucfirst($member['role'] ?? 'Member'); ?>
                                </span>
                                <span style="font-size:11px;color:#999;margin-left:8px;">
                                    Joined: <?= date('M d, Y', strtotime($member['joined_at'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-users"></i>
                    <p>No members in this team yet.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Registrations -->
        <div class="section-card">
            <div class="section-title">
                <i class="fa-solid fa-calendar-check"></i> Event Registrations
                <span style="font-size:14px;font-weight:400;color:#71717a;margin-left:5px;">(<?= count($registrations); ?>)</span>
            </div>
            <?php if (!empty($registrations)): ?>
                <?php foreach ($registrations as $reg): ?>
                    <div class="registration-item">
                        <div>
                            <strong><?= htmlspecialchars($reg['event_name']); ?></strong>
                            <span style="font-size:13px;color:#71717a;display:block;">
                                <i class="fa-regular fa-calendar"></i> <?= date('M d, Y', strtotime($reg['event_date'])); ?>
                            </span>
                        </div>
                        <div>
                            <span class="registration-status reg-<?= $reg['registration_status'] ?? 'pending'; ?>">
                                <?= ucfirst($reg['registration_status'] ?? 'Pending'); ?>
                            </span>
                            <span style="font-size:11px;color:#999;margin-left:8px;">
                                <?= date('M d, Y', strtotime($reg['registered_at'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-calendar-xmark"></i>
                    <p>This team hasn't registered for any events yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>