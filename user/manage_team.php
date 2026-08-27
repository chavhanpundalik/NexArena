<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=login_required");
    exit();
}

require_once "../db_connect.php";

$user_id = (int) $_SESSION['user_id'];
$team_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get dark mode setting
$dark_mode = 0;
$settings_sql = "SELECT dark_mode FROM user_settings WHERE user_id = ?";
$settings_stmt = $conn->prepare($settings_sql);
$settings_stmt->bind_param("i", $user_id);
$settings_stmt->execute();
$settings_result = $settings_stmt->get_result();

if ($settings_result->num_rows > 0) {
    $settings_data = $settings_result->fetch_assoc();
    $dark_mode = $settings_data['dark_mode'] ?? 0;
}
$settings_stmt->close();

$dark_mode_class = ($dark_mode == 1) ? 'dark-mode' : '';

if ($team_id == 0) {
    header("Location: teams.php?error=invalid_team");
    exit();
}

// Fetch team details and check if user is captain
$team_sql = $conn->prepare("
    SELECT t.*, 
           (SELECT COUNT(*) FROM team_members WHERE team_id = t.team_id AND status = 'active') as member_count,
           u.full_name as captain_name
    FROM teams t
    LEFT JOIN users u ON t.captain_id = u.user_id
    WHERE t.team_id = ?
");
$team_sql->bind_param("i", $team_id);
$team_sql->execute();
$team_result = $team_sql->get_result();

if ($team_result->num_rows == 0) {
    header("Location: teams.php?error=team_not_found");
    exit();
}

$team = $team_result->fetch_assoc();
$is_captain = ($team['captain_id'] == $user_id);

// Fetch team members
$members_sql = $conn->prepare("
    SELECT tm.*, u.full_name, u.email, u.user_id
    FROM team_members tm
    INNER JOIN users u ON tm.user_id = u.user_id
    WHERE tm.team_id = ? AND tm.status = 'active'
    ORDER BY FIELD(tm.role, 'captain', 'co-captain', 'member')
");
$members_sql->bind_param("i", $team_id);
$members_sql->execute();
$members_result = $members_sql->get_result();

// Handle player addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_player' && $is_captain) {
        $player_email = trim($_POST['player_email'] ?? '');
        
        if (!empty($player_email)) {
            // Check if user exists
            $user_check = $conn->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
            $user_check->bind_param("s", $player_email);
            $user_check->execute();
            $user_result = $user_check->get_result();
            
            if ($user_result->num_rows > 0) {
                $player = $user_result->fetch_assoc();
                $player_id = $player['user_id'];
                
                // Check if already in team
                $check_member = $conn->prepare("SELECT * FROM team_members WHERE team_id = ? AND user_id = ?");
                $check_member->bind_param("ii", $team_id, $player_id);
                $check_member->execute();
                $member_check_result = $check_member->get_result();
                
                if ($member_check_result->num_rows == 0) {
                    // Add player to team
                    $add_sql = $conn->prepare("
                        INSERT INTO team_members (team_id, user_id, role, status) 
                        VALUES (?, ?, 'member', 'active')
                    ");
                    $add_sql->bind_param("ii", $team_id, $player_id);
                    
                    if ($add_sql->execute()) {
                        $_SESSION['success'] = $player['full_name'] . " has been added to the team!";
                    } else {
                        $_SESSION['error'] = "Failed to add player. Please try again.";
                    }
                } else {
                    $_SESSION['error'] = "This player is already in the team.";
                }
            } else {
                $_SESSION['error'] = "User not found with this email.";
            }
        } else {
            $_SESSION['error'] = "Please enter a valid email.";
        }
        header("Location: manage_team.php?id=" . $team_id);
        exit();
    }
    
    if ($action === 'remove_player' && $is_captain) {
        $remove_user_id = (int)$_POST['user_id'];
        
        if ($remove_user_id != $user_id) { // Can't remove yourself
            $remove_sql = $conn->prepare("
                DELETE FROM team_members 
                WHERE team_id = ? AND user_id = ? AND role != 'captain'
            ");
            $remove_sql->bind_param("ii", $team_id, $remove_user_id);
            
            if ($remove_sql->execute() && $remove_sql->affected_rows > 0) {
                $_SESSION['success'] = "Player removed from team.";
            } else {
                $_SESSION['error'] = "Failed to remove player.";
            }
        } else {
            $_SESSION['error'] = "You cannot remove yourself as captain.";
        }
        header("Location: manage_team.php?id=" . $team_id);
        exit();
    }
}

// Handle flash messages
$successMsg = $_SESSION['success'] ?? null;
$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

function clean($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Don't close connection here - sidebar needs it
// $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Team | NexArena</title>
    
    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/theme.css">
    
    <!-- Page CSS -->
    <link rel="stylesheet" href="assets/team.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        .manage-team-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
            margin-left: 290px;
        }
        
        [data-theme="dark"] .manage-team-container {
            /* Inherits from theme */
        }
        
        .team-header {
            background: var(--orange-gradient);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
        }
        
        .team-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 800;
        }
        
        .team-header .team-meta {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        .team-header .team-meta span {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .team-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-primary);
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: 800;
            color: var(--orange);
        }
        
        .stat-card .label {
            color: var(--text-muted);
            font-size: 14px;
        }
        
        .members-section {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-primary);
        }
        
        .members-section h2 {
            margin: 0 0 20px 0;
            font-size: 24px;
            color: var(--text-primary);
        }
        
        .member-list {
            display: grid;
            gap: 15px;
        }
        
        .member-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: var(--bg-tertiary);
            border-radius: 12px;
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }
        
        .member-item:hover {
            transform: translateX(5px);
            background: var(--bg-hover);
        }
        
        .member-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--orange);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        
        .member-details h4 {
            margin: 0;
            font-size: 16px;
            color: var(--text-primary);
        }
        
        .member-details small {
            color: var(--text-muted);
            font-size: 12px;
        }
        
        .member-role {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .role-captain {
            background: var(--orange);
            color: white;
        }
        
        .role-co-captain {
            background: #ffb347;
            color: white;
        }
        
        [data-theme="dark"] .role-co-captain {
            background: #8a5a00;
            color: white;
        }
        
        .role-member {
            background: var(--bg-input);
            color: var(--text-muted);
        }
        
        .member-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-remove {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid var(--danger-border);
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
        }
        
        .btn-remove:hover {
            background: var(--danger);
            color: white;
        }
        
        .add-player-form {
            background: var(--bg-tertiary);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-light);
            margin-bottom: 20px;
        }
        
        .add-player-form .form-row {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .add-player-form input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid var(--border-input);
            border-radius: 10px;
            font-size: 14px;
            background: var(--bg-input);
            color: var(--text-primary);
            transition: all 0.2s ease;
        }
        
        .add-player-form input:focus {
            border-color: var(--orange);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,107,53,0.1);
        }
        
        .btn-primary {
            background: var(--orange);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background: var(--orange-hover);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: var(--bg-input);
            color: var(--text-primary);
            border: 1px solid var(--border-input);
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }
        
        .btn-secondary:hover {
            background: var(--border-hover);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid var(--success-border);
        }
        
        .alert-error {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid var(--danger-border);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--text-muted);
            text-decoration: none;
        }
        
        .back-link:hover {
            color: var(--orange);
        }
        
        @media (max-width: 768px) {
            .manage-team-container {
                margin-left: 80px;
                padding: 15px;
            }
            
            .add-player-form .form-row {
                flex-direction: column;
            }
            
            .member-item {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .member-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
        
        @media (max-width: 400px) {
            .manage-team-container {
                margin-left: 68px;
                padding: 10px;
            }
        }
    </style>
</head>
<body class="<?php echo $dark_mode_class; ?>">
    <?php include "sidebar.php"; ?>
    
    <div class="manage-team-container">
        <!-- Flash Messages -->
        <?php if ($successMsg): ?>
            <div class="alert alert-success"><?= clean($successMsg) ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="alert alert-error"><?= clean($errorMsg) ?></div>
        <?php endif; ?>
        
        <!-- Team Header -->
        <div class="team-header">
            <h1><i class="fas fa-users"></i> <?= clean($team['team_name']) ?></h1>
            <div class="team-meta">
                <span><i class="fas fa-gamepad"></i> <?= clean($team['game'] ?? 'Not specified') ?></span>
                <span><i class="fas fa-map-marker-alt"></i> <?= clean($team['region'] ?? 'Not specified') ?></span>
                <span><i class="fas fa-user-tie"></i> Captain: <?= clean($team['captain_name']) ?></span>
                <span><i class="fas fa-users"></i> <?= $team['member_count'] ?> members</span>
                <span><i class="fas fa-lock"></i> <?= $team['is_private'] ? 'Private' : 'Public' ?></span>
            </div>
        </div>
        
        <!-- Team Stats -->
        <div class="team-stats">
            <div class="stat-card">
                <div class="number"><?= $team['member_count'] ?></div>
                <div class="label">Total Members</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $is_captain ? '👑' : '⭐' ?></div>
                <div class="label"><?= $is_captain ? 'You are the Captain' : 'Team Member' ?></div>
            </div>
            <div class="stat-card">
                <div class="number"><?= date('d M Y', strtotime($team['created_at'])) ?></div>
                <div class="label">Created</div>
            </div>
        </div>
        
        <!-- Members Section -->
        <div class="members-section">
            <h2><i class="fas fa-user-friends"></i> Team Members</h2>