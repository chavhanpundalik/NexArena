<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
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

if ($team_id <= 0) {
    header("Location: teams.php");
    exit();
}

function clean($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Get team details
$team_sql = $conn->prepare("
    SELECT 
        t.*,
        e.event_name,
        e.event_date,
        e.location
    FROM teams t
    LEFT JOIN events e ON t.event_id = e.event_id
    WHERE t.team_id = ?
");
$team_sql->bind_param("i", $team_id);
$team_sql->execute();
$team = $team_sql->get_result()->fetch_assoc();

if (!$team) {
    header("Location: teams.php");
    exit();
}

// Check if user is member
$member_sql = $conn->prepare("
    SELECT role, joined_at 
    FROM team_members 
    WHERE team_id = ? AND user_id = ?
");
$member_sql->bind_param("ii", $team_id, $user_id);
$member_sql->execute();
$member = $member_sql->get_result()->fetch_assoc();

$is_member = $member !== null;
$is_captain = $is_member && $member['role'] === 'captain';

// Get team members
$members_sql = $conn->prepare("
    SELECT 
        u.user_id,
        u.full_name,
        u.username,
        tm.role,
        tm.joined_at
    FROM team_members tm
    INNER JOIN users u ON tm.user_id = u.user_id
    WHERE tm.team_id = ?
    ORDER BY tm.role = 'captain' DESC, tm.joined_at ASC
");
$members_sql->bind_param("i", $team_id);
$members_sql->execute();
$members = $members_sql->get_result();

// Don't close connection here - sidebar needs it
// $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($team['team_name']) ?> | NexArena</title>
    
    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/theme.css">
    
    <!-- Page CSS -->
    <link rel="stylesheet" href="assets/team.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        .team-detail-container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
            margin-left: 290px;
        }
        [data-theme="dark"] .team-detail-container {
            /* Inherits from theme */
        }
        .team-detail-header {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-primary);
            position: relative;
        }
        .team-detail-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--orange);
            border-radius: 20px 20px 0 0;
        }
        .team-detail-header h1 {
            font-size: 32px;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        .team-detail-header .meta {
            color: var(--text-muted);
            font-size: 14px;
        }
        .team-detail-header .meta i {
            margin-right: 5px;
            color: var(--orange);
        }
        .team-actions {
            display: flex;
            gap: 12px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .team-actions a,
        .team-actions button {
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-edit {
            background: var(--orange);
            color: #fff;
        }
        .btn-edit:hover {
            background: var(--orange-hover);
            transform: translateY(-2px);
        }
        .btn-leave {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid var(--danger-border);
        }
        .btn-leave:hover {
            background: var(--danger);
            color: #fff;
        }
        .btn-join {
            background: var(--orange);
            color: #fff;
        }
        .btn-join:hover {
            background: var(--orange-hover);
            transform: translateY(-2px);
        }
        .btn-back {
            background: var(--bg-input);
            color: var(--text-primary);
            border: 1px solid var(--border-input);
        }
        .btn-back:hover {
            background: var(--border-hover);
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        .detail-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid var(--border-primary);
            box-shadow: var(--shadow-sm);
        }
        .detail-card h2 {
            font-size: 20px;
            color: var(--text-primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .detail-card h2 i {
            color: var(--orange);
        }
        
        .member-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .member-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            border-radius: 12px;
            border: 1px solid var(--border-light);
        }
        .member-item .member-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .member-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--orange);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        .member-name {
            font-weight: 600;
            color: var(--text-primary);
        }
        .member-role {
            font-size: 12px;
            color: var(--text-muted);
            background: var(--bg-input);
            padding: 3px 10px;
            border-radius: 20px;
        }
        .member-role.captain {
            background: var(--orange);
            color: #fff;
        }
        .member-joined {
            font-size: 12px;
            color: var(--text-lighter);
        }
        
        .empty-members {
            text-align: center;
            padding: 30px 0;
            color: var(--text-muted);
        }
        
        .team-status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .team-status-badge.active {
            background: var(--success-bg);
            color: var(--success);
        }
        .team-status-badge.inactive {
            background: var(--danger-bg);
            color: var(--danger);
        }
        .team-status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        [data-theme="dark"] .team-status-badge.pending {
            background: #3a2a0a;
            color: #fbbf24;
        }
        
        @media (max-width: 768px) {
            .team-detail-container {
                margin-left: 80px;
                padding: 0 15px;
            }
            .detail-grid {
                grid-template-columns: 1fr;
            }
            .team-detail-header {
                padding: 20px;
            }
            .team-detail-header h1 {
                font-size: 26px;
            }
        }
        @media (max-width: 400px) {
            .team-detail-container {
                margin-left: 68px;
                padding: 0 10px;
            }
            .team-detail-header h1 {
                font-size: 22px;
            }
            .detail-card {
                padding: 18px;
            }
            .member-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>
</head>
<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">
    <?php include "sidebar.php"; ?>
    
    <div class="team-detail-container">
        <!-- Team Header -->
        <div class="team-detail-header">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1><?= clean($team['team_name']) ?></h1>
                    <div class="meta">
                        <i class="fas fa-calendar-alt"></i> 
                        <?= !empty($team['event_name']) ? clean($team['event_name']) : 'No event assigned' ?>
                    </div>
                    <?php if (!empty($team['event_date'])): ?>
                        <div class="meta">
                            <i class="fas fa-clock"></i> 
                            <?= date("d M Y", strtotime($team['event_date'])) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($team['location'])): ?>
                        <div class="meta">
                            <i class="fas fa-map-marker-alt"></i> 
                            <?= clean($team['location']) ?>
                        </div>
                    <?php endif; ?>
                    <div style="margin-top: 10px;">
                        <span class="team-status-badge <?= clean(strtolower($team['status'] ?? 'active')) ?>">
                            <?= ucfirst(clean($team['status'] ?? 'Active')) ?>
                        </span>
                    </div>
                </div>
                <div style="text-align: right;">
                    <?php if ($is_captain): ?>
                        <div style="color: var(--orange); font-weight: 600;">
                            <i class="fas fa-crown"></i> Captain
                        </div>
                    <?php elseif ($is_member): ?>
                        <div style="color: var(--text-muted);">
                            <i class="fas fa-user"></i> Member
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="team-actions">
                <a href="teams.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Teams</a>
                <?php if ($is_captain): ?>
                    <a href="team_settings.php?id=<?= $team_id ?>" class="btn-edit"><i class="fas fa-cog"></i> Settings</a>
                <?php endif; ?>
                <?php if ($is_member): ?>
                    <button class="btn-leave" onclick="if(confirm('Are you sure you want to leave this team?')){ window.location.href='leave_team.php?id=<?= $team_id ?>'; }">
                        <i class="fas fa-sign-out-alt"></i> Leave Team
                    </button>
                <?php else: ?>
                    <a href="join_team.php?id=<?= $team_id ?>" class="btn-join"><i class="fas fa-user-plus"></i> Join Team</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Detail Grid -->
        <div class="detail-grid">
            <!-- Members -->
            <div class="detail-card">
                <h2><i class="fas fa-users"></i> Team Members</h2>
                <div class="member-list">
                    <?php if ($members->num_rows > 0): ?>
                        <?php while ($member = $members->fetch_assoc()): ?>
                            <div class="member-item">
                                <div class="member-info">
                                    <div class="member-avatar">
                                        <?= strtoupper(substr($member['full_name'] ?? $member['username'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="member-name"><?= clean($member['full_name'] ?? $member['username'] ?? 'Unknown') ?></div>
                                        <span class="member-joined">Joined <?= date("d M Y", strtotime($member['joined_at'])) ?></span>
                                    </div>
                                </div>
                                <span class="member-role <?= $member['role'] === 'captain' ? 'captain' : '' ?>">
                                    <?= ucfirst($member['role'] ?? 'Member') ?>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-members">
                            <p>No members yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Team Info -->
            <div class="detail-card">
                <h2><i class="fas fa-info-circle"></i> Team Info</h2>
                
                <?php if (!empty($team['description'])): ?>
                    <p style="color: var(--text-secondary); line-height: 1.7; margin-bottom: 15px;">
                        <?= clean($team['description']) ?>
                    </p>
                <?php endif; ?>
                
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div>
                        <small style="color: var(--text-lighter);">Game</small>
                        <div style="font-weight: 600; color: var(--text-primary);">
                            <?= clean($team['game'] ?? 'Not specified') ?>
                        </div>
                    </div>
                    <div>
                        <small style="color: var(--text-lighter);">Region</small>
                        <div style="font-weight: 600; color: var(--text-primary);">
                            <?= clean($team['region'] ?? 'Not specified') ?>
                        </div>
                    </div>
                    <div>
                        <small style="color: var(--text-lighter);">Max Players</small>
                        <div style="font-weight: 600; color: var(--text-primary);">
                            <?= (int)($team['max_players'] ?? 11) ?>
                        </div>
                    </div>
                    <div>
                        <small style="color: var(--text-lighter);">Visibility</small>
                        <div style="font-weight: 600; color: var(--text-primary);">
                            <?= ($team['is_private'] ?? 0) ? '🔒 Private' : '🌍 Public' ?>
                        </div>
                    </div>
                    <div>
                        <small style="color: var(--text-lighter);">Created</small>
                        <div style="font-weight: 600; color: var(--text-primary);">
                            <?= date("d M Y", strtotime($team['created_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Theme JavaScript - MUST BE LAST -->
    <script src="assets/theme.js"></script>
</body>
</html>

<?php
// Don't close connection here - sidebar needs it
// $conn->close();
?>